<?php

class Social_Config extends Core_Settings_Model
{
    public $record_code = 'social_config';

    public static function create()
    {
        $config = new self();
        return $config->load();
    }   
    
    protected function build_form()
    {
        $host->add_field('allow_login', 'Allow users to sign on using Social Providers?', 'full', db_bool)->renderAs(frm_checkbox);
    }

    protected function init_config_data()
    {

    }

    public function is_configured()
    {
        $config = self::create();
        if (!$config)
            return false;

        return true;
    }
}