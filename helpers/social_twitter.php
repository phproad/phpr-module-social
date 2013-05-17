<?php

class Social_Twitter 
{
	public static function get_tweets($options = array()) 
	{
		extract(array_merge(array(
			'count' => 50,
			'format' => 'json'
		), $options));
		
		// Get tweets and decode them into a variable
		$url = "http://api.twitter.com/1/statuses/user_timeline.".$format
				."?id=".$username
				."&count=".$count
				."&page=1"
				."&include_rts=true"
				."&include_entities=true";

		$net = Net_Request::create($url);
		$response = $net->send();
		$tweet_data = $response->data;

		$items = json_decode($tweet_data); 
		$tweets = array();
		
		foreach(array_slice($items, 0, $count) as $item)
		{
			$time = self::ago(strtotime($item->created_at));
			$tweets[] = array(
				'content' => isset($item->retweeted_status) ? 'RT @' . $item->retweeted_status->user->screen_name . ': ' . $item->retweeted_status->text : $item->text, 
				'date' => $time);
		}

		return $tweets;
	}

	public static function ago($timestamp)
	{
		$difference = time() - $timestamp;

		if ($difference < 60)
			return $difference . " " . Phpr_String::word_form($difference, 'second') . " ago";
		else 
		{
			$difference = round($difference / 60);
			
			if ($difference < 60)
				return $difference . " " . Phpr_String::word_form($difference, 'minute') . " ago";
			else
			{
				$difference = round($difference / 60);
				
				if ($difference < 24)
					return $difference . " " . Phpr_String::word_form($difference, 'hour') . " ago";
				else
				{
					$difference = round($difference / 24);
					
					if ($difference < 7)
						return $difference . " " . Phpr_String::word_form($difference, 'day') . " ago";
					else
					{
						$difference = round($difference / 7);
						return $difference . " " . Phpr_String::word_form($difference, 'week') . " ago";
					}
				}
			}
		}
	}
	
}