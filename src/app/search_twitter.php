<?php

// include_once(dirname(__FILE__) . "/post_to_twitter.php");
include_once(dirname(__FILE__) . "/twitter/oauth_object.php");
include_once(dirname(__FILE__) . "/twitter/twitter_post_manager.php");
include_once(dirname(__FILE__) . "/database/database_manager.php");
include_once(dirname(__FILE__) . "/database/database_helper.php");

/**
 * 探す
 *　↓
 *　DM文言作成
 *　↓
 *　送る
 *　↓
 *　最後のID保存
 */

class SearchTwitter
{

	public function search( $oauth_object ){

		$twitter_manager = new TwitterPostManager();
		$database_manager = new DatabaseManager();
		$database_manager->connect();

		$word_res = DatabaseHelper::selectFromTwitterSearchWord( $database_manager, $twitter_manager );
		foreach ($word_res as $o) {
			$this->notice( $database_manager, $twitter_manager, $oauth_object, $o );
		}
		
		$database_manager->close();
	}


	public function searchWithId( $oauth_object, $word_id ){

		$twitter_manager = new TwitterPostManager();
		$database_manager = new DatabaseManager();
		$database_manager->connect();

		$word_res = DatabaseHelper::selectFromTwitterSearchWordWithId( $database_manager, $twitter_manager, $word_id );
		$this->notice( $database_manager, $twitter_manager, $oauth_object, $word_res );
		
		$database_manager->close();
	}


	private function notice( $database_manager, $twitter_manager, $oauth_object, $word_res ) {

		if ( $word_res !== null ) {
			$word_id = $word_res["id"];
			$word = $word_res["word"];
			$notice_user = $word_res["notice_user"];
			$latest_tweet_id = $word_res["latest_tweet_id"];

			// 検索
			$search_result = $twitter_manager->search( $oauth_object, $word, 100, $latest_tweet_id );
			$statuses = $search_result->statuses;
			if ( $statuses > 0 ) {
				$latest_id = null;
				$current_date = date('Y年m月d日 H時i分');
				$direct_message_text = "search word:\n" . $word . "\nat:\n" . $current_date . "\n";
				for ($i=0; $i < count($statuses); $i++) { 
					$streaming_obj = new StreamingObject();
					$streaming_obj->initWithJson( $statuses[$i] );

					if ( !$streaming_obj->isRetweeted() ) {
						// デバッグ
						// $streaming_obj->displayTweet();
						print_r("\n" . $streaming_obj->getId() );

						// DMで送る文字列生成
						// $direct_message_text .= $streaming_obj->getText() . "\n" . $streaming_obj->generateTweetLink() . "\n\n";	// 文字数制限解除されたら
						// DM送信
						$t = $direct_message_text . $streaming_obj->generateTweetLink();
						$res = $twitter_manager->sendDirectMessage( $oauth_object, $notice_user, $t );
						// print_r($res);
					}

					if ( $i === 0 ) {
						$latest_id = $streaming_obj->getId();
					}
				}
				// print_r($direct_message_text);

				// DM送信
				// $res = $twitter_manager->sendDirectMessage( $oauth_object, $notice_user, $direct_message_text ); // 文字数制限解除されたら
				// print_r($res);

				// latest_id更新
				DatabaseHelper::updateLatestTweetId( $database_manager, $latest_id, $word_id );
			}
		}
	}
}

?>