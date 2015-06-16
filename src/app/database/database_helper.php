<?php

include_once(dirname(__FILE__) . "/database_manager.php");

class DatabaseHelper{
	
	// tumblr_postの最新のIDを取得
	public static function selectPostIdFromTumblrPost( $database_manager, $blog_name ) {

		$query = "SELECT post_id FROM tumblr_post WHERE blog_name = '" . $blog_name . "' ORDER BY post_id DESC LIMIT 1";
		echo "<p>{$query}";
		$res = $database_manager->select( $query );

		if ( count($res) < 1 ) {
			return -1;	
		}

		return $res[0]['post_id'];
	}

	// tumblr_postに投稿を登録
	public static function insertTumblrPost( $database_manager, $blog_name, $post_id, $photo_url ){

		$query = "INSERT INTO tumblr_post (blog_name, post_id, photo_url) VALUES ('" . $blog_name . "', ". $post_id . ", '" . $photo_url . "')";
		// echo "<p>{$query}";
		return $database_manager->insert( $query );
	}

	// ランダムで画像urlを取得
	public static function selectRandomPhotoUrlFromTumblrPost( $database_manager, $blog_name ){

		$query = "SELECT photo_url FROM tumblr_post WHERE blog_name = '" . $blog_name . "' ORDER BY RAND() LIMIT 1";
		// echo "<p>{$query}";
		$res = $database_manager->select( $query );
		if (count($res) === 0) {
			return null;
		}
		return $res[0]["photo_url"];
	}

	// tumblr_postからランダム投稿を取得
	public static function selectRandomTumblrPost( $database_manager, $blog_name, $limit_row ){

		// ログの最新10件をランダム取得から除外
		$id_list;
		{
			$query = "SELECT tumblr_post_id FROM twitter_post_log WHERE blog_name = '" . $blog_name . "' AND tumblr_post_id != -1 ORDER BY id DESC LIMIT 20";
			echo "<p>{$query}";
			$id_list = $database_manager->select( $query );
		}

		$exclusionQuery = "";
		if (count($id_list) > 0) {
			$ids = array(); 
			for ($i=0; $i < count($id_list); $i++) { 
				$ids[count($ids)] = $id_list[$i]["tumblr_post_id"];
			}

			$exclusionQuery = " AND id NOT IN (" . implode( ",", $ids) . ")";
		}

		$query = "SELECT * FROM tumblr_post WHERE blog_name = '" . $blog_name . "' " . $exclusionQuery . " ORDER BY RAND() LIMIT " . $limit_row;
		// echo "<p>{$query}";

		$res = $database_manager->select( $query );
		if ( count($res) < $limit_row ) {
			$query = "SELECT * FROM tumblr_post WHERE blog_name = '" . $blog_name . "' ORDER BY RAND() LIMIT " . $limit_row;
			// echo "<p>{$query}";
			return $database_manager->select( $query );
		}
		
		return $res;
	}

	// twitter_postからidを元に投稿テキストを取得
	public static function selectPostTextFromTwitterPost( $database_manager, $twitter_post_id ){

		$query = "SELECT post_text FROM twitter_post WHERE id = " . $twitter_post_id . "";
		$res = $database_manager->select( $query );
		return $res[0]['post_text'];
	}

	// twitter_postに投稿を登録
	public static function insertTwitterPost( $database_manager, $twitter_post_id, $post_text, $image_url ){

		$query = "INSERT INTO twitter_post (post_id, post_text, image_url) VALUES (" . $twitter_post_id . ", '" . $post_text . "', '" . $image_url . "')";
		return $database_manager->insert( $query );
	}

	// tumblr_postのtwitter_post_idを更新
	public static function updateTumblrPostSetTwitterPostId( $database_manager, $twitter_post_id, $tumblr_post_id ){
				
		$sub_query = "SELECT id FROM twitter_post WHERE post_id = " . $twitter_post_id . "";
		$query = "UPDATE tumblr_post SET twitter_post_id = (" . $sub_query . ") WHERE id = " . $tumblr_post_id . "";
		// echo "<p>debug<p>";
		// echo "{$query}";
		return $database_manager->insert( $query );
	}

	// twitter_post_logに登録
	public static function insertTwitterPostLog( $database_manager, $blog_name, $tumblr_post_id, $error_msg ){

		$query = "INSERT INTO twitter_post_log (blog_name, tumblr_post_id, error_msg) VALUES ('" . $blog_name . "', " . $tumblr_post_id . ", '" . $error_msg . "')";
		return $database_manager->insert( $query );
	}

	// twitter_post_logの削除
	public static function deleteTwitterPostLog( $database_manager ){

		$query = "DELETE FROM twitter_post_log WHERE posted_at < current_date";
		return $database_manager->insert( $query );
	}

	// auto_reply_logに登録
	public static function insertAutoReplyLog( $database_manager, $blog_name, $error_msg ){

		$query = "INSERT INTO auto_reply_log (blog_name, error_msg) VALUES ('" . $blog_name . "', '" . $error_msg . "')";
		return $database_manager->insert( $query );
	}

	// auto_reply_logから15分前までの投稿数を取得
	public static function selectCountFromAutoReplyLog( $database_manager ){

		$query = "SELECT count(*) AS count  FROM auto_reply_log WHERE posted_at > addtime(now(),'-00:15:00')";
		$res = $database_manager->select( $query );
		return $res[0]['count'];
	}

	// auto_reply_logの削除
	public static function deleteAutoReplyLog( $database_manager ){

		$query = "DELETE FROM auto_reply_log WHERE posted_at < current_date";
		return $database_manager->insert( $query );
	}

}