<?php

class Social_Provider extends Db_ActiveRecord
{
    public $table_name = 'social_providers';
    public $implement = 'Db_Model_Dynamic';

    public $is_enabled = 1;

    protected $provider_obj = null;
    protected $added_fields = array();
    protected $hidden_fields = array();
    protected $form_context = null;

    public $custom_columns = array('provider_name' => db_text);
    public $encrypted_columns = array('config_data');

    public $fetched_data = array();

    protected $form_fields_defined = false;
    protected static $cache = array();
    private static $providers = null;

    public static function create()
    {
        return new self();
    }

    public function define_columns($context = null)
    {
        $this->define_column('provider_name', 'Provider');
        $this->define_column('is_enabled', 'Enabled')->order('desc');
        // $this->define_column('code', 'API Code')->defaultInvisible()->validation()->fn('trim')->fn('mb_strtolower')->unique('A payment gateway with the specified API code already exists');
    }

    public function define_form_fields($context = null)
    {
        if ($this->form_fields_defined)
            return false;

        $this->form_fields_defined = true;

        $this->add_form_field('is_enabled');

        $obj = $this->get_provider_object();
        $method_info = $obj->get_info();

        $this->get_provider_object()->build_config_ui($this, $context);
        if (!$this->is_new_record())
            $this->load_dynamic_data();
        else
            $this->get_provider_object()->init_config_data($this);

        // $this->add_form_field('code', 'full')->comment('A unique code used to reference this provider by other modules. Leave blank unless instructed.');
    }

    // Custom columns
    //

    public function eval_provider_name()
    {
        $obj = $this->get_provider_object();
        $info = $obj->get_info();
        if (array_key_exists('name', $info))
            return $info['name'];

        return null;
    }

    // Getters
    // 

    public function get_provider_object()
    {
        if ($this->provider_obj !== null)
            return $this->provider_obj;

        if (!Phpr::$class_loader->load($this->class_name))
            throw new Phpr_ApplicationException("Class ".$this->class_name." not found");

        $class_name = $this->class_name;

        return $this->provider_obj = new $class_name();
    }

    // Dynamic model
    // 

    public function add_field($code, $title, $side = 'full', $type = db_text)
    {
        $form_field = $this->add_dynamic_field($code, $title, $side, $type);
        $this->added_fields[$code] = $form_field;
        return $form_field;
    }

}