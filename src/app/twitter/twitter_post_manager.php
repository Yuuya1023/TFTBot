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
		$parameters = array(
		    'status' => '',
		    'media_ids' => implode( ",", $media_ids),
		);
		if ( $reply_to_status_id !== null && $screen_name !== null ) {
			// リプの巻き込み
			$mention = "";
			if ( $mentions !== null && count($mentions) > 0 ) {
				$mention_list = array($screen_name);
				foreach ($mentions as $s) { 
					$mention_list[] = $s;
				}

				//配列で重複している物を削除する
				$mention_list = array_unique($mention_list);
				$mention_list = array_values($mention_list);

				$mention = "@" . implode(" @", $mention_list);
			}
			else {
				$mention = "@" . $screen_name;
			}

			$parameters["status"] = $mention;
			$parameters["in_reply_to_status_id"] = $reply_to_status_id;
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


	public function sendDirectMessage( $oauth_object, $screen_name, $text ){

		//接続
		$connection = $this->connect( $oauth_object );

		$param = array(
		    "screen_name"	=>	$screen_name,
		    "text"	=> 	$text,
		    );

		$result = $connection->post("direct_messages/new", $param);

		return $result;
	}


	public function search( $oauth_object, $keyword, $count, $since_id ){

		$connection = $this->connect( $oauth_object );
		 
		$param = array(
		    "q"				=> $keyword,
		    "lang"			=> "ja",
		    "count"			=> $count,
		    "result_type"	=> "recent",
		    );

		if ( $since_id !== null ) {
			$param["since_id"] = $since_id; 
		}
		  
		$result = $connection->get("search/tweets", $param);

		// $statuses = $result->statuses;
		// foreach ($statuses as $s) {
		// 	$streaming_obj = new StreamingObject();
		// 	$streaming_obj->initWithJson( $s );

		// 	$streaming_obj->displayTweet();
		// }

		return $result;
	}


	public function streaming( $oauth_object, $my_screen_name, $match_text_list, $blog_name ){
		$url = 'https://userstream.twitter.com/1.1/user.json';
		$method = 'GET';
 
		// パラメータ
		$oauth_parameters = $this->createOauthParams( $url, $method, $oauth_object, null );
 
		// 接続＆データ取得
		$errno = null;
 		$errstr = null;
 		try {
			$fp = fsockopen("ssl://userstream.twitter.com", 443, $errno, $errstr);
			if ($fp) {
			    fwrite($fp, "GET " . $url . " HTTP/1.1\r\n"
			                . "Host: userstream.twitter.com\r\n"
			                . 'Authorization: OAuth ' . $this->http_build_query_rfc_3986($oauth_parameters, ',') . "\r\n"
			                . "\r\n");

			    while ( !feof($fp) ) {
					// var_dump( feof($fp) );
					$streaming_obj = new StreamingObject();
					$streaming_obj->init( fgets($fp) );

					$this->autoReply( $oauth_object, $streaming_obj, $my_screen_name, $blog_name, $match_text_list );
					// $this->ticketBotMonitor( $streaming_obj );
			    }
			    fclose($fp);

			    // リトライ
			    $this->retryCount++;
			    // if ( $this->retryCount < 20 ) {
					$error_msg = "connection failed. retry count is " . $this->retryCount;
					$this->recordError( $error_msg );

					sleep( 60 );
			  		$this->streaming( $oauth_object, $my_screen_name, $match_text_list, $blog_name );
			    // }
			}
			else {
				// ソケット通信エラー
				$error_msg = $errno . " " . $errstr;
				$this->recordError( $error_msg );
			}
 		} 
 		catch (Exception $e) {
 			// Twitterライブラリ内ですろーされた例外
			$this->recordError( $e->getMessage() );

			sleep( 60 * 10 );
	  		$this->streaming( $oauth_object, $my_screen_name, $match_text_list, $blog_name );
 		}
	}


	private function autoReply( $oauth_object, $streaming_obj, $my_screen_name, $blog_name, $match_text_list ) {

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
			// var_dump( $streaming_obj->getJson() );
		}
	}

	private function ticketBotMonitor( $streaming_obj ) {

		if ( $streaming_obj->isValidResponse() ) {
			$streaming_obj->displayDetail( null );

			if ( strcasecmp( $streaming_obj->getScreenName() , "for_yu_84" ) !== 0 || $streaming_obj->isRetweeted() ) return;

			// デバッグ
			print_r($streaming_obj->getHashtags());

			if ( $streaming_obj->isIncludeHashtag( "チケボ監視君" ) ) {
				$database_manager = new DatabaseManager();
				$database_manager->connect();

				$list = explode(",", $streaming_obj->getText());
				print_r("------------------");
				print_r($list);
				print_r("------------------");

				if ( $streaming_obj->isIncludeHashtag( "登録" ) ) {
					// 登録 
					$table_name = "otamesi_twitter_search_word";
					$word = $list[0];
					$notice_user = $list[1];
					DatabaseHelper::insertTwitterSearchWord( $database_manager, $table_name, $word, $notice_user );
				}
				else if ( $streaming_obj->isIncludeHashtag( "更新" ) ) {
					// 更新
					
				}
				else if ( $streaming_obj->isIncludeHashtag( "取得" ) ) {
					// 取得
					
				}
				else if ( $streaming_obj->isIncludeHashtag( "削除" ) ) {
					// 削除
					
				}
				$database_manager->close();
			}
		}
		else {
			// var_dump( $streaming_obj->getJson() );
		}
	}

	// public function streamingFilter( $oauth_object, $match_text_list ){

	// 	$url = 'https://stream.twitter.com/1.1/statuses/sample.json';
	// 	$method = 'GET';
 // 		print_r( $url );

	// 	// $get_parameters = array(
	// 	//     'locations' => '132.2,29.9,146.2,39.0,138.4,33.5,146.1,46.20',
	// 	// );

	// 	// パラメータ
	// 	$oauth_parameters = $this->createOauthParams( $url, $method, $oauth_object, $get_parameters );
	// 	var_dump($oauth_parameters);
 
	// 	// 接続＆データ取得
	// 	$errno = null;
 // 		$errstr = null;
	// 	$fp = fsockopen("ssl://userstream.twitter.com", 443, $errno, $errstr);
	// 	if ($fp) {
	// 	    fwrite($fp, "GET " . $url . " HTTP/1.1\r\n"
	// 	                . "Host: userstream.twitter.com\r\n"
	// 	                . 'Authorization: OAuth ' . $this->http_build_query_rfc_3986($oauth_parameters, ',') . "\r\n"
	// 	                . "\r\n");

	// 	    while ( !feof($fp) ) {
	// 			// var_dump( feof($fp) );
	// 			$streaming_obj = new StreamingObject();
	// 			$streaming_obj->init( fgets($fp) );

	// 			if ( $streaming_obj->isValidResponse() ) {
	// 				// デバッグ
	// 				$streaming_obj->displayDetail( $match_text_list );

	// 				// // リツイートには反応しない,指定した文字列が入っているか
	// 				// if ( !$streaming_obj->isRetweeted() && $streaming_obj->isIncludeText( $match_text_list ) ) {
	// 				// 	// メンションのリスト作成
	// 				// 	$mention_list = $streaming_obj->getMentionList();

	// 				// 	// 自分を巻き込んでる場合は反応しない
	// 				// 	if ( !in_array( $my_screen_name, $mention_list ) ) {
	// 				// 		$this->replyImage( $oauth_object, $streaming_obj, $mention_list, $blog_name );
	// 				// 	}
	// 				// }
	// 			}
	// 			else {
	// 				var_dump( $streaming_obj->getJson() );
	// 			}
	// 	    }
	// 	    fclose($fp);

	// 	    // リトライ
	// 	    $this->retryCount++;
	// 	    if ( $this->retryCount < 20 ) {
	// 			$error_msg = "connection failed. retry count is " . $this->retryCount;
	// 			$this->recordError( $error_msg );

	// 			sleep( 60 );
	// 	  		$this->streaming( $oauth_object, "", $match_text_list, "" );
	// 	    }
	// 	}
	// 	else {
	// 		// ソケット通信エラー
	// 		$error_msg = $errno . " " . $errstr;
	// 		$this->recordError( $error_msg );
	// 	}
	// }


	private function createOauthParams( $url, $method, $oauth_object, $get_parameters ) {

		$oauth_parameters = array(
		    'oauth_consumer_key' => $oauth_object->getConsumerKey(),
		    'oauth_nonce' => microtime(),
		    'oauth_signature_method' => 'HMAC-SHA1',
		    'oauth_timestamp' => time(),
		    'oauth_token' => $oauth_object->getAccessToken(),
		    'oauth_version' => '1.0',
		);
 
		// 署名を作る
		$a = null;
		if ( $get_parameters !== null && count($get_parameters) > 0 ) {
			$a = array_merge( $oauth_parameters, $get_parameters );
		}
		else {
			$a = $oauth_parameters;
		}

		ksort($a);
		$base_string = implode('&', array(
		    rawurlencode($method),
		    rawurlencode($url),
		    rawurlencode($this->http_build_query_rfc_3986($a, '&'))
		));
		$key = implode('&', array(rawurlencode($oauth_object->getConsumerSecret()), rawurlencode($oauth_object->getAccessTokenSecret())));
		$oauth_parameters['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $key, true));

		return $oauth_parameters;
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