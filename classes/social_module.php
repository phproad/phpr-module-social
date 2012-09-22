<?php

class Social_Module extends Core_Module_Base
{

    protected function set_module_info()
    {
        return new Core_Module_Detail(
            "Social",
            "Social functions",
            "Scripts Ahoy!",
            "http://scriptsahoy.com/"
        );
    }

    public function build_admin_settings($settings)
    {
        $settings->add('/social/setup', 'Social Settings', 'Social website settings', '/modules/social/assets/images/social_config.png', 300);
    }
}
