<?php

class Social_Config extends Core_Settings_Base
{
	public $record_code = 'social_config';

	public static function create()
	{
		$config = new self();
		return $config->load();
	}   
	
	protected function build_form()
	{
		$this->add_field('allow_auth', 'Allow users to authenticate using Social Providers?', 'full', db_bool)->display_as(frm_checkbox);
	}

	protected function init_config_data()
	{
		$this->allow_auth = false;
	}

	public static function can_authenticate()
	{
		$obj = self::create();
		
		if (!$obj->allow_auth)
			return false;

		if (!count(Social_Provider::find_all_active_providers()))
			return false;

		return true;
	}

	public function is_configured()
	{
		$config = self::create();
		if (!$config)
			return false;

		return true;
	}
}