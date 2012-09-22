<?php

class Social_Twitter 
{
    public $options;

    public function __construct($options = array()) 
    {
        $this->options = array_merge(array(
            'username' => null
        ), $options);
    }
    
    public static function create($options = array()) 
    {
        return new Social_Twitter($options);
    }
    
    public static function ago($timestamp)
    {
        $difference = time() - $timestamp;

        if ($difference < 60)
                return $difference." seconds ago";
        else 
        {
            $difference = round($difference / 60);
            
            if ($difference < 60)
                    return $difference." minutes ago";
            else
            {
                $difference = round($difference / 60);
                
                if ($difference < 24)
                        return $difference." hours ago";
                else
                {
                    $difference = round($difference / 24);
                    
                    if ($difference < 7)
                            return $difference." days ago";
                    else
                    {
                            $difference = round($difference / 7);
                            return $difference." weeks ago";
                    }
                }
            }
        }
    }

    public function get_tweets($options = array()) 
    {
        extract(array_merge(array(
            'count' => 50,
            'format' => 'json'
        ), $this->options, $options));
        
        $items = json_decode(file_get_contents("http://api.twitter.com/1/statuses/user_timeline.{$format}?id={$username}&count={$count}&page=1&include_rts=true&include_entities=true")); // get tweets and decode them into a variable
        $tweets = array();
        
        foreach(array_slice($items, 0, $count) as $item):
            $time = self::ago(strtotime($item->created_at));
            
            $tweets[] = array('content' => isset($item->retweeted_status) ? 'RT @' . $item->retweeted_status->user->screen_name . ': ' . $item->retweeted_status->text : $item->text, 'date' => $time);
        endforeach;

        return $tweets;
    }
}