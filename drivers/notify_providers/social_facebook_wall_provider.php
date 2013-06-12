<?php

class Social_Facebook_Wall_Provider extends Notify_Provider_Base
{

	protected static $facebook_permissions = 'publish_stream,manage_pages';

	private static $_facebook_client = null;

	/**
	 * Returns information about the provider.
	 * @return array Returns array with two keys: name and description
	 * array('name' => 'SMS Matrix', 'code' => 'smsmatrix', 'description' => 'SMS Matrix provider: http://www.smsmatrix.com')
	 */
	public function get_info()
	{
		if (!Social_Provider::get_active_provider('facebook'))
			return false;

		return array(
			'name' => 'Facebook User Wall',
			'code' => 'facebook_user_wall',
			'description' => 'Posts to a users Facebook Wall (if linked to Facebook)'
		);
	}
	
	/**
	 * Builds the provider configuration user interface.
	 * @param $host ActiveRecord object to add fields to
	 */
	public function build_config_ui($host, $context = null)
	{
		$host->add_form_section('This provider has no options.')->tab('General');
	}
	
	/**
	 * Validates configuration data before it is saved to database
	 * Use host object field_error method to report about errors in data:
	 * $host->field_error('max_weight', 'Max weight should not be less than Min weight');
	 * @param $host ActiveRecord object containing configuration fields values
	 */
	public function validate_config_on_save($host)
	{
	}
	
	/**
	 * Initializes configuration data when the provider object is created for the first time
	 * Use host object to access and set fields previously added with build_config_ui method.
	 * @param $host ActiveRecord object containing configuration fields values
	 */
	public function init_config_data($host)
	{
	}

	// Template UI
	// 

	public function build_template_ui($host, $context = null)
	{
		$host->add_field('facebook_user_wall_link', 'User Wall Link', 'full', db_varchar)->tab('Facebook Wall');
		$host->add_field('facebook_user_wall_subject', 'User Wall Subject', 'full', db_varchar)->tab('Facebook Wall');
		$host->add_field('facebook_user_wall_message', 'User Wall Message', 'full', db_text)->tab('Facebook Wall');
	}

	public function init_template_data($host)
	{
		if (!$host->init_template_extension())
			return;

		if (!strlen($host->facebook_user_wall_subject)) 
			$host->facebook_user_wall_subject = $host->get_external_subject();

		if (!strlen($host->facebook_user_wall_message))
			$host->facebook_user_wall_message = $host->get_external_content();
		
		if (!strlen($host->facebook_user_wall_link))
			$host->facebook_user_wall_link = root_url('', true);
	}


	// Sending
	// 

	public function send_notification($template) 
	{
		if ($template->facebook_user_wall_message || $template->facebook_user_wall_subject) {
			$this->send_message(
				$template->facebook_user_wall_message, 
				$template->facebook_user_wall_subject, 
				$template->facebook_user_wall_link
			);
		}

		return true;
	}

	public function send_test_message($recipient) 
	{
		$message = 'This is a test notification from '.c('site_name').'.';
		$this->send_message($message);
		return true;
	}

	/**
	 * Sends SMS message to a specific recipient(s).
	 * @param array $recipients An array of recipients phone numbers.
	 * @param string $message Message text
	 * @return array Returns an array of identifiers assigned to the messages by the SMS provider.
	 */
	public function send_message($message, $subject=null, $link=null)
	{
		$host = $this->get_host_object();
		if (!$host)
			throw new Exception("The send_message() method must be called from a host object");

		$message = array(
			'message' => $message,
			'subject' => $subject,
			'link' => $link
		);

		try {
			$facebook = $this->get_facebook_client();
			$fb_user_id = $facebook->getUser();
			$fb_user_profile = $facebook->api('/me');

			$uri = '/me/feed';
			$facebook->api($uri, 'post', $message);
			
		} catch (FacebookApiException $ex) {
			throw new Phpr_ApplicationException('Error posting to Facebook: '.$ex->getMessage());
		}
	}

	private function get_facebook_client() 
	{
		if (self::$_facebook_client)
			return self::$_facebook_client;

		$social_provider = Social_Provider::get_active_provider('facebook');
		$client = $social_provider->get_client();
		return self::$_facebook_client = $client;		
	}
	
}

