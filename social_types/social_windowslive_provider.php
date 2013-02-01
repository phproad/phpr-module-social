<?php

class Social_WindowsLive_Provider extends Social_Provider_Base
{
	public function get_info()
	{
		return array(
			'id' => 'windowslive',
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
		$host->add_field('windowslive_app_id', 'Client ID', 'full', db_text)->renderAs(frm_text);
		$host->add_field('windowslive_secret', 'Client Secret', 'full', db_text)->renderAs(frm_text);
	}

	public function get_client()
	{
		$host = $this->get_host_object();

		require_once  dirname(__FILE__).'/php-oauth-api/httpclient-2012-10-05/http.php';
		require_once  dirname(__FILE__).'/php-oauth-api/oauth-api-2012-11-19/oauth_client.php';
		

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
			die();

		$response = array();

		// Move into User fields where possible
		$response['token'] = $user->id;
		if (!empty($user->emails->account)) $response['email'] = filter_var($user->emails->account, FILTER_SANITIZE_EMAIL);
		if (!empty($user->emails->personal)) $response['email'] = filter_var($user->emails->personal, FILTER_SANITIZE_EMAIL);
		if (!empty($user->emails->business)) $response['email'] = filter_var($user->emails->business, FILTER_SANITIZE_EMAIL);
		if (!empty($user->emails->preferred)) $response['email'] = filter_var($user->emails->preferred, FILTER_SANITIZE_EMAIL);
		if (!empty($user->first_name)) $response['first_name'] = $user->first_name;
		if (!empty($user->last_name)) $response['last_name'] = $user->last_name;

		return $response;
	}
}