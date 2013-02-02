<?php

class Social_Provider_User extends Db_ActiveRecord
{
    public $table_name = 'social_provider_users';

    public $belongs_to = array(
        'user' => array('class_name'=>'User', 'foreign_key'=>'user_id')
    );

    public static function create()
    {
        return new self();
    }

    public function define_columns($context = null)
    {
        $this->define_column('provider_id', 'Login Provider', db_varchar)->order('asc')->validation()->fn('trim');
        $this->define_column('provider_token', 'Login Provider Token', db_varchar)->validation()->fn('trim');
    }

    public function before_create($session_key = null)
    {
        // Prevent duplication
        Db_Helper::query("delete from social_provider_users where user_id=:user_id and provider_id=:provider_id", array(
            'user_id' => $this->user_id,
            'provider_id' => $this->provider_id
        ));
    }

    public static function set_orders($item_ids, $item_orders)
    {
        if (is_string($item_ids))
            $item_ids = explode(',', $item_ids);

        if (is_string($item_orders))
            $item_orders = explode(',', $item_orders);

        foreach ($item_ids as $index=>$id)
        {
            $order = $item_orders[$index];
            Db_Helper::query('update social_provider_users set sort_order=:sort_order where id=:id', array(
                'sort_order'=>$order,
                'id'=>$id
            ));
        }
    }
}

