<?php

class Social_LinkedIn_Provider extends Social_Provider_Base
{
	public function get_info()
	{
		return array(
			'code' => 'linkedin',
			'name'=>'LinkedIn',
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
		$host->add_field('linkedin_app_id', 'API Key', 'full', db_text)->display_as(frm_text);
		$host->add_field('linkedin_secret', 'Secret Key', 'full', db_text)->display_as(frm_text);
	}

	public function get_client()
	{
		$host = $this->get_host_object();

		require_once $this->get_vendor_path('/php-oauth-api/httpclient/http.php', true);
		require_once $this->get_vendor_path('/php-oauth-api/oauth-api/oauth_client.php', true);

		$client = new oauth_client_class();
		$client->session_started = true;
		$client->server = 'LinkedIn';
		$client->redirect_uri = $this->get_callback_url();
		$client->client_id = $host->linkedin_app_id;
		$client->client_secret = $host->linkedin_secret;

		// API permission scopes
		// Separate scopes with a space, not with +
		$client->scope = 'r_fullprofile r_emailaddress';

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
				if (strlen($client->access_token))
				{
					$success = $client->CallAPI(
						'http://api.linkedin.com/v1/people/~:(id,first-name,last-name,email-address,main-address,phone-numbers,positions)',
						'GET', array(
							'format'=>'json'
						), array('FailOnAccessError'=>true), $user);
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
		$response['email'] = filter_var($user->emailAddress, FILTER_SANITIZE_EMAIL);
		$response['first_name'] = $user->firstName;
		$response['last_name'] = $user->lastName;

		return $response;
	}
}