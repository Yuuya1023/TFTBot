<?php

include_once(dirname(__FILE__) . "/../src/app/post_to_twitter.php");

$poster = new PostToTwitter();
$poster->post( "koroazu" );

?>