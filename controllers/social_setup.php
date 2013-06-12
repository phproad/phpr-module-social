<?php

class Social_Setup extends Admin_Settings_Controller
{
	public $implement = 'Db_Form_Behavior';

	public $form_edit_title = 'Social Settings';
	public $form_model_class = 'Social_Config';
	public $form_flash_id = 'form-flash';

	public $form_redirect = null;
	public $form_edit_save_flash = 'Social configuration has been saved.';

	public function __construct()
	{
		parent::__construct();
		$this->app_menu = 'system';
		$this->form_redirect = url('social/providers');
	}

	public function index()
	{   
		$this->app_page_title = $this->form_edit_title;
		
		try
		{
			$record = Social_Config::create();
			$this->view_data['form_model'] = $record;
		}
		catch (exception $ex)
		{
			$this->handle_page_error($ex);
		}
	}

	protected function index_on_save()
	{
		try
		{
			$config = Social_Config::create();
			$config->save(post($this->form_model_class, array()), $this->form_get_edit_session_key());
			Phpr::$session->flash['success'] = 'Social configuration has been successfully saved.';
			Phpr::$response->redirect($this->form_redirect);
		}
		catch (Exception $ex)
		{
			Phpr::$response->ajax_report_exception($ex, true, true);
		}       
	}

}
