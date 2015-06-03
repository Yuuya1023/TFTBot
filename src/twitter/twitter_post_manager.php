<?php

include_once(dirname(__FILE__) . "/../define.php");
include_once(dirname(__FILE__) . "/../util/util.php");
require_once(dirname(__FILE__) . "/lib/twitteroauth/autoload.php");
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterPostManager
{
	private $consumerKey = TWITTER_API_KEY;
	private $consumerSecret = TWITTER_API_KEY_SECRET;
	private $accessToken = TWITTER_ACCES_TOKEN;
	private $accessTokenSecret = TWITTER_ACCES_TOKEN_SECRET;

	private function connect(){

		$connection = new TwitterOAuth($this->consumerKey, $this->consumerSecret, $this->accessToken, $this->accessTokenSecret);
		return $connection;
	}

	public function uploadImage( $phto_url ){

		//接続
		$connection = $this->connect();
		$media_id = $connection->upload("media/upload", array("media" => $phto_url));

		// var_dump($media_id);
		// echo "<p><p>";

		// 投稿
		$parameters = array(
		    'status' => '',
		    'media_ids' => $media_id->media_id_string,
		);
		$result = $connection->post('statuses/update', $parameters);

		// $json = json_encode($result);
		// echo indent($json);

		return $result;
	}

	public function uploadText( $text ){

		//接続
		$connection = $this->connect();

		//ツイート
		$result = $connection->post("statuses/update", array("status" => $text));

		// $json = json_encode($result);
		// echo indent($json);

		return $result;
	}

}

?>