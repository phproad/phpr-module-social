<?php

class Social_Provider_Manager
{
    public static $object_cache = null;
    public static $provider_cache = null;
    public static $active_providers = array();

    // Class/Object handling
    // 

    /**
     * Returns a list of providers. Providers must be in a module
     * /provider folder and must follow the naming convention
     * <module_name>_<provider_id>_provider.php
     * @return array of provider class names
     */
    public static function find_providers()
    {
        if (self::$object_cache !== null)
            return self::$object_cache;

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
        $provider_objects = array();
        foreach ($classes as $class)
        {
            if (!preg_match('/_Provider$/i', $class) || get_parent_class($class) != 'Social_Provider_Base')
                continue;

            $provider_objects[] = $class;
        }

        return self::$object_cache = $provider_objects;
    }

    public static function find_active_providers($order = array())
    {
        $providers = self::get_active_providers($order);

        $provider_objects = array();
        foreach ($providers as $provider)
        {
            $provider_objects[] = $provider->get_provider_object();
        }

        return $provider_objects;
    }

    /**
     * Finds a given provider
     * @param $id the id of the provider declared in get_info
     * @param $only_enabled discard non-enabled providers from the search
     * @return provider object on success, null on failure
     */
    public static function find_provider($code)
    {
        $providers = self::find_providers();
        foreach ($providers as $provider)
        {
            if ($provider->get_id() == $code)
                return $provider;
        }
        return null;
    }

    // Model handling
    // 

    public static function get_providers()
    {
        if (!self::$provider_cache)
            return self::$provider_cache = Social_Provider::create()->find_all();

        return self::$provider_cache;
    }

    public static function get_provider($code)
    {
        $providers = self::find_providers();
        foreach ($providers as $provider)
        {
            if ($provider->code == $code)
                return $provider;
        }
        return null;
    }

    /**
     * Returns a list of active Provider objects
     * @param (optional) array $order - array of provider_ids
     * @return array of provider objects
     */
    public static function get_active_providers($order = array())
    {
        if (!self::$active_providers)
        {
            $active_providers = array();
            $providers = self::get_providers();
            
            foreach ($providers as $provider)
            {
                if ($provider->is_enabled)
                    $active_providers[$provider->code] = $provider;
            }

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
}