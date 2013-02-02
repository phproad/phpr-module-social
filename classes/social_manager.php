<?php

class Social_Manager
{
    private static $_object_cache = null;
    private static $_class_cache = null;
    
    // Class/Object handling
    // 

    /**
     * Returns a list of providers. Providers must be in a module
     * /provider folder and must follow the naming convention
     * <module_name>_<provider_id>_provider.php
     * @return array of provider class names
     */
    public static function get_provider_class_names()
    {
        if (self::$_class_cache !== null)
            return self::$_class_cache;

        $modules = Core_Module_Manager::find_modules();
        foreach ($modules as $id => $module_info)
        {
            $class_path = PATH_APP."/".PHPR_MODULES."/".$id."/social_types";
            
            if (!file_exists($class_path))
                continue;

            $iterator = new DirectoryIterator($class_path);

            foreach ($iterator as $file)
            {
                if (!$file->isDir() && preg_match('/^'.$id.'_[^\.]*\_provider.php$/i', $file->getFilename()))
                    require_once($class_path.'/'.$file->getFilename());
            }
        }

        $classes = get_declared_classes();
        $provider_classes = array();
        foreach ($classes as $class_name)
        {
            if (!preg_match('/_Provider$/i', $class_name) || get_parent_class($class_name) != 'Social_Provider_Base')
                continue;

            $provider_classes[] = $class_name;            
        }

        return self::$_class_cache = $provider_classes;
    }

    public static function get_providers()
    {
        if (self::$_object_cache !== null)
            return self::$_object_cache;

        $provider_objects = array();
        foreach (self::get_provider_class_names() as $class_name)
            $provider_objects[] = new $class_name();
        
        return self::$_object_cache = $provider_objects;
    }

    /**
     * Finds a given provider
     * @param $id the id of the provider declared in get_info
     * @param $only_enabled discard non-enabled providers from the search
     * @return provider object on success, null on failure
     */
    public static function get_provider($code)
    {
        $providers = self::get_providers();
        foreach ($providers as $provider)
        {
            if ($provider->get_code() == $code)
                return $provider;
        }
        return new Social_Provider_Base();
    }

    /**
     * Returns a list of Provider objects in a given sort order. If any
     * providers don't appear in the order list they'll appear unsorted at
     * the end
     * @param array $providers - list of provider objects
     * @param array $order - array of provider ids
     * @return array of provider objects
     */
    private static function sort_active_providers($providers, $order)
    {
        $new_order = array();

        foreach ($order as $provider_id)
        {
           foreach ($providers as $key => $provider)
           {
                if ($provider->code == $provider_id)
                {
                    $new_order[] = $provider;
                    unset($providers[$key]);
                }
           }
        }

        if (count($providers))
            $new_order = array_merge($new_order, $providers);

        return $new_order;
    }

    // Access point handling
    // 

    /**
     * Handles login for a provider when it returns to our site with the relevant info
     * @return void
     */
    public function api_callback()
    {
        $config = FlynSocialLogin_Configuration::create();
        $provider_id = Phpr::$request->getField('hauth_done', '');
        $provider = Social_Provider::get_provider($provider_id);
        if (!$provider)
        {
            return $this->handle_error(array(
                'debug' => !strlen($provider_id) 
                    ? "provider_callable(): No hauth.done GET variable. Unable to determine provider"
                    : "provider_callable(): No provider of id '".$provider_id."' found or provider not enabled.",
                'user' => "We were unable to determine who you were trying to log in with."
            ));
        }

        try 
        {
            $user_data = $provider->login();
            if (!is_array($user_data))
                return $this->handle_error($provider->get_error());
        } 
        catch (Exception $e)
        {
            return $this->handle_error(array(
                'debug' => "provider_callable(): Provider '".$provider_id."' error: ".$e->getMessage(),
                'user' => $e->getMessage()
            ));
        }

        $user = $this->get_provider_user($provider, $user_data);
        
        // A user wasn't found or created which means we're forcing emails
        // So redirect to forced email page
        if (!$user)
        {
            $user_data['provider_id'] = $provider->get_code();
            Phpr::$session->set('social_user_data', $user_data);
            Phpr::$response->redirect($config->email_confirmation_page);
            return;
        }

        // Log the user in
        Phpr::$frontend_security->userLogin($user->id);
        Phpr::$response->redirect(root_url('/', true));
    }

    /**
     * Some providers require a special login page. Use a URL like:
     * /social_api_login/?provider=PROVIDER_ID
     */
    public function api_login()
    {
        $provider_id = Phpr::$request->getField('provider', '');
        $provider = Social_Provider::get_provider($provider_id);
        if ( !$provider )
            return $this->handle_error(array(
                'debug' => "api_login(): No provider of id '$provider_id' found or provider not enabled.",
                'user' => "We were unable to determine who you were trying to log in with."
            ));

        try {
            if ( !$provider->send_login_request() )
                return $this->handle_error($provider->get_error());
        } catch (Exception $e) {
            return $this->handle_error(array(
                'debug' => "api_login(): Provider '".$provider_id."' error: ".$e->getMessage(),
                'user' => $e->getMessage()
            ));
        }
    }


