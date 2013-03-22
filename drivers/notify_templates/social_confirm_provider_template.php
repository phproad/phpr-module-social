<?php

class Social_Confirm_Provider_Template extends Notify_Template_Base
{
	public $required_params = array('user', 'provider');

	public function get_info()
	{
		return array(
			'name'=> 'Confirm User Provider Linkage',
			'description' => 'An email confirmation sent when a customer associats a login provider such as Twitter with their existing account.',
			'code' => 'social:confirm_provider'
		);
	}

	public function get_subject()
	{
		return 'Confirm attachment of new login provider';
	}

	public function get_content()
	{
		return file_get_contents($this->get_partial_path('content.htm'));
	}

	public function prepare_template($template, $params=array())
	{
		extract($params);

		$user->set_notify_vars($template, 'user_');
		$template->set_vars(array());

		$template->add_recipient($user);
	}
}
