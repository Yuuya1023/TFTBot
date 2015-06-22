<?php

include_once(dirname(__FILE__) . "/../../../define.php");
include_once(dirname(__FILE__) . "/../util/util.php");
include_once(dirname(__FILE__) . "/oauth_object.php");
include_once(dirname(__FILE__) . "/streaming_object.php");
require_once(dirname(__FILE__) . "/lib/twitteroauth/autoload.php");
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterPostManager
{

	private $retryCount = 0;

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
			if ( !array_key_exists( "error", $media_id ) ) {
				$media_ids[count($media_ids)] = $media_id->media_id_string;
			}
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
			if ( $mentions !== null && count($mentions) > 0 ) {
				$mention_list = array($screen_name);
				for ($i=0; $i < count($mentions) ; $i++) { 
					$mention_list[count($mention_list)] = $mentions[$i];
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

	public function streaming( $oauth_object, $my_screen_name, $match_text_list, $blog_name ){
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
		$errno = null;
 		$errstr = null;
		$fp = fsockopen("ssl://userstream.twitter.com", 443, $errno, $errstr);
		if ($fp) {
		    fwrite($fp, "GET " . $url . " HTTP/1.1\r\n"
		                . "Host: userstream.twitter.com\r\n"
		                // . 'Authorization: OAuth ' . http_build_query($oauth_parameters, '', ',', PHP_QUERY_RFC3986) . "\r\n"
		                . 'Authorization: OAuth ' . $this->http_build_query_rfc_3986($oauth_parameters, ',') . "\r\n"
		                . "\r\n");

		    while ( !feof($fp) ) {
				$streaming_obj = new StreamingObject();
				$streaming_obj->init( fgets($fp) );

				if ( $streaming_obj->isValidResponse() ) {
					// デバッグ
					$streaming_obj->displayDetail( $match_text_list );

					// リツイートには反応しない,指定した文字列が入っているか
					if ( !$streaming_obj->isRetweeted() && $streaming_obj->isIncludeText( $match_text_list ) ) {
						// メンションのリスト作成
						$mention_list = $streaming_obj->getMentionList();

						// 自分を巻き込んでる場合は反応しない
						if ( !in_array( $my_screen_name, $mention_list ) ) {
							$this->replyImage( $oauth_object, $streaming_obj, $mention_list, $blog_name );
						}
					}
				}
				else {
					var_dump( $streaming_obj->getJson() );
				}
		    }
		    fclose($fp);

		    // リトライ
		    $this->retryCount++;
		    if ( $this->retryCount < 20 ) {
				$error_msg = "connection failed. retry count is " . $this->retryCount;
				$this->recordError( $error_msg );

				sleep( 60 );
		  		$this->streaming( $oauth_object, $my_screen_name, $match_text_list, $blog_name );
		    }
		}
		else {
			// ソケット通信エラー
			$error_msg = $errno . " " . $errstr;
			$this->recordError( $error_msg );
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


	private function replyImage( $oauth_object, $streaming_obj, $mention_list, $blog_name ) {
		$database_manager = new DatabaseManager();
		$database_manager->connect();

		// 規制回避のため直近15分の間に50件まで
		$error_msg = "";
		$post_limit = 50;
		$count = DatabaseHelper::selectCountFromAutoReplyLog( $database_manager );
		if ( $count < $post_limit ) {
			$photo_url_list = DatabaseHelper::selectRandomPhotoUrlFromTumblrPost( $database_manager, $blog_name, 1 );
			// $photo_url_list = array($photo_url);
			$upload_result = $this->uploadReplyImage( $oauth_object, 
													$photo_url_list, 
													$streaming_obj->getId(), 
													$streaming_obj->getScreenName(), 
													$mention_list );

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


	private function recordError( $error_msg ) {

		$database_manager = new DatabaseManager();
		$database_manager->connect();
		DatabaseHelper::insertAutoReplyLog( $database_manager, "-1", $error_msg );
		$database_manager->close();

	}

}

?>