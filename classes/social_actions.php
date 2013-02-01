<?php

class Social_Actions extends Cms_Action_Base
{

    public function on_email_confirmation()
    {
        // Make sure all the data we need is available
        $module = Phpr_Module_Manager::find_by_id('social');
        $user_data = Phpr::$session->get('social_user_data', array());
        
        if (empty($user_data))
        {
            Phpr::$session->flash['error'] = "Unable to determine login provider.";
            return;
        }
        
        $provider = Social_Provider_Manager::get_provider($user_data['provider_id']);
        if (empty($provider))
        {
            Phpr::$session->flash['error'] = "Unable to determine login provider.";
            return;
        }

        //Make sure the provider isn't already attached to anyone
        //$user_provider = Db_Helper::object("
        //  SELECT * FROM social_user_providers
        //  WHERE provider_id=:provider_id AND provider_token=:provider_token",
        //  array(
        //      'provider_id' => $provider->info['id'],
        //      'provider_token' => $user_data['token'],
        //  )
        //);
        //if ( $user_provider )
        //{
        //  Phpr::$session->flash['error'] = "Please log in via the login page.";
        //  return;
        //}

        if (post('flynsarmysocialmedia_email_confirmation'))
        {
            $validation = new Phpr_Validation();
            $validation->add('email', 'Email')->fn('trim')->fn('mb_strtolower')->required()->Email('Please provide valid email address.');
            //$validation->add('first_name', 'First Name')->fn('trim')->required("Please specify a first name");
            //$validation->add('last_name', 'Last Name')->fn('trim')->required("Please specify a last name");
            if (post('password') || post('confirm_password'))
            {
                $validation->add('password', 'Password')->fn('trim')->required();
                $validation->add('confirm_password', 'Password Confirmation')->fn('trim')->matches('password', 'Password and confirmation password do not match.');
            }
            if (!$validation->validate($_POST))
                $validation->throwException();

            // If user already exists, attach the new provider but require they log in
            // first to prove they own the account
            if ($user = User::create()->find_by_email(post('email')))
            {
                $user_provider = $module->set_provider_user(
                    $user,
                    $user_data,
                    $provider,
                    false
                );

                $template = Email_Template::create()->find_by_code('social_associate_provider');
                if (!$template)
                {
                    Phpr::$session->flash['error'] = "Error, email template not found.";
                    return;
                }

                $url = root_url(
                    "social_associate_email?confirm=".
                        $user_provider->id.
                        $user_provider->shop_user_id.
                        $user_provider->provider_token,
                    true
                );
                $message = $user->set_user_email_vars($template->content);
                $message = str_replace('{social_provider_name}', $provider->info['name'], $message);
                $message = str_replace('{social_associate_url}', $url, $message);
                $template->send_to_user($user, $message);
                Phpr::$session->remove('social_user_data');

                if (post('flash_associated'))
                    Phpr::$session->flash['success'] = sprintf(post('flash_associated', ''), post('email'));
                else
                    Phpr::$session->flash['success'] = $provider->info['name'] . " successfully associated with your account. An email confirmation has been sent to ".post('email');

                if ( post('redirect_associated') )
                    Phpr::$response->redirect(post('redirect_associated'));
                return;
            }

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