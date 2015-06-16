<?php

include_once(dirname(__FILE__) . "/../src/app/post_to_twitter.php");
include_once(dirname(__FILE__) . "/../src/app/twitter/oauth_object.php");

$twitter_manager = new TwitterPostManager();
{
	$oauth_object = new OauthObject();
	$oauth_object->init( TWITTER_API_KEY, 
						TWITTER_API_KEY_SECRET, 
						"", 
						"" 
						);
	$list = array(
		"___bot_test___", 
		);
	$twitter_manager->streaming( $oauth_object, "koroazu_daisuki", $list, "koroazu");
}

?>