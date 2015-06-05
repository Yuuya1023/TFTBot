<?php

include_once(dirname(__FILE__) . "/../src/app/tumblr_post_register.php");

$register = new TumblrPostRegister();
$register->regist( "koroazu" );
// $register->regist( "daredemo-daisuki" );

echo "aaaa";
?>