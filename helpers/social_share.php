<?php

class Social_Share
{

	public static function facebook($url, $text=null, $title=null)
	{
		return 'http://www.facebook.com/sharer.php?s=100&amp;p[summary]='.urlencode($text).'&amp;p[url]='.urlencode($url).'&amp;p[title]='.$title;
	}

	public static function twitter($url, $text=null)
	{
		return 'http://twitter.com/share?text='.urlencode($text).'&amp;url='.urlencode($url);
	}

}