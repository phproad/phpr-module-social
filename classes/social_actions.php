<?php

class Social_Actions extends Cms_Action_Base
{

	public function on_confirm_email()
	{
		// Make sure all the data we need is available
		$module = Phpr_Module_Manager::get_by_id('social');
		$user_data = Phpr::$session->get('social_user_data', array());
		
		if (empty($user_data))
		{
			Phpr::$session->flash['error'] = "Unable to determine login provider.";
			return;
		}
		
		$provider = Social_Provider::get_provider($user_data['provider_code']);
		if (!$provider)
		{
			Phpr::$session->flash['error'] = "Unable to determine login provider.";
			return;
		}

		if (post('social_email_confirmation'))
		{
			$validation = new Phpr_Validation();
			$validation->add('email', 'Email')->fn('trim')->fn('mb_strtolower')->required()->Email('Please provide valid email address.');

			if (!$validation->validate($_POST))
				$validation->throw_exception();

			// If user already exists, attach the new provider but require they log in
			// first to prove they own the account
			if ($user = User::create()->find_by_email(post('email')))
				$validation->set_error('A user with that email address is already registered!', null, true);

			$user = $module->create_new_user($user_data);
			$module->set_provider_user($user, $user_data, $provider, true);
			Phpr::$session->remove('social_user_data');

			if (post('flash'))
				Phpr::$session->flash['success'] = post('flash');

			if (post('redirect'))
				Phpr::$response->redirect(post('redirect'));
		}
	}


}