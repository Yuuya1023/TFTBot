<?php

class OauthObject
{
	private $consumerKey = null;
	private $consumerSecret = null;
	private $accessToken = null;
	private $accessTokenSecret = null;

	public function init( $consumerKey, $consumerSecret, $accessToken, $accessTokenSecret ){

		$this->consumerKey = $consumerKey;
		$this->consumerSecret = $consumerSecret;
		$this->accessToken = $accessToken;
		$this->accessTokenSecret = $accessTokenSecret;
	}

	public function getConsumerKey(){
		return $this->consumerKey;
	}
	public function getConsumerSecret(){
		return $this->consumerSecret;
	}
	public function getAccessToken(){
		return $this->accessToken;
	}
	public function getAccessTokenSecret(){
		return $this->accessTokenSecret;
	}

}

?>