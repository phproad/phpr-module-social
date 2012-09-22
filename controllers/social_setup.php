<?php

class Social_Setup extends Admin_Settings_Controller
{   
    public $implement = 'Db_FormBehavior';

    public $form_edit_title = 'Social Settings';
    public $form_model_class = 'Social_Config';
    public $form_flash_id = 'form_flash';

    public $form_redirect = null;
    public $form_edit_save_flash = 'Social configuration has been saved.';

    public function __construct()
    {
        parent::__construct();
        $this->app_menu = 'system';     
        $this->form_redirect = url('admin/settings/');
    }

    public function index()
    {   
        $this->app_page_title = $this->form_edit_title;
        
        try
        {
            $record = Social_Config::create();                 
            $this->viewData['form_model'] = $record;            
        }
        catch (exception $ex)
        {
            $this->handlePageError($ex);
        }
    }

    protected function index_onSave()
    {
        try
        {
            $config = Social_Config::create();
            $config->save(post($this->form_model_class, array()), $this->formGetEditSessionKey());
            Phpr::$session->flash['success'] = 'Social configuration has been successfully saved.';
            Phpr::$response->redirect(url('admin/settings/'));
        }
        catch (Exception $ex)
        {
            Phpr::$response->ajaxReportException($ex, true, true);
        }       
    }

}
