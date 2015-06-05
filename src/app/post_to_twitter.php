<?php

include_once(dirname(__FILE__) . "/database/database_manager.php");
include_once(dirname(__FILE__) . "/database/database_helper.php");
include_once(dirname(__FILE__) . "/twitter/twitter_post_manager.php");

/**
* tumblr_postからランダム取得
* ↓
* twiiter_post_idが
* →null
*  twitterにphoto_urlの画像をアップロード
*  結果をtwitter_postに登録し、tumblr_postのtwitter_post_idに登録
* →値あり
*  twitter_postからツイート内容取得してそのままツイート
* ↓
* ログに保存
*/
class PostToTwitter
{

	public function post( $oauth_object, $blog_name ){

		// status is a duplicate対策で三回までトライ
		for ($i=0; $i < 3; $i++) { 
			$is_success = $this->tryPost( $oauth_object, $blog_name );

			if ( $is_success === true ) {
				return;
			}
		}
	}

	private function tryPost( $oauth_object, $blog_name ){

		// ログに保存するための変数
		$twitter_post_id = "";
		$error_msg = "";
		// 投稿後にレコードを更新するためのid
		$tumblr_post_id = -1;

		$database_manager = new DatabaseManager();
		$database_manager->connect();

		$twitter_manager = new TwitterPostManager();

		// 投稿するツイートをランダムで一件取得
		$tum_res = DatabaseHelper::selectRandomTumblrPost( $database_manager, $blog_name, 1 );
		if ( $tum_res && count( $tum_res ) > 0 ) {
			// echo "<p>";
			// var_dump($tum_res);
			// print($tum_res[0]['id']);
			// print($tum_res[0]['blog_name']);
			// print($tum_res[0]['photo_url']);
			// echo "<p>";

			$tumblr_post_id = $tum_res[0]['id'];

			$temp_twitter_post_id = $tum_res[0]['twitter_post_id'];
			if ( $temp_twitter_post_id ) {
				// 投稿文言を取得してそのまま投稿
				$post_text = DatabaseHelper::selectPostTextFromTwitterPost( $database_manager, $temp_twitter_post_id );

				// 投稿
				$upload_result = $twitter_manager->uploadText( $oauth_object, $post_text );

				if ( array_key_exists( "errors", $upload_result ) ) {
					$errors = $upload_result->errors;
					foreach ($errors as $error) {
						$error_msg = $error->code . " " . $error->message . ", " . $error_msg;
					}
				}

				$twitter_post_id = $temp_twitter_post_id;
			}
			else {
				// 画像をアップロードしてそのまま投稿
				$photo_url_list = array();
				$photo_url_list[0] = $tum_res[0]['photo_url'];
				$upload_result = $twitter_manager->uploadImage( $oauth_object, $photo_url_list );

				if ( array_key_exists( "errors", $upload_result ) ) {
					$errors = $upload_result->errors;
					foreach ($errors as $error) {
						$error_msg = $error->code . " " . $error->message . ", " . $error_msg;
					}
				}
				else {
					$twitter_post_id = $upload_result->id;
					// twitter_postにレコード追加
					{
						$post_text = $upload_result->text;
						$image_url = $upload_result->entities->media[0]->url;
						// echo "<p>{$post_text},{$image_url}";
						DatabaseHelper::insertTwitterPost( $database_manager, $twitter_post_id, $post_text, $image_url );
					}

					// 追加したレコードのid取得
					// tumblr_postの指定レコードにtwitter_post_idを保存
					DatabaseHelper::updateTumblrPostSetTwitterPostId( $database_manager, $twitter_post_id, $tumblr_post_id );
				}

			}
		}

		// echo "<p>debug<p>";
		// echo "<p>{$error_msg}";
		// echo "<p>{$tumblr_post_id}";


		// ログに保存
		DatabaseHelper::insertTwitterPostLog( $database_manager, $blog_name, $tumblr_post_id, $error_msg );
		$database_manager->close();

		echo "tryend<p>";

		if ( $error_msg !== "" && $tumblr_post_id !== -1 ) {
			return false;
		}

		return true;
	}

	public function postFourImages( $oauth_object, $blog_name ){

		// ログに保存するための変数
		$error_msg = "";

		$database_manager = new DatabaseManager();
		$database_manager->connect();

		$twitter_manager = new TwitterPostManager();

		// 投稿するツイートをランダムで4件取得
		$tum_res = DatabaseHelper::selectRandomTumblrPost( $database_manager, $blog_name, 4 );
		if ( $tum_res && count( $tum_res ) > 0 ) {

			// 画像をアップロードしてそのまま投稿
			$tumblr_post_id_list = array();
			$photo_url_list = array();
			foreach ($tum_res as $res) {
				$index = count($photo_url_list);
				$tumblr_post_id_list[$index] = $res['id'];
				$photo_url_list[$index] = $res['photo_url'];
			}
			$upload_result = $twitter_manager->uploadImage( $oauth_object, $photo_url_list );

			if ( array_key_exists( "errors", $upload_result ) ) {
				$errors = $upload_result->errors;
				foreach ($errors as $error) {
					$error_msg = $error->code . " " . $error->message . ", " . $error_msg;
				}
			}
		}

		// ログに保存
		foreach ($tumblr_post_id_list as $id) {
			DatabaseHelper::insertTwitterPostLog( $database_manager, $blog_name, $id, $error_msg );
		}
		$database_manager->close();

		echo "end<p>";

		if ( $error_msg !== "" ) {
			return false;
		}

		return true;
	}
}
?>