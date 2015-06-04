<?php

session_start();

include_once(dirname(__FILE__) . "/../../../../define.php");
require_once(dirname(__FILE__) . "/../lib/twitteroauth/autoload.php");
use Abraham\TwitterOAuth\TwitterOAuth;


//TwitterOAuth をインスタンス化
$connection = new TwitterOAuth(TWITTER_API_KEY, TWITTER_API_KEY_SECRET);

//コールバックURLをここでセット
$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => CALLBACK_URL));

//callback.phpで使うのでセッションに入れる
$_SESSION['oauth_token'] = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

//Twitter.com 上の認証画面のURLを取得( この行についてはコメント欄も参照 )
$url = $connection->url('oauth/authenticate', array('oauth_token' => $request_token['oauth_token']));

//Twitter.com の認証画面へリダイレクト
header( 'location: '. $url );