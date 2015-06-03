<?php

include_once(dirname(__FILE__) . "/../define.php");

class TumblrPostManager
{
	private $api_key = TUMBLR_API_KEY;
	private $host = TUMBLR_HOST;

	private $offset = 0;
	private $limit = 50;
	private $post_list = array();

	public function create() {

		while( true ) {
			$api_url = "https://api.tumblr.com/v2/blog/{$this->host}/posts/photo?api_key={$this->api_key}&offset={$this->offset}&limit={$this->limit}";
			$json = @file_get_contents( $api_url );
			$obj = json_decode($json);
			// echo "{$api_url}";

			// 投稿画像のurlだけをとりだし配列に保存
			$posts = $obj->response->posts;
			foreach ($posts as $post) {
				$photos = $post->photos;
				foreach ($photos as $photo) {
					$this->post_list[count($this->post_list)] = $this->getDetail( $post, $photo );
				}
			}

			// test
			// break;

			$this->offset += count($posts);
			if ( count($posts) < $this->limit) {
				break;
			}

		}

		// log
		// $post_t = $this->post_list;
		// var_dump($post_t[0]);

		return $this->post_list;
	}


	private function getDetail( $post, $photo ){

		$res = array();
		$res['blog_name'] = $post->blog_name;
		$res['post_id'] = $post->id;
		$res['photo_url'] = $photo->original_size->url;

		return $res;
	}
}

?>