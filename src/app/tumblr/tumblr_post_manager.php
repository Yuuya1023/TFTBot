<?php

include_once(dirname(__FILE__) . "/../../../define.php");

class TumblrPostManager
{
	private $api_key = TUMBLR_API_KEY;

	private $offset = 0;
	private $limit = 50;
	private $post_list = array();

	private $host_suffix = ".tumblr.com";

	public function createWithBlogName( $blog_name, $last_post_id ) {

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
				$photos = $post->photos;
				
				// 登録済みの投稿かチェック
				if ( $post->id == $last_post_id ) {
					return $this->post_list;
				}
				else {
					foreach ($photos as $photo) {
						$detail = $this->getDetail( $post, $photo );
						$this->post_list[count($this->post_list)] = $detail;
					}	
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