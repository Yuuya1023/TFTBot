<?php

class TumblrPostObject
{
	private $responseJson = null;

	public function init( $response ){
		$this->responseJson = json_decode($response);
	}

	public function initWithJson( $json ){
		$this->responseJson = $json;
	}


	public function getJson(){
		return $this->responseJson;
	}


	public function getBlogName() {
		return $this->responseJson->blog_name;
	}

	public function getId() {
		return $this->responseJson->id;
	}

	public function getTags() {
		return $this->responseJson->tags;
	}

	public function isIncludeTag( $tag ){
		if ( $tag === null || $tag === "" ) return false;

		$tags = $this->getTags();
		foreach ($tags as $t) {
			if ( strcmp( $t, $tag ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	public function isIncludeTags( $tags ){
		if ( $tags === null || count($tags) === 0 ) return false;

		foreach ($tags as $t) {
			if ( $this->isIncludeTag( $t ) ) return true;
		}
		return false;
	}

	public function getPhotos() {
		return $this->responseJson->photos;
	}

	public function getOriginalSizeUrls() {
		$res = array();
		$photos = $this->getPhotos();
		foreach ( $photos as $photo ) {
			$res[] = $this->getOriginalSizeUrl( $photo );
		}
		return $res;
	}



	public function getOriginalSizeUrl( $photo ) {
		return $photo->original_size->url;
	}
}

?>