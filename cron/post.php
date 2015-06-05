<?php

include_once(dirname(__FILE__) . "/../src/app/post_to_twitter.php");
include_once(dirname(__FILE__) . "/../src/app/twitter/oauth_object.php");

$poster = new PostToTwitter();
{
	$oauth_object = new OauthObject();
	$oauth_object->init( TWITTER_API_KEY, TWITTER_API_KEY_SECRET, TWITTER_ACCES_TOKEN, TWITTER_ACCES_TOKEN_SECRET );
	$poster->post(  $oauth_object, "koroazu" );
}

?>