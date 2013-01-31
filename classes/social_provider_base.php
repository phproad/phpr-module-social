<?

/**
 * Represents the generic payment type.
 * All other payment types must be derived from this class
 */

class Social_Provider_Base
{
    public $config;
    public $error = array(
        'debug' => 'No error given.',
        'customer' => 'An unknown error occurred. Please try again shortly.',
    );


    // Returns information about the event
    public function get_info()
    {
        return array(
            'name' => 'unknown',
            'description' => 'Unknown Provider'
        );
    }

    public function is_enabled()
    {
        return false;
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
    public function build_config_ui($host)
    {
    }

    /**
     * Initializes configuration data when the social provider is first created
     * Use host object to access and set fields previously added with build_config_ui method.
     * @param $host ActiveRecord object containing configuration fields values
     */
    public function init_config_data($host)
    {
    }

    /**
     * Returns a cached copy of the SocialLogin configuration model
     */
    public function get_config()
    {
        if (!$this->config)
            $this->config = Social_Config::create();

        return $this->config;
    }

    /**
     * Perform an action after a $user has been registered and signed in
     */
    public function after_registration($user)
    {

    }

    /**
     * The URL on our site that OAuth requests will respond to with login details
     * @param $provider_id
     */
    public function get_callback_url()
    {
        $info = $this->get_info();
        return root_url('/social_provider_callback/?hauth.done='.$info['id'], true);
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
        $class_path = File_Path::find_path_to_class($class_name);
        return $class_path.'/'.strtolower($class_name).'/partials/'.$partial_name;
    }
}