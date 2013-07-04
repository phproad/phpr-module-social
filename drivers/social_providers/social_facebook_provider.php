<?php

class Social_Facebook_Provider extends Social_Provider_Base
{
	protected $_client = null;
	protected static $facebook_permissions = 'email,publish_stream';

	public function get_info()
	{
		return array(
			'code' => 'facebook',
			'name' => 'Facebook',
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
		$host->add_field('facebook_app_id', 'App ID', 'full', db_text)->display_as(frm_text);
		$host->add_field('facebook_secret', 'App Secret', 'full', db_text)->display_as(frm_text);
	}

	public function get_client()
	{
		if ($this->_client !== null)
			return $this->_client;

		$host = $this->get_host_object();

		require_once($this->get_vendor_path('/facebook-php-sdk/facebook.php'));

		// Fix for failed SSL verification
		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYHOST] = 2;

		$client = new Facebook(array(
			'appId'  => $host->facebook_app_id,
			'secret' => $host->facebook_secret
		));

		return $this->_client = $client;
	}

	public function get_login_url()
	{
		return $this->get_client()->getLoginUrl(array(
			'redirect_uri' => $this->get_callback_url(),
			'scope' => array('perms' => self::$facebook_permissions),
		));
	}

	public function login()
	{
		$client = $this->get_client();
		$user = $client->getUser();

		if (!$user) {
			return $this->set_error(array(
				'debug' => "login(): getUser() call failed to log us in.",
				'customer' => "An error occurred while attempting to log you in. Please try again later.",
			));
		}

		try 
		{
			// Proceed knowing you have a logged in user who's authenticated.
			$user_profile = $client->api('/me');
		} 
		catch (FacebookApiException $e) 
		{
			return $this->set_error(array(
				'debug' => "login(): Error grabbing user profile: ".$e->getMessage(),
				'customer' => "An error while attempting to log you in. Please try again.",
			));
		}

		$response = array();
		$response['token'] = $user_profile['id'];

		if (isset($user_profile['email'])) 
			$response['email'] = $user_profile['email'];

		if (isset($user_profile['first_name'])) 
			$response['first_name'] = $user_profile['first_name'];

		if (isset($user_profile['last_name'])) 
			$response['first_name'] = $user_profile['last_name'];

		return $response;
	}
}
