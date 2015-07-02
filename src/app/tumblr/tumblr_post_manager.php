<?php

include_once(dirname(__FILE__) . "/../../../define.php");
include_once(dirname(__FILE__) . "/tumblr_object.php");

class TumblrPostManager
{
	private $api_key = TUMBLR_API_KEY;

	private $offset = 0;
	private $limit = 50;
	private $post_list = array();

	private $host_suffix = ".tumblr.com";

	public function createWithBlogName( $blog_name, $last_post_id, $search_tags ) {

		$this->offset = 0;
		$host_name = $blog_name . $this->host_suffix;
		while( true ) {
			$api_url = "https://api.tumblr.com/v2/blog/{$host_name}/posts/photo?api_key={$this->api_key}&offset={$this->offset}&limit={$this->limit}";
			$json = @file_get_contents( $api_url );
			$obj = json_decode($json);
			// echo "{$api_url}";

			// 投稿画像のurlだけをとりだし配列に保存
			$posts = $obj->response->posts;
			foreach ($posts as $post) {
				$post_object = new TumblrPostObject();
				$post_object->initWithJson( $post );
				
				// 登録済みの投稿かチェック
				if ( $post_object->getId() == $last_post_id ) {
					return $this->post_list;
				}
				else {
					// 対象タグが入ってるやつだけ
					if ( $search_tags === null || $post_object->isIncludeTags( $search_tags ) ) {
				// var_dump($post_object->getTags());
						$urls = $post_object->getOriginalSizeUrls();
						foreach ($urls as $url) {
							$this->post_list[] = $this->createDetail( $post_object, $url );
						}
					}
				}
			}

			// test
			// break;

			$this->offset += count($posts);
			if ( count($posts) < $this->limit) {
				break;
			}

			print_r("\n..." . $this->offset);

		}

		// log
		// $post_t = $this->post_list;
		// var_dump($post_t[0]);

		return $this->post_list;

	}

	private function createDetail( $post_object, $url ){

		$res = array();
		$res['blog_name'] = $post_object->getBlogName();
		$res['post_id'] = $post_object->getId();
		$res['photo_url'] = $url;

		return $res;
	}
}

?>