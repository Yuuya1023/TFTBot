<?php

class StreamingObject
{
	private $responseJson = null;

	public function init( $response ){
		$this->responseJson = json_decode($response, true);
	}


	public function isValidResponse(){
		return $this->responseJson && array_key_exists( "id", $this->responseJson );
	}

	public function isRetweeted(){
		// retweetedが使えないので一旦これで
		return preg_match( "/RT/u", $this->getText() );
	}

	public function isIncludeText( $match_text_list ){
		$match_text = implode("|", $match_text_list);
		return preg_match("/" . $match_text . "/u", $this->getText());
	}

	public function getId(){
		return $this->responseJson["id"];
	}

	public function getText(){
		return "" . $this->responseJson['text'];
	}

	public function getScreenName(){
		return $this->responseJson["user"]["screen_name"];
	}

	public function getMentionList(){
		// メンションリスト作成
		$mention_list = array();
		$mentions = $this->responseJson["entities"]["user_mentions"];
		for ($i=0; $i < count($mentions) ; $i++) { 
			$mention_list[count($mention_list)] = $mentions[$i]["screen_name"];
		}
		return $mention_list;
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