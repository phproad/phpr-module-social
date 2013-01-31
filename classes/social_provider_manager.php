<?php

class Social_Provider_Manager
{
    public static $providers = null;
    public static $active_providers = array();

    /**
     * Returns a list of providers. Providers must be in a module
     * /provider folder and must follow the naming convention
     * <module_name>_<provider_id>_provider.php
     * @return array of provider class names
     */
    public static function list_providers()
    {
        if (self::$providers !== null)
            return self::$providers;

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
        self::$providers = array();
        foreach ($classes as $class)
        {
            if (!preg_match('/_Provider$/i', $class) || get_parent_class($class) != 'Social_Provider_Base')
                continue;

            self::$providers[] = $class;
        }

        return self::$providers;
    }

    /**
     * Finds a given provider
     * @param $id the id of the provider declared in get_info
     * @param $only_enabled discard non-enabled providers from the search
     * @return provider object on success, null on failure
     */
    public static function get_provider($id, $only_enabled=true)
    {
        if (empty($id))
            return null;

        $providers = Social_Provider_Manager::list_providers();
        foreach ($providers as $class_name)
        {
            $provider = new $class_name();
            $info = $provider->get_info();
            if ($info['id'] == $id)
            {
                if ($only_enabled && !$provider->is_enabled())
                    return null;
                    // return $this->handle_error(array(
                    //  'debug' => "Provider '$id' is not found or enabled.",
                    //  'customer' => "We were unable to determine who you were trying to log in with."
                    // ));

                return $provider;
            }
        }

        return null;
    }

    /**
     * Returns a list of active Provider objects
     * @param (optional) array $order - array of provider_ids
     * @return array of provider objects
     */
    public static function get_active_providers($order=array())
    {
        if (!self::$active_providers)
        {
            $active_providers = array();
            $providers = self::list_providers();
            foreach ($providers as $class_name)
            {
                $obj = new $class_name();
                if ($obj->is_enabled())
                {
                    $obj->info = $obj->get_info();
                    $active_providers[] = $obj;
                }
            }

            // Cache the provider obj list
            self::$active_providers = $active_providers;
        }

        if (self::$active_providers)
        {
            return $order 
                ? self::sort_active_providers(self::$active_providers, $order) 
                : self::$active_providers;
        }
    }

    /**
     * Returns a list of Provider objects in a given sort order. If any
     * providers don't appear in the order list they'll appear unsorted at
     * the end
     * @param array $providers - list of provider objects
     * @param array $order - array of provider ids
     * @return array of provider objects
     */
    public static function sort_active_providers($providers, $order)
    {
        $new_order = array();

        foreach ($order as $provider_id)
        {
           foreach ($providers as $key=>$provider)
           {
                if ($provider->info['id'] == $provider_id)
                {
                    $new_order[] = $provider;
                    unset($providers[$key]);
                }
           }
        }

        if (sizeof($providers))
            $new_order = array_merge($new_order, $providers);

        return $new_order;
    }
}