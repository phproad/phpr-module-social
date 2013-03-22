<?php

class Social_WindowsLive_Provider extends Social_Provider_Base
{
	public function get_info()
	{
		return array(
			'code' => 'windowslive',
			'name'=>'Windows Live',
		);
	}

	/**
	 * Builds the social login administration user interface
	 * For drop-down and radio fields you should also add methods returning
	 * options. For example, of you want to have Sizes drop-down:
	 * public function get_sizes_options();
	 * This method should return array with keys corresponding your option identifiers
	 * and values corresponding its titles.
	 *
	 * @param $host ActiveRecord object to add fields to
	 * @param string $context Form context. In preview mode its value is 'preview'
	 */
	public function build_config_ui($host, $context = null)
	{
		$host->add_form_partial($this->get_partial_path('hint.htm'));
		$host->add_field('windowslive_app_id', 'Client ID', 'full', db_text)->display_as(frm_text);
		$host->add_field('windowslive_secret', 'Client Secret', 'full', db_text)->display_as(frm_text);
	}

	public function get_client()
	{
		$host = $this->get_host_object();

		require_once $this->get_vendor_path('/php-oauth-api/httpclient/http.php', true);
		require_once $this->get_vendor_path('/php-oauth-api/oauth-api/oauth_client.php', true);

		$client = new oauth_client_class;
		$client->session_started = true;
		$client->server = 'Microsoft';
		$client->redirect_uri = $this->get_callback_url();
		$client->client_id = $host->windowslive_app_id;
		$client->client_secret = $host->windowslive_secret;
		$client->scope = 'wl.basic wl.emails';

		return $client;
	}

	public function get_login_url()
	{
		return $this->get_callback_url();
	}

	public function login()
	{
		$_GET = Phpr::$request->get_fields;

		$client = $this->get_client();

		if (($success = $client->Initialize()))
		{
			if (($success = $client->Process()))
			{
				if (strlen($client->authorization_error))
				{
					$client->error = $client->authorization_error;
					$success = false;
				}
				else if (strlen($client->access_token))
				{
					$success = $client->CallAPI(
						'https://apis.live.net/v5.0/me',
						'GET', array(), array('FailOnAccessError'=>true), $user);
				}
			}
			$success = $client->Finalize($success);
		}
		
		if ($client->exit)
			throw new Exception('Client ended session');

		if (!isset($user))
			throw new Exception('Client did not return a valid user, check your API credentials');

		$response = array();

		// Move into User fields where possible
		$response['token'] = $user->id;
		
		if (isset($user->emails->account)) 
			$response['email'] = filter_var($user->emails->account, FILTER_SANITIZE_EMAIL);
		
		if (isset($user->emails->personal)) 
			$response['email'] = filter_var($user->emails->personal, FILTER_SANITIZE_EMAIL);
		
		if (isset($user->emails->business)) 
			$response['email'] = filter_var($user->emails->business, FILTER_SANITIZE_EMAIL);
		
		if (isset($user->emails->preferred)) 
			$response['email'] = filter_var($user->emails->preferred, FILTER_SANITIZE_EMAIL);
		
		if (isset($user->first_name)) 
			$response['first_name'] = $user->first_name;
		
		if (isset($user->last_name)) 
			$response['last_name'] = $user->last_name;

		return $response;
	}
}