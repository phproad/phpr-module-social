<?php

class Social_Dropbox_Provider extends Social_Provider_Base
{
	public function get_info()
	{
		return array(
			'code' => 'dropbox',
			'name'=>'Dropbox',
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
		$host->add_field('dropbox_app_id', 'App Key', 'full', db_text)->display_as(frm_text);
		$host->add_field('dropbox_secret', 'App Secret', 'full', db_text)->display_as(frm_text);
	}

	public function get_client()
	{
		$host = $this->get_host_object();

		require_once $this->get_vendor_path('/php-oauth-api/httpclient/http.php', true);
		require_once $this->get_vendor_path('/php-oauth-api/oauth-api/oauth_client.php', true);

		$client = new oauth_client_class;
		$client->session_started = true;
		$client->server = 'Dropbox';
		$client->redirect_uri = $this->get_callback_url();
		$client->client_id = $host->dropbox_app_id;
		$client->client_secret = $host->dropbox_secret;
		
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
						'https://api.dropbox.com/1/account/info',
						'GET', 
						array(), 
						array('FailOnAccessError'=>true), 
						$user
					);
				}
			}
			$success = $client->Finalize($success);
		}

		if ($client->exit)
			throw new Exception('Client ended session');

		if (!isset($user))
			throw new Exception('Client did not return a valid user, check your API credentials');

		$display_name = array('');
		if (!empty($user->display_name))
			$display_name = explode(' ', $user->display_name, 2);

		if (sizeof($display_name) != 2) 
			$display_name[] = '';

		$response = array();

		// Move into User fields where possible
		$response['token'] = $user->uid;
		if (!empty($user->email)) 
			$response['email'] = filter_var($user->email, FILTER_SANITIZE_EMAIL);
		
		$response['first_name'] = $display_name[0];
		$response['last_name'] = $display_name[1];
		return $response;
	}
}