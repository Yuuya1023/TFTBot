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

		return $this->uploadReplyImage( $oauth_object, $photo_url_list, null, null, null );
	}

	public function uploadReplyImage( $oauth_object, $photo_url_list, $reply_to_status_id, $screen_name, $mentions ){

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
		$parameters;
		if ( $reply_to_status_id !== null && $screen_name !== null ) {
			// リプの巻き込み
			$mention = "";
			if ( $mentions !== null && count($mentions) ) {
				$mention_list = array($screen_name);
				for ($i=0; $i < count($mentions) ; $i++) { 
					$mention_list[count($mention_list)] = $mentions[$i]["screen_name"];
				}

				//配列で重複している物を削除する
				$mention_list = array_unique($mention_list);
				$mention_list = array_values($mention_list);

				$mention = "@" . implode(" @", $mention_list);
			}
			else {
				$mention = "@" . $screen_name;
			}
			$parameters = array(
			    'status' => $mention,
			    'media_ids' => implode( ",", $media_ids),
			    'in_reply_to_status_id' => $reply_to_status_id,
			);
		}
		else {
			$parameters = array(
			    'status' => '',
			    'media_ids' => implode( ",", $media_ids),
			);
		}
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

	public function streaming( $oauth_object, $match_text_list, $blog_name ){
		$url = 'https://userstream.twitter.com/1.1/user.json';
		$method = 'GET';
 
		// パラメータ
		$oauth_parameters = array(
		    'oauth_consumer_key' => $oauth_object->getConsumerKey(),
		    'oauth_nonce' => microtime(),
		    'oauth_signature_method' => 'HMAC-SHA1',
		    'oauth_timestamp' => time(),
		    'oauth_token' => $oauth_object->getAccessToken(),
		    'oauth_version' => '1.0',
		);
 
		// 署名を作る
		$a = $oauth_parameters;
		ksort($a);
		$base_string = implode('&', array(
		    rawurlencode($method),
		    rawurlencode($url),
		    // rawurlencode(http_build_query($a, '', '&', PHP_QUERY_RFC3986))
		    rawurlencode($this->http_build_query_rfc_3986($a, '&'))
		));
		$key = implode('&', array(rawurlencode($oauth_object->getConsumerSecret()), rawurlencode($oauth_object->getAccessTokenSecret())));
		$oauth_parameters['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $key, true));
 
 
		// 接続＆データ取得
		$fp = fsockopen("ssl://userstream.twitter.com", 443);
		if ($fp) {
		    fwrite($fp, "GET " . $url . " HTTP/1.1\r\n"
		                . "Host: userstream.twitter.com\r\n"
		                // . 'Authorization: OAuth ' . http_build_query($oauth_parameters, '', ',', PHP_QUERY_RFC3986) . "\r\n"
		                . 'Authorization: OAuth ' . $this->http_build_query_rfc_3986($oauth_parameters, ',') . "\r\n"
		                . "\r\n");

		    while (!feof($fp)) {
		        $res = fgets($fp);
				$res = json_decode($res, true);

				// print_r($res);

				if ( $res && array_key_exists( "id", $res)) {
					$text = "" .  $res['text'];
					// print_r(" @" . $res["user"]["screen_name"]);
					// print_r(" text->" . $text);

					// リツイートには反応しない
					if ( !preg_match("/RT/u", $text) ) {
						// 指定した文字列が入っているか
						$match_text = implode("|", $match_text_list);
   						if( preg_match("/" . $match_text . "/u", $text) ) {
							// print_r(" hit!!!!!!!!!");
							// print_r("\n");

							$database_manager = new DatabaseManager();
							$database_manager->connect();

							$count = DatabaseHelper::selectCountFromAutoReplyLog( $database_manager );

							// 規制回避のため直近15分の間に100件まで
							$error_msg = "";
							$post_limit = 100;
							if ( $count < $post_limit ) {
								$photo_url = DatabaseHelper::selectRandomPhotoUrlFromTumblrPost( $database_manager, $blog_name );
								$photo_url_list = array($photo_url);
								$upload_result = $this->uploadReplyImage( $oauth_object, 
																		$photo_url_list, 
																		$res["id"], 
																		$res["user"]["screen_name"], 
																		$res["entities"]["user_mentions"] );

								if ( array_key_exists( "errors", $upload_result ) ) {
									$errors = $upload_result->errors;
									foreach ($errors as $error) {
										$error_msg = $error->code . " " . $error->message . ", " . $error_msg;
									}
								}

							}
							else {
								// 規制回避のため自粛
								$error_msg = "reached at post limit(" . $post_limit . ").";
							}
							DatabaseHelper::insertAutoReplyLog( $database_manager, $blog_name, $error_msg );
							$database_manager->close();
						}
						else {
							// print_r(" no");
							// print_r("\n");
						}
					}
				}
		    }
		    fclose($fp);
		}
	}

	private function http_build_query_rfc_3986($query_data, $arg_separator='&') {
	    $r = '';
	    $query_data = (array) $query_data;
	    if(!empty($query_data))
	    {
	        foreach($query_data as $k=>$query_var)
	        {
	            $r .= $arg_separator;
	            $r .= $k;
	            $r .= '=';
	            $r .= rawurlencode($query_var);
	        }
	    }
	    return trim($r,$arg_separator);
	}
}

?>