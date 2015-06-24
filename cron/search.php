<?php

include_once(dirname(__FILE__) . "/../src/app/search_twitter.php");
include_once(dirname(__FILE__) . "/../src/app/twitter/oauth_object.php");

$twitter_manager = new SearchTwitter();
{
	$oauth_object = new OauthObject();
	$oauth_object->init( TWITTER_API_KEY, 
						TWITTER_API_KEY_SECRET,
						"", 
						"" 
						);
	$twitter_manager->search( $oauth_object, 1, "" );
}

?>