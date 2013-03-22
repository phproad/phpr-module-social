<?php

class Social_Facebook 
{
	const provider_code = 'facebook';

	public static function facepile($options = array()) 
	{
		$provider = Social_Provider::get_provider(self::provider_code);
		if (!$provider)
			return "";

		extract(array_merge(array(
			'url' => root_url('/', true),
			'size' => 'medium',
			'colorscheme' => 'light',
			'max_rows' => 1,
			'width' => 200
		), $options));
		

		$str = '<iframe src="//www.facebook.com/plugins/facepile.php?'
			.'href='.urlencode($url)
			.'&amp;action&amp;size='.$size
			.'&amp;max_rows='.$max_rows
			.'&amp;width='.$width
			.'&amp;colorscheme='.$colorscheme
			.'&amp;appId='.$provider->facebook_app_id
			.'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$width.'px;" allowTransparency="true"></iframe>';

		return $str;
	}

	public static function like_button($options = array())
	{
		$provider = Social_Provider::get_provider(self::provider_code);

		if (!$provider)
			return "";

		extract(array_merge(array(
			'url' => root_url('/', true),
			'show_send_button' => false,
			'show_faces' => false,
			'action' => 'like', // like, recommend
			'size' => 'medium',
			'colorscheme' => 'light',
			'max_rows' => 1,
			'layout' => 'button_count', // button_count, box_count, standard
			'width' => 100,
			'height' => 35
		), $options));

		$str = '<iframe src="//www.facebook.com/plugins/like.php?'
			.'href='.urlencode($url)
			.'&amp;send='.(($show_send_button) ? 'true' : 'false')
			.'&amp;show_faces='.(($show_faces) ? 'true' : 'false')
			.'&amp;action='.$action
			.'&amp;colorscheme='.$colorscheme
			.'&amp;layout='.$layout
			.'&amp;font'
			.'&amp;height='.$height
			.'&amp;appId='.$provider->facebook_app_id
			.'&amp;width='.$width
			.'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$width.'px;  height:'.$height.'px;" allowTransparency="true"></iframe>';

		return $str;
	}
}

