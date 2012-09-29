<?php

class Social_Facebook 
{
    public $options;

    public function __construct($options = array()) 
    {
        $social = Social_Config::create(); 

        $this->options = array_merge(array(
            'app_id' => $social->facebook_app_id
        ), $options);
    }
    
    public static function create($options = array()) 
    {
        return new Social_Facebook($options);
    }

    public function facepile($options = array()) 
    {
        extract(array_merge(array(
            'size' => 'medium',
            'colorscheme' => 'light',
            'max_rows' => 1,
            'width' => 200
        ), $this->options, $options));
        

        $str = '<iframe src="//www.facebook.com/plugins/facepile.php?'
            .'href='.urlencode(root_url('/', true))
            .'&amp;action&amp;size='.$size
            .'&amp;max_rows='.$max_rows
            .'&amp;width='.$width
            .'&amp;colorscheme='.$colorscheme
            .'&amp;appId='.$this->options['app_id']
            .'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$width.'px;" allowTransparency="true"></iframe>';

        return $str;
    }
}

