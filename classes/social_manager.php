<?php

class Social_Manager
{
	// Class/Object handling
	// 

	public static function get_providers()
	{
		return Phpr_Driver_Manager::get_drivers('Social_Provider_Base');
	}

	public static function get_provider($code)
	{
		return Phpr_Driver_Manager::get_driver('Social_Provider_Base', $code);
	}

	// Access point handling
	// 

	/**
	 * Handles login for a provider when it returns to our site with the relevant info
	 * @return void
	 */
	public function api_callback($params=array())
	{
		if (!isset($params[0])) {
			return $this->handle_error(array(
				'debug' => "provider_callable(): Unable to determine which provider we are authenticating with. Missing provider code",
				'user' => "We were unable to determine who you were trying to log in with."
			));
		}

		$provider_code = $params[0];
		$provider = Social_Provider::get_provider($provider_code);
		
		if (!$provider) {
			return $this->handle_error(array(
				'debug' => "provider_callable(): No provider of id '".$provider_code."' found or provider not enabled.",
				'user' => "We were unable to determine who you were trying to log in with."
			));
		}

		try 
		{
			$user_data = $provider->login();
			if (!is_array($user_data))
				return $this->handle_error($provider->get_error());
		} 
		catch (Exception $e)
		{
			return $this->handle_error(array(
				'debug' => "provider_callable(): Provider '".$provider_code."' error: ".$e->getMessage(),
				'user' => $e->getMessage()
			));
		}

		$user = self::get_provider_user($provider, $user_data);
		
		// A user wasn't found or created which means we're forcing emails
		// So redirect to forced email page
		if (!$user && $provider->registration_redirect)
		{
			$user_data['provider_code'] = $provider->get_code();
			Phpr::$session->set('social_user_data', $user_data);
			Phpr::$response->redirect($provider->registration_redirect);
			return;
		}

		// Log the user in
		Phpr::$frontend_security->user_login($user->id);
		Phpr::$response->redirect(root_url('/', true));
	}

	/**
	 * Some providers require a special login page. Use a URL like:
	 * /social_api_login/?provider=PROVIDER_ID
	 */
	public function api_login()
	{
		$provider_code = Phpr::$request->get_field('provider');
		$provider = Social_Provider::get_provider($provider_code);
		
		if (!$provider_code || !$provider)
		{
			return $this->handle_error(array(
				'debug' => "api_login(): No provider of id '".$provider_code."' found or provider not enabled.",
				'user' => "We were unable to determine who you were trying to log in with."
			));
		}

		try 
		{
			if (!$provider->send_login_request())
				return $this->handle_error($provider->get_error());
		} 
		catch (Exception $ex) 
		{            
			return $this->handle_error(array(
				'debug' => "api_login(): Provider '".$provider_code."' error: ".$ex->getMessage(),
				'user' => $ex->getMessage()
			));

		}
	}


	/**
	 * The confirmation URL a user clicks when associating a provider with
	 * their user account.
	 * @return Session flash message and redirect to homepage
	 */
	public function api_associate()
	{
		$confirm = Phpr::$request->get_field('confirm');
		if (!$confirm)
		{
			Phpr::$session->flash['error'] = "Invalid confirmation URL.";
			Phpr::$response->redirect('/');
			return;
		}

		$user_provider = Social_Provider_User::create()
			->where('is_enabled = 0')
			->where('CONCAT(id, user_id, provider_token)=:confirm', array(
				'confirm' => $confirm
			))->find();

		if (!$user_provider)
		{
			Phpr::$session->flash['error'] = "Invalid confirmation code.";
			Phpr::$response->redirect('/');
			return;
		}

		$user_provider->is_enabled = true;
		$user_provider->save();

		// Log the user in
		Phpr::$session->flash['success'] = 'Account successfully associated.';
		Phpr::$frontend_security->userLogin($user_provider->user_id);
		Phpr::$response->redirect(root_url('/', true));
	}


	//
	// User Management
	// 

	/**
	 * Creates a user for a given provider when an email is provided
	 */
	public static function get_provider_user($provider, $user_data)
	{
		$user = null;
		$insert_user_provider = true;

		// Try to find an existing user with matching provider and token
		$user_provider = Social_Provider_User::create()->where('provider_code=:provider_code and provider_token=:provider_token', array(
			'provider_code' => $provider->get_code(),
			'provider_token' => $user_data['token'],
		))->find();

		if ($user_provider)
		{
			// Customer has already associated but hasn't responded to the activation email
			if (!$user_provider->is_enabled)
				return false;

			// This account has a valid user_provider attached. No need to waste DB calls rebuilding it
			if ($user_provider->user)
				return $user_provider->user;
		}

		// If no email given and we're forcing emails, dont create an empty user
		if (!isset($user_data['email']))
			return false;

		// Try to find a user with this email if one was provided
		$user = User::create()->find_by_email($user_data['email']);
		
		if ($user) {
			self::set_provider_user($user, $user_data, $provider, true);
			return $user;
		}

		$user = self::create_new_user($user_data);
		self::set_provider_user($user, $user_data, $provider, true);
		return $user;
	}

	public static function create_new_user($user_data)
	{
		// Existing user not found, create one
		$user = User::create();
		$user->disable_column_cache('front_end', false);
		$user->init_columns('front_end');
		$user->validation->focus_prefix = null;

		// If no email is provided, make the email field optional
		if (!isset($user_data['email']))
			$user->validation->get_rule('email')->optional();

		if (!isset($user_data['first_name']))
			$user->validation->get_rule('first_name')->optional();

		if (!isset($user_data['last_name']) )
			$user->validation->get_rule('last_name')->optional();

		//
		// @todo this logic should execute the user:on_register action instead
		//

		$user->generate_password();

		// Fee check
		Phpr_Module_Manager::module_exists('payment') && Payment_Fee::trigger_event('User_Register_Event', array('handler'=>'user:on_register'));

		$user->save($user_data);

		// Send notification
		Notify::trigger('user:register_confirm', array('user'=>$user));

		return $user;
	}

	public static function set_provider_user($user, $user_data, $provider, $is_enabled = true)
	{
		if (!$user || !$provider)
			return false;

		$user_provider = Social_Provider_User::create()->save(array(
			'user_id' => $user->id,
			'provider_code' => $provider->get_code(),
			'provider_token' => $user_data['token'],
			'is_enabled' => $is_enabled ? 1 : 0,
		));

		return $user_provider;
	}

	//
	// Error handling
	// 

	public function handle_error($messages)
	{
		if (Cms_Config::is_dev_mode())
			die($messages['debug']);

		Phpr::$session->flash['error'] = $messages['user'];
		Phpr::$response->redirect(root_url("/"));
	}

}