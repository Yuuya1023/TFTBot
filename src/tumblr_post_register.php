<?php

include_once(dirname(__FILE__) . "/tumblr/tumblr_post_manager.php");
include_once(dirname(__FILE__) . "/database/database_manager.php");
include_once(dirname(__FILE__) . "/database/database_helper.php");


$tumblr_manager = new TumblrPostManager();
$post_list = $tumblr_manager->create();
$count = count($post_list);
echo "<p>画像枚数 : {$count}";

$database_manager = new DatabaseManager();
$database_manager->connect();
foreach ($post_list as $post) {
	// 追加
	DatabaseHelper::insertTumblrPost( $database_manager, $post['blog_name'], $post['post_id'], $post['photo_url'] );
}
$database_manager->close();


echo "<p>end";
?>