<?php

class Social_Module extends Core_Module_Base
{
	protected function set_module_info()
	{
		return new Core_Module_Detail(
			"Social",
			"Social functions",
			"PHPRoad",
			"http://phproad.com/"
		);
	}

	public function build_admin_settings($settings)
	{
		$settings->add('/social/providers', 'Social Providers', 'Set up social network integration', '/modules/social/assets/images/social_config.png', 300);
	}

	public function subscribe_events()
	{
		Phpr::$events->add_event('user:on_extend_user_model', $this, 'extend_user_model');
	}

	public function extend_user_model($model)
	{
		$model->add_relation('has_many', 'social_providers', array(
			'class_name' => 'Social_Provider_User',
			'foreign_key' => 'user_id',
			'order' => 'id',
			'delete' => true
		));
		
		$model->define_multi_relation_column('social_providers', 'social_providers', 'Login Providers', '@provider_code')->default_invisible();
	}

	public function subscribe_access_points($action = null)
	{
		$manager = new Social_Manager();
		return array(
			'api_social_provider_callback' => array($manager, 'api_callback'),
			'api_social_provider_login' => array($manager, 'api_login'),
			'api_social_provider_associate' => array($manager, 'api_associate'),
		);
	}
}