    /**
     * The confirmation URL a user clicks when associating a provider with
     * their LS account.
     * @return Session flash message and redirect to homepage
     */
    public function api_associate()
    {
        // Restore $_GET for WP permalinks
        $_GET = Phpr::$request->get_fields;

        $confirm = empty($_GET['confirm']) ? '' : $_GET['confirm'];
        if ( empty($confirm) )
        {
            Phpr::$session->flash['error'] = "Invalid confirmation URL.";
            Phpr::$response->redirect('/');
            return;
        }

        $user_provider_id = Db_DbHelper::object("
            SELECT *
            FROM social_provider_users
            WHERE is_enabled=0 AND CONCAT(id, user_id, provider_token)=:confirm
        ", array('confirm'=>$confirm));

        if ( !$user_provider_id )
        {
            Phpr::$session->flash['error'] = "Invalid confirmation code.";
            Phpr::$response->redirect('/');
            return;
        }

        Db_DbHelper::query("
            UPDATE social_provider_users
            SET is_enabled=1 WHERE is_enabled=0 AND CONCAT(id, user_id, provider_token)=:confirm
        ", array('confirm'=>$confirm));

        //Log the user in
        Phpr::$session->flash['success'] = 'Account successfully associated.';
        Phpr::$frontend_security->userLogin($user_provider_id->user_id);
        Phpr::$response->redirect(root_url('/', true));
    }


    /**
     * Creates a user for a given provider when an email is provided
     */
    public function get_provider_user( $provider, array $user_data )
    {
        $config = FlynSocialLogin_Configuration::create();
        $user = null;
        $insert_user_provider = true;

        //Try to find an existing user with matching provider and token
        $user_provider = Db_DbHelper::object("
            SELECT *
            FROM social_provider_users
            WHERE provider_id=:provider_id AND provider_token=:provider_token",
            array(
                'provider_id' => $provider->info['id'],
                'provider_token' => $user_data['token'],
            )
        );
        if ( $user_provider )
        {
            // Customer has already associated but hasn't responded to the activation email
            if ( !$user_provider->is_enabled )
                return false;

            $user = Shop_Customer::create()->find( $user_provider->user_id );
            // This account has a valid user_provider attached. No need to waste DB calls rebuilding it
            if ( $user )
                return $user;
        }

        //Try to find a user with this email if one was provided
        if ( !empty($user_data['email']) )
        {
            $user = Shop_Customer::create()->find_by_email($user_data['email']);
            if ( $user )
            {
                $this->set_provider_user($user, $user_data, $provider, true);
                return $user;
            }
        }

        //If no email given and we're forcing emails, dont create an empty user
        if ( empty($user_data['email']) && !empty($config->email_confirmation_page) )
            return false;

        $user = $this->create_new_user($user_data);

        $this->set_provider_user($user, $user_data, $provider, true);

        return $user;
    }

    public function create_new_user( array $user_data )
    {
        //Existing user not found, create one
        $user = new Shop_Customer();
        $user->disable_column_cache('front_end', false);
        $user->init_columns_info('front_end');
        $user->validation->focusPrefix = null;

        //If no email is provided, make the email field optional
        if ( empty($user_data['email']) )
            $user->validation->getRule('email')->optional();
        if ( empty($user_data['first_name']) )
            $user->validation->getRule('first_name')->optional();
        if ( empty($user_data['last_name']) )
            $user->validation->getRule('last_name')->optional();

        $user->generate_password();
        $shipping_params = Shop_ShippingParams::get();
        $user->shipping_country_id = $shipping_params->default_shipping_country_id;
        $user->shipping_state_id = $shipping_params->default_shipping_state_id;
        $user->shipping_zip = $shipping_params->default_shipping_zip;
        $user->shipping_city = $shipping_params->default_shipping_city;
        $user->save($user_data);
        $user->send_registration_confirmation();

        return $user;
    }

    public function set_provider_user( $user, $user_data, $provider, $is_enabled = true )
    {
        if ( !$user || !$provider )
            return false;

        //Save the login provider info. Make sure it's only in the DB once
        Db_DbHelper::query("
            DELETE FROM social_provider_users
            WHERE user_id=:user_id AND provider_id=:provider_id",
            array(
                'user_id' => $user->get_primary_key_value(),
                'provider_id' => $provider->info['id'],
            )
        );
        $user_provider = new FlynSocialLogin_Customer_Provider();
        $user_provider->save(array(
            'user_id' => $user->id,
            'provider_id' => $provider->info['id'],
            'provider_token' => $user_data['token'],
            'is_enabled' => $is_enabled ? 1 : 0,
        ));

        return $user_provider;
    }


    public function handle_error($messages)
    {
        if (self::$debug)
            die($messages['debug']);

        Phpr::$session->flash['error'] = $messages['user'];
        Phpr::$response->redirect(root_url("/"));
    }

}