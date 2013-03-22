<?php

class Social_Google_Provider extends Social_Provider_Base
{
	public function get_info()
	{
		return array(
			'code' => 'google',
			'name'=>'Google',
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
		$host->add_field('google_app_id', 'Client ID', 'full', db_text)->display_as(frm_text);
		$host->add_field('google_secret', 'Client Secret', 'full', db_text)->display_as(frm_text);
	}

	public function get_client()
	{
		$host = $this->get_host_object();
		
		require_once dirname(__FILE__).'/social_google_provider/vendor/google-api-php-client/Google_Client.php';
		require_once dirname(__FILE__).'/social_google_provider/vendor/google-api-php-client/contrib/Google_Oauth2Service.php';

		$client = new Google_Client();
		$client->setApplicationName('PHPR Social Login');
		$client->setApprovalPrompt('auto');
		
		//$client->setAccessType('online');
		// Visit https://code.google.com/apis/console?api=plus to generate your
		// oauth2_client_id, oauth2_client_secret, and to register your oauth2_redirect_uri.

		$client->setClientId($host->google_app_id);
		$client->setClientSecret($host->google_secret);
		$client->setRedirectUri($this->get_callback_url());
		// $client->setDeveloperKey('insert_your_developer_key');

		return $client;
	}

	public function get_login_url()
	{
		$client = $this->get_client();
		$oauth2 = new Google_Oauth2Service($client);

		return $client->createAuthUrl();
	}

	public function login()
	{
		$code = Phpr::$request->get_field('code', '');
		if ( empty($code) )
			return $this->set_error(array(
				'debug' => "An error occurred. 'code' GET variable not found.",
				'customer' => "An error occurred communicating with the authentication server. Could not log you in.",
			));

		$client = $this->get_client();
		$oauth2 = new Google_Oauth2Service($client);

		try 
		{
			$client->authenticate($code);
		} 
		catch (Exception $e) 
		{
			return $this->set_error(array(
				'debug' => 'Error. Provider responded with: ' . $e->getMessage(),
				'customer' => $e->getMessage(),
			));
		}

		$user = $oauth2->userinfo->get();
		$response = array();

		// Move into User fields where possible
		$response['token'] = $user['id'];
		if ( !empty($user['email']) ) $response['email'] = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
		if ( !empty($user['given_name']) ) $response['first_name'] = $user['given_name'];
		if ( !empty($user['family_name']) ) $response['last_name'] = $user['family_name'];

		return $response;
	}
}