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

    public function facepile($url=null, $options = array()) 
    {
        if (!$url)
            root_url('/', true);

        extract(array_merge(array(
            'size' => 'medium',
            'colorscheme' => 'light',
            'max_rows' => 1,
            'width' => 200
        ), $this->options, $options));
        

        $str = '<iframe src="//www.facebook.com/plugins/facepile.php?'
            .'href='.urlencode($url)
            .'&amp;action&amp;size='.$size
            .'&amp;max_rows='.$max_rows
            .'&amp;width='.$width
            .'&amp;colorscheme='.$colorscheme
            .'&amp;appId='.$this->options['app_id']
            .'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$width.'px;" allowTransparency="true"></iframe>';

        return $str;
    }

    public function like_button($url, $options = array())
    {
        if (!$url)
            root_url('/', true);

        extract(array_merge(array(
            'show_send_button' => false,
            'show_faces' => false,
            'action' => 'like', // like or recommend
            'size' => 'medium',
            'colorscheme' => 'light',
            'max_rows' => 1,
            'width' => 100,
            'height' => 35
        ), $this->options, $options));

        $str = '<iframe src="//www.facebook.com/plugins/like.php'
            .'href='.urlencode($url)
            .'&amp;send='.(($show_send_button) ? 'true' : 'false')
            .'&amp;show_faces='.(($show_faces) ? 'true' : 'false')
            .'&amp;action='.$action
            .'&amp;colorscheme='.$colorscheme
            .'&amp;font'
            .'&amp;height='.$height
            .'&amp;appId='.$this->options['app_id']
            .'&amp;width='.$width
            .'&amp;appId='.$this->options['app_id']
            .'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$width.'px;  height:'.$height.'px;" allowTransparency="true"></iframe>';
    }
}

