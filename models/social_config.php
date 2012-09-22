<?php

class Social_Config extends Core_Settings_Model
{
    public $record_code = 'social_config';

    const mode_smtp = 'smtp';
    const mode_sendmail = 'sendmail';
    const mode_mail = 'mail';    

    public static function create()
    {
        $config = new self();
        return $config->load();
    }   
    
    protected function build_form()
    {
        $this->add_field('facebook_app_id', 'App ID', 'full', db_varchar)->tab('Facebook')
            ->comment('Enter your Facebook app ID for this site. To get an app ID visit <a href="http://developers.facebook.com/setup" target="_blank">Facebook\'s Setup Page</a> and request one.', 'below', true);
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