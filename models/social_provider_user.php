<?php

class Social_Provider_User extends Db_ActiveRecord
{
	public $table_name = 'social_provider_users';

	public $implement = 'Db_Model_Sortable';

	public $belongs_to = array(
		'user' => array('class_name'=>'User', 'foreign_key'=>'user_id')
	);

	public function define_columns($context = null)
	{
		$this->define_column('provider_code', 'Login Provider', db_varchar)->order('asc')->validation()->fn('trim');
		$this->define_column('provider_token', 'Login Provider Token', db_varchar)->validation()->fn('trim');
	}

	public function before_create($session_key = null)
	{
		// Prevent duplication
		Db_Helper::query("delete from social_provider_users where user_id=:user_id and provider_code=:provider_code", array(
			'user_id' => $this->user_id,
			'provider_code' => $this->provider_code
		));
	}
}

