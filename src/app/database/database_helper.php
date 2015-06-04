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

	// tumblr_postからランダムにひとつ投稿を取得
	public static function selectRandomTumblrPost( $database_manager, $blog_name ){

		$query = "SELECT * FROM tumblr_post WHERE blog_name = '" . $blog_name . "' ORDER BY RAND() LIMIT 1";
		// echo "<p>{$query}";
		return $database_manager->select( $query );
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
	public static function insertTwitterPostLog( $database_manager, $tumblr_post_id, $error_msg ){

		$query = "INSERT INTO twitter_post_log (tumblr_post_id, error_msg) VALUES (" . $tumblr_post_id . ", '" . $error_msg . "')";
		return $database_manager->insert( $query );
	}
}