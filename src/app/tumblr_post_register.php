<?php

include_once(dirname(__FILE__) . "/tumblr/tumblr_post_manager.php");
include_once(dirname(__FILE__) . "/database/database_manager.php");
include_once(dirname(__FILE__) . "/database/database_helper.php");


$tumblr_manager = new TumblrPostManager();

$database_manager = new DatabaseManager();
$database_manager->connect();


// temp
$blog_name = "koroazu";

// 最新のIDを取得
$post_id = DatabaseHelper::selectPostIdFromTumblrPost( $database_manager, $blog_name );

// 投稿一覧取得
$post_list = $tumblr_manager->createWithBlogName( $blog_name, $post_id );
$count = count($post_list);
echo "<p>画像枚数 : {$count}<p>";

// echo "<p>";
// var_dump($post_list);
// echo "<p>";

// databaseに登録
$count = 0;
foreach ($post_list as $post) {
	// 追加
	DatabaseHelper::insertTumblrPost( $database_manager, $post['blog_name'], $post['post_id'], $post['photo_url'] );
	$count++;
}
$database_manager->close();


echo "登録数:{$count}<p>";
echo "end";
?>