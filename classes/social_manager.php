<?php

class Social_Manager
{
    private static $object_cache = null;
    private static $class_cache = null;
    
    private static $active_providers = array();

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
        if (self::$class_cache !== null)
            return self::$class_cache;

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

        return self::$class_cache = $provider_classes;
    }

    public static function get_providers()
    {
        if (self::$object_cache !== null)
            return self::$object_cache;

        $provider_objects = array();
        foreach (self::get_provider_class_names() as $class_name)
            $provider_objects[] = new $class_name();
        
        return self::$object_cache = $provider_objects;
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

}