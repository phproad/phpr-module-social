<?

/**
 * Represents the generic payment type.
 * All other payment types must be derived from this class
 */

class Social_Provider_Base extends Phpr_Extension_Base
{
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
        return (isset($info['name'])) ? $info['name'] : 'Unknown';
    }

    public function get_code() 
    {
        $info = $this->get_info();
        return (isset($info['code'])) ? $info['code'] : 'unknown';
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
     * @param $provider_id
     */
    public function get_callback_url()
    {
        return root_url('/api_social_provider_callback/?hauth.done='.$this->get_code(), true);
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
