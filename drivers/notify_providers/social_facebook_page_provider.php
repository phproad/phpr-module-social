<?php

class Social_Facebook_Page_Provider extends Notify_Provider_Base
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
			'name' => 'Facebook Site Page',
			'code' => 'facebook_site_page',
			'description' => 'Posts to a site owned Facebook Page'
		);
	}
	
	/**
	 * Builds the provider configuration user interface.
	 * @param $host ActiveRecord object to add fields to
	 */
	public function build_config_ui($host, $context = null)
	{
		if (!$this->is_linked()) {
			$host->is_enabled = false;
			$host->find_form_field('is_enabled')->disabled();
			$host->add_form_section('Before you can proceed, you must <a href="'.$this->get_linkage_url().'">authorize this provider to communicate with Facebook</a>.', 'Facebook Integration Required')
				->is_html()->tab('General');
		} else {
			$host->add_field('facebook_page_id', 'Page to Post to', 'full', db_varchar)->display_as(frm_dropdown);
		}
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

	public function get_facebook_page_id_options($key_value = -1)
	{
		try {
			$facebook = $this->get_facebook_client();
			$fb_user_id = $facebook->getUser();
			$fql_query = 'SELECT page_id, name, page_url FROM page WHERE page_id IN (SELECT page_id FROM page_admin WHERE uid='.$fb_user_id.')';
			$post_results = $facebook->api(array('method' => 'fql.query', 'query' => $fql_query));
			$options = array();

			foreach ($post_results as $result) {
				$options[$result['page_id']] = $result['name'];
			}

			return $options;

		} catch (FacebookApiException $ex) {
			throw new Phpr_ApplicationException('Facebook API Error: '.$ex->getMessage());
		}
		return array();
	}

	// Template UI
	// 

	public function build_template_ui($host, $context = null)
	{
		$host->add_field('facebook_site_page_link', 'Site Page Link', 'full', db_varchar)->tab('Facebook Page');
		$host->add_field('facebook_site_page_subject', 'Site Page Subject', 'full', db_varchar)->tab('Facebook Page');
		$host->add_field('facebook_site_page_message', 'Site Page Message', 'full', db_text)->tab('Facebook Page');
	}

	public function init_template_data($host)
	{
		if (!$host->init_template_extension())
			return;

		if (!strlen($host->facebook_site_page_subject)) 
			$host->facebook_site_page_subject = $host->get_external_subject();

		if (!strlen($host->facebook_site_page_message))
			$host->facebook_site_page_message = $host->get_external_content();
		
		if (!strlen($host->facebook_site_page_link))
			$host->facebook_site_page_link = root_url('', true);
	}


	// Sending
	// 

	public function send_notification($template) 
	{
		if ($template->facebook_site_page_message || $template->facebook_site_page_subject) {
			$this->send_message(
				$template->facebook_site_page_message, 
				$template->facebook_site_page_subject, 
				$template->facebook_site_page_link
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

			$uri = '/'.$host->facebook_page_id.'/feed';
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

	// Is this provider linked to facebook?
	private function is_linked() 
	{
		$client = $this->get_facebook_client();
		$fb_user_id = $client->getUser();
		return $fb_user_id;
	}

	private function get_linkage_url()
	{
		$host = $this->get_host_object();
		$client = $this->get_facebook_client();

		$return_url = ($host->is_new_record()) 
			? url('notify/providers/create/'.get_class($this), true)
			: url('notify/providers/edit/'.$host->id, true);

		return $client->getLoginUrl(array(
			'redirect_uri' => $return_url,
			'scope' => array('perms' => self::$facebook_permissions))
		);
	}
	
}

