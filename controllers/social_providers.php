<?php

class Social_Providers extends Admin_Controller
{
    public $implement = 'Db_ListBehavior, Db_FormBehavior';
    public $list_model_class = 'Social_Provider';
    public $list_record_url = null;
    public $list_reuse_model = false;
    public $list_no_sorting = true;

    public $form_preview_title = 'Social Provider';
    public $form_create_title = 'New Social Provider';
    public $form_edit_title = 'Edit Social Provider';
    public $form_model_class = 'Social_Provider';
    public $form_not_found_message = 'Social provider not found';
    public $form_redirect = null;

    public $form_edit_save_flash = 'The social provider has been successfully saved';
    public $form_create_save_flash = 'The social provider has been successfully added';
    public $form_edit_delete_flash = 'The social provider has been successfully deleted';
    
    protected $required_permissions = array('social:manage_providers');

    public function __construct()
    {
        parent::__construct();
        $this->app_menu = 'social';
        $this->app_page = 'providers';
        $this->app_module_name = 'Social';

        $this->list_record_url = url('/social/providers/edit/');
        $this->form_redirect = url('/social/providers');
    }

    public function index()
    {
        $this->app_page_title = 'Providers';
    }

    public function formCreateModelObject()
    {
        $obj = Social_Provider::create();

        $class_name = Phpr::$router->param('param1');

        if (!Phpr::$class_loader->load($class_name))
            throw new Phpr_ApplicationException('Class '.$class_name.' not found');

        $obj->class_name = $class_name;
        $obj->init_columns_info();
        $obj->define_form_fields();

        $provider_info = $obj->get_provider_object()->get_info();
        $obj->code = isset($provider_info['id']) ? $provider_info['id'] : null;

        return $obj;
    }

    protected function index_on_load_add_popup()
    {
        try
        {
            $providers = Social_Provider_Manager::list_providers();

            $provider_list = array();
            foreach ($providers as $class_name)
            {
                $obj = new $class_name();
                $info = $obj->get_info();
                if (array_key_exists('name', $info))
                {
                    $info['class_name'] = $class_name;
                    $provider_list[] = $info;
                }
            }

            usort($provider_list, array('Social_Providers', 'provider_cmp'));

            $this->viewData['provider_list'] = $provider_list;
        }
        catch (Exception $ex)
        {
            $this->handlePageError($ex);
        }

        $this->renderPartial('add_provider_form');
    }

    public static function provider_cmp($a, $b)
    {
        return strcasecmp($a['name'], $b['name']);
    }

    public function listGetRowClass($model)
    {
        return $model->is_enabled ? null : 'disabled';
    }
}

