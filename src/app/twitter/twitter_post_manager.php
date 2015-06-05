<?php

include_once(dirname(__FILE__) . "/../../../define.php");
include_once(dirname(__FILE__) . "/../util/util.php");
include_once(dirname(__FILE__) . "/oauth_object.php");
require_once(dirname(__FILE__) . "/lib/twitteroauth/autoload.php");
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterPostManager
{

	private function connect( $oauth_object ){

		$connection = new TwitterOAuth($oauth_object->getConsumerKey(), $oauth_object->getConsumerSecret(), $oauth_object->getAccessToken(), $oauth_object->getAccessTokenSecret());
		return $connection;
	}

	public function uploadImage( $oauth_object, $photo_url_list ){

		//接続
		$connection = $this->connect( $oauth_object );

		$media_ids = array();
		foreach ($photo_url_list as $photo_url) {
			$media_id = $connection->upload("media/upload", array("media" => $photo_url));
			$media_ids[count($media_ids)] = $media_id->media_id_string;
		}

		// var_dump($media_ids);
		// echo "<p><p>";
		// echo implode( ",", $media_ids);
		// echo "<p><p>";

		// 投稿
		$parameters = array(
		    'status' => '',
		    'media_ids' => implode( ",", $media_ids),
		);
		$result = $connection->post('statuses/update', $parameters);

		// $json = json_encode($result);
		// echo indent($json);

		return $result;
	}

	public function uploadText( $oauth_object, $text ){

		//接続
		$connection = $this->connect( $oauth_object );

		//ツイート
		$result = $connection->post("statuses/update", array("status" => $text));

		// $json = json_encode($result);
		// echo indent($json);

		return $result;
	}

}

?>