<?php

class Social_Twitter_Provider extends Social_Provider_Base
{
	public function get_info()
	{
		return array(
			'code' => 'twitter',
			'name'=>'Twitter',
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
		$host->add_field('twitter_app_id', 'Consumer Key', 'full', db_text)->display_as(frm_text);
		$host->add_field('twitter_secret', 'Consumer Secret', 'full', db_text)->display_as(frm_text);
		$host->add_field('registration_redirect', 'Page to redirect to on registration', 'full', db_text)->display_as(frm_dropdown)->empty_option('<please select>')
			->comment("Twitter doesn't provide an email address or name so we need to redirect to a page where the user provides this information.");
	}

	public function get_registration_redirect_options($key_value = -1)
	{
		return Cms_Page::create()->get_name_list();
	}

	public function get_login_url()
	{
		return root_url('api_social_provider_login/?provider='.$this->get_code());
	}

	public function send_login_request()
	{
		$host = $this->get_host_object();

		require_once $this->get_vendor_path('/twitteroauth/twitteroauth.php');

		// Build TwitterOAuth object with client credentials. 
		$client = new TwitterOAuth(
			$host->twitter_app_id, 
			$host->twitter_secret
		);

		// Get temporary credentials. 
		try 
		{
			$request_token = $client->getRequestToken($this->get_callback_url());
		} 
		catch (Exception $ex)
		{
			return $this->set_error(array(
				'debug' => "Failed to retrieve Twitter request token. Check your API credentials",
				'customer' => "Could not connect to Twitter. Refresh the page or try again later."
			));
		}

		Phpr::$session->set('oauth_token', $request_token['oauth_token']);
		Phpr::$session->set('oauth_token_secret', $request_token['oauth_token_secret']);

		switch ($client->http_code) 
		{
			case 200:
				// Build authorize URL and redirect user to Twitter. 
				$url = $client->getAuthorizeURL(Phpr::$session->get('oauth_token'));
				header('Location: ' . $url);
				die();
				break;
			
			default:
				return $this->set_error(array(
					'debug' => "Failed to retrieve Twitter request token. HTTP response " . $client->http_code,
					'customer' => "Could not connect to Twitter. Refresh the page or try again later."
				));
				break;
		}
	}

	public function login()
	{ 
		// If the oauth_token is old redirect to the connect page. 
		if (isset($_REQUEST['oauth_token']) && Phpr::$session->get('oauth_token') !== $_REQUEST['oauth_token'])
		{
			return $this->set_error(array(
				'debug' => "login(): oauth_token is old.",
				'customer' => "Your login session has expired. Please try again."
			));
		}

		$host = $this->get_host_object();

		require_once($this->get_vendor_path('/twitteroauth/twitteroauth.php'));

		// Create TwitteroAuth object with app key/secret and token key/secret from default phase 
		$connection = new TwitterOAuth(
			$host->twitter_app_id, 
			$host->twitter_secret, 
			Phpr::$session->get('oauth_token', ''), 
			Phpr::$session->get('oauth_token_secret', '')
		);

		// Request access tokens from twitter 
		$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

		// $access_token will look like this:
		// array(4) [
		// 	oauth_token => '#########-zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz',
		// 	oauth_token_secret => 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz',
		// 	user_id => '#########',
		// 	screen_name => 'scriptsahoy'
		// ]
		

		// Save the access tokens. Normally these would be saved in a database for future use.
		// Phpr::$session->set('access_token', $access_token);

		// Remove no longer needed request tokens
		Phpr::$session->remove('oauth_token');
		Phpr::$session->remove('oauth_token_secret');

		// If HTTP response is 200 continue otherwise send to connect page to retry
		if ($connection->http_code != 200) {
			return $this->set_error(array(
				'debug' => "login(): HTTP response code was ".$connection->http_code." when trying to get access token.",
				'customer' => "",
			));
		}

		$screen_name = explode(' ', $access_token['screen_name'], 2);
		$first_name = reset($screen_name);
		$last_name = sizeof($screen_name) > 1 ? end($screen_name) : '';

		// Use their twitter user id as the unique identifier so if they
		// revoke the token and relogin we won't create a duplicate user
		return array(
			'token' => $access_token['user_id'],
			'first_name' => $first_name,
			'last_name' => $last_name,
		);
	}

	public function after_registration($user)
	{
		$host = $this->get_host_object();

		if ($host->twitter_registration_redirect)
			Phpr::$response->redirect($host->twitter_registration_redirect);
	}
}