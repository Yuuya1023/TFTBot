<?php

class StreamingObject
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

	public function isValidResponse(){
		return $this->responseJson && array_key_exists( "id", $this->responseJson );
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
		$mentions = $this->responseJson->entities->user_mentions;
		// for ($i=0; $i < count($mentions) ; $i++) { 
		// 	$mention_list[count($mention_list)] = $mentions[$i]["screen_name"];
		// }
		foreach ($mentions as $mention) {
			$mention_list[] = $mention->screen_name;
		}
		return $mention_list;
	}


	public function generateTweetLink(){
		return "https://twitter.com/" . $this->getScreenName() . "/status/" . $this->getId();
	}


	public function displayTweet(){
		print_r("\n");
		print_r($this->getId() . " @" . $this->getScreenName() . "\n" . $this->getText() );

		print_r("\n");
		print_r("Retweeted->");
		if ( $this->isRetweeted() ) print_r("true");
		else  print_r("false");

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