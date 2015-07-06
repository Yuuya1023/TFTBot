<?php

class StreamingObject
{
	private $responseJson = null;

	public function init( $response ){
		$this->responseJson = json_decode($response);
		if ( gettype($this->responseJson) === "integer" || gettype($this->responseJson) === "double" ) 
			$this->responseJson = null;
	}

	public function initWithJson( $json ){
		$this->responseJson = $json;
	}

	public function getJson(){
		return $this->responseJson;
	}

	public function isValidResponse(){
		return $this->responseJson !== null && array_key_exists( "id", $this->responseJson );
	}

	public function isRetweeted(){
		// RTから始まってるやつはリツイート
		return preg_match( "/^RT/u", $this->getText() );
	}

	public function isIncludeRT(){
		return preg_match( "/RT/u", $this->getText() );
	}

	public function isIncludeText( $match_text_list ){
		if ( $match_text_list === null || count($match_text_list) === 0 ) return false;
		$match_text = implode("|", $match_text_list);
		return preg_match("/" . $match_text . "/u", $this->getText());
	}

	public function isIncludeHashtag( $tag ){
		if ( $tag === null || $tag === "" ) return false;

		$tags = $this->getHashtags();
		foreach ($tags as $t) {
			if ( strcmp( $t->text, $tag ) === 0 ) {
				return true;
			}
		}
		return false;
	}


	public function getId(){
		return $this->responseJson->id;
	}

	public function getText(){
		return "" . $this->responseJson->text;
	}

	public function getUserId(){
		return $this->responseJson->user->id;
	}

	public function getScreenName(){
		return $this->responseJson->user->screen_name;
	}

	public function getMentionList(){
		// メンションリスト作成
		$mention_list = array();
		$mentions = $this->getUserMentions();
		foreach ($mentions as $mention) {
			$mention_list[] = $mention->screen_name;
		}
		return $mention_list;
	}

	public function getHashtags(){
		return $this->responseJson->entities->hashtags;
	}

	public function getUserMentions(){
		return $this->responseJson->entities->user_mentions;
	}


	public function generateTweetLink(){
		return "https://twitter.com/" . $this->getScreenName() . "/status/" . $this->getId();
	}


	public function displayTweet(){
		print_r("\n");
		print_r($this->getId() . " @" . $this->getScreenName() . "\n" . $this->getText() );

		print_r("\n");
	}

	public function displayDetail( $match_text_list ){
		print_r("\n");
		print_r("@" . $this->getScreenName() . " " . $this->getText() );

		print_r("\n");
		print_r("Retweeted->");
		if ( $this->isRetweeted() ) print_r("true");
		else  print_r("false");

		print_r(", IncludeText->");
		if ( $this->isIncludeText( $match_text_list ) ) print_r("true!!!!!!");
		else  print_r("false");

		print_r("\n");
	}

}

?>