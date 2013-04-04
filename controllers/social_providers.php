<?php

class Social_Providers extends Admin_Controller
{
	public $implement = 'Db_List_Behavior, Db_Form_Behavior';
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

		$this->list_record_url = url('social/providers/edit');
		$this->form_redirect = url('social/providers');
	}

	public function index()
	{
		$this->app_page_title = 'Providers';
	}

	public function create_form_before_display($model)
	{
		$model->code = $model->get_code();
	}

	public function form_create_model_object()
	{
		$model = Social_Provider::create();

		$class_name = Phpr::$router->param('param1');

		if (!Phpr::$class_loader->load($class_name))
			throw new Phpr_ApplicationException('Class '.$class_name.' not found');

		$model->class_name = $class_name;
		$model->init_columns();
		$model->init_form_fields();
		$model->code = $model->get_code();

		return $model;
	}

	protected function index_on_load_add_popup()
	{
		try
		{
			$provider_list = Social_Manager::get_providers();
			usort($provider_list, array('Social_Providers', 'provider_compare'));
			$this->view_data['provider_list'] = $provider_list;
		}
		catch (Exception $ex)
		{
			$this->handle_page_error($ex);
		}

		$this->display_partial('add_provider_form');
	}

	public static function provider_compare($a, $b)
	{
		return strcasecmp($a->get_name(), $b->get_name());
	}

	public function list_get_row_class($model)
	{
		return $model->is_enabled ? null : 'disabled';
	}
}

