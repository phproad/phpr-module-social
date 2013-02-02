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

    private static $_provider_cache = null;
    private static $_active_providers = null;

    public $fetched_data = array();

    protected $form_fields_defined = false;
    protected static $cache = array();

    public static function create()
    {
        return new self();
    }

    public function define_columns($context = null)
    {
        $this->define_column('provider_name', 'Provider');
        $this->define_column('is_enabled', 'Enabled')->order('desc');
        $this->define_column('code', 'API Code')->defaultInvisible()->validation()->fn('trim')->fn('mb_strtolower')->unique('A payment gateway with the specified API code already exists');
    }

    public function define_form_fields($context = null)
    {
        // Prevent duplication
        if ($this->form_fields_defined) return false; 
        $this->form_fields_defined = true;

        // Mixin provider class
        if ($this->class_name && !$this->is_extended_with($this->class_name))
            $this->extend_with($this->class_name);

        $this->form_context = $context;

        // Build form
        $this->add_form_field('is_enabled');
        $this->build_config_ui($this, $context);
        $this->add_form_field('code', 'full')->disabled()
            ->comment('A unique code used to reference this provider by other modules.');

        // Load provider's default data
        if ($this->is_new_record())
            $this->init_config_data($this);
    }

    // Events
    // 

    public function after_fetch()
    {
        // Mixin provider class
        if ($this->class_name && !$this->is_extended_with($this->class_name))
            $this->extend_with($this->class_name);
    }

    // Options
    //

    public function get_added_field_options($db_name, $key_value = -1)
    {
        $method_name = "get_".$db_name."_options";

        if (!$this->method_exists($method_name))
            throw new Phpr_SystemException("Method ".$method_name." is not defined in ".$this->class_name." class");

        return $this->$method_name($key_value);
    }

    // Filters
    // 

    public function apply_visibility()
    {
        $this->where('is_enabled is not null and is_enabled=1');
        return $this;
    }

    // Custom columns
    //

    public function eval_provider_name()
    {
        return $this->get_name();
    }

    // Model handling
    // 

    public static function find_all_providers()
    {
        if (!self::$_provider_cache)
            return self::$_provider_cache = Social_Provider::create()->find_all();

        return self::$_provider_cache;
    }

    public static function get_provider($code)
    {
        $providers = self::find_all_providers();
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
    public static function find_all_active_providers($order = array())
    {
        if (!self::$_active_providers)
        {
            $active_providers = array();
            $providers = self::find_all_providers();
            
            foreach ($providers as $provider)
            {
                if ($provider->is_enabled)
                    $active_providers[$provider->code] = $provider;
            }

            self::$_active_providers = $active_providers;
        }

        if (self::$_active_providers)
        {
            return $order 
                ? self::sort_active_providers(self::$_active_providers, $order) 
                : self::$_active_providers;
        }
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