<?

/**
 * Represents the generic payment type.
 * All other payment types must be derived from this class
 */

class Social_Provider_Base extends Phpr_Extension
{
	public static $driver_folder = 'social_providers';
	public static $driver_suffix = '_provider';

	public $config;
	public $error = array(
		'debug' => 'No error given.',
		'customer' => 'An unknown error occurred. Please try again shortly.',
	);

	// Returns information about the provider
	public function get_info()
	{
		return array(
			'code' => 'unknown',
			'name' => 'Unknown'
		);
	}

	/*
	 * Handles login for the provider callback URL
	 * @return array($user_details) on success, false on failure
	 * $user_details should contain a field called 'token'
	 */
	public function login() { }

	/**
	 * Returns the URL used to log in with this provider
	 * @return string $url
	 */
	public function get_login_url() { }

	public function get_name() 
	{
		$info = $this->get_info();
		return (isset($info['name'])) ? $info['name'] : false;
	}

	public function get_code() 
	{
		$info = $this->get_info();
		return (isset($info['code'])) ? $info['code'] : false;
	}

	/**
	 * Builds the payment type administration user interface
	 * For drop-down and radio fields you should also add methods returning
	 * options. For example, of you want to have Sizes drop-down:
	 * public function get_sizes_options();
	 * This method should return array with keys corresponding your option identifiers
	 * and values corresponding its titles.
	 *
	 * @param $host ActiveRecord object to add fields to
	 * @param string $context Form context. In preview mode its value is 'preview'
	 */
	public function build_config_ui($host, $context = null) { }

	/**
	 * Initializes configuration data when the social provider is first created
	 * Use host object to access and set fields previously added with build_config_ui method.
	 * @param $host ActiveRecord object containing configuration fields values
	 */
	public function init_config_data($host) { }

	/**
	 * Returns a cached copy of the SocialLogin configuration model
	 */
	public function get_host_object()
	{
		return Social_Provider::get_provider($this->get_code());
	}

	/**
	 * Perform an action after a $user has been registered and signed in
	 */
	public function after_registration($user) { }

	/**
	 * The URL on our site that OAuth requests will respond to with login details
	 * @param $provider_code
	 */
	public function get_callback_url()
	{
		return root_url('api_social_provider_callback/'.$this->get_code(), true);
	}

	public function set_error(array $messages)
	{
		$this->error = $messages;
		return false;
	}

	public function get_error()
	{
		return $this->error;
	}

	public function get_partial_path($partial_name = null)
	{
		$class_name = get_class($this);
		$class_path = File_Path::get_path_to_class($class_name);
		return $class_path.'/'.strtolower($class_name).'/partials/'.$partial_name;
	}

	/**
	 * Returns full relative path to a resource file situated in the provider's resources directory.
	 * @param string $path Specifies the relative resource file name, for example '/assets/javascript/widget.js'
	 * @return string Returns full relative path, suitable for passing to the controller's add_css() or add_javascript() method.
	 */
	public function get_vendor_path($path, $use_global = false)
	{
		if (substr($path, 0, 1) != '/')
			$path = '/'.$path;
	 
		if ($use_global)       
			return PATH_APP.'/'.PHPR_MODULES.'/social/vendor'.$path;
		
		$class_name = get_class($this);
		$class_path = File_Path::get_path_to_class($class_name);
		return $class_path.'/'.strtolower($class_name).'/vendor'.$path;
	}

}
