<?php

//セッションスタート
session_start();

//OAuthリクエスト用の関数
function tumblr_oauth($request_url,$method,$parameters){
    $consumer_key = "";  //コンシューマーキー
    $secret_key = "";   //シークレットキー
    $callback_url = "";  //このプログラムのURL

    global $http_response_header;
    $oauth_parameters = array(
        // "oauth_callback" => $callback_url,
        "oauth_consumer_key" => $consumer_key,
        "oauth_nonce" => microtime(),
        "oauth_signature_method" => "HMAC-SHA1",
        "oauth_timestamp" => time(),
        "oauth_version" => "1.0"
    );
    if(isset($parameters["oauth_token"])){$oauth_parameters["oauth_token"] = $parameters["oauth_token"];unset($parameters["oauth_token"],$oauth_parameters["oauth_callback"]);}
    if(isset($parameters["oauth_token_secret"])){$oauth_token_secret = $parameters["oauth_token_secret"];unset($parameters["oauth_token_secret"]);}else{$oauth_token_secret = "";}
    if(isset($parameters["oauth_verifier"])){$oauth_parameters["oauth_verifier"] = $parameters["oauth_verifier"];unset($parameters["oauth_verifier"]);}
    // $tail = ($method === "GET") ? "?".http_build_query($parameters,"","&",PHP_QUERY_RFC3986) : "";
    $tail = ($method === "GET") ? "?".http_build_query_rfc_3986($parameters) : "";
    $all_parameters = array_merge($oauth_parameters,$parameters);
    ksort($all_parameters);
    // $base_string = implode("&",array(rawurlencode($method),rawurlencode($request_url),rawurlencode(http_build_query($all_parameters,"","&",PHP_QUERY_RFC3986))));
    $base_string = implode("&",array(rawurlencode($method),rawurlencode($request_url),rawurlencode(http_build_query_rfc_3986($all_parameters))));
    $key = implode("&",array(rawurlencode($secret_key),rawurlencode($oauth_token_secret)));
    $oauth_parameters["oauth_signature"] = base64_encode(hash_hmac("sha1", $base_string, $key, true));
    // $data = array("http"=>array("method" => $method,"header" => array("Authorization: OAuth ".http_build_query($oauth_parameters,"",",",PHP_QUERY_RFC3986),),));
    $data = array("http"=>array("method" => $method,"header" => array("Authorization: OAuth ".http_build_query_rfc_3986($oauth_parameters, ","),),));
    if($method !== "GET") $data["http"]["content"] = http_build_query($parameters);
    $data = @file_get_contents($request_url.$tail,false,stream_context_create($data));

    return $data ? $data : false;
}
 
//GETクエリ形式の文字列を配列に変換する関数
function get_query($data){
    $ary = explode("&",$data);
    foreach($ary as $items){
        $item = explode("=",$items);
        $query[$item[0]] = $item[1];
    }
    return $query;
}

function http_build_query_rfc_3986($query_data,$arg_separator='&')
{
    $r = '';
    $query_data = (array) $query_data;
    if(!empty($query_data))
    {
        foreach($query_data as $k=>$query_var)
        {
            $r .= $arg_separator;
            $r .= $k;
            $r .= '=';
            $r .= rawurlencode($query_var);
        }
    }
    return trim($r,$arg_separator);

}

//アクセストークンを取得
if(isset($_GET["oauth_token"]) && is_string($_GET["oauth_token"]) && !empty($_GET["oauth_token"]) && isset($_GET["oauth_verifier"]) && is_string($_GET["oauth_verifier"]) && !empty($_GET["oauth_verifier"])){

    //アクセストークンをリクエストする
    $query = get_query(tumblr_oauth("http://www.tumblr.com/oauth/access_token","POST",array("oauth_token"=>$_GET["oauth_token"],"oauth_token_secret"=>$_SESSION["oauth_token_secret"],"oauth_verifier" => $_GET["oauth_verifier"])));
    
    //セッション終了
    $_SESSION = array();
    session_destroy();
    
    //エラー判定
    if(!$query){
        echo "<p>アクセストークンの取得に失敗しました…。</p>";
        exit;
    }
 
    //情報の整理
    $token = $query["oauth_token"];
    $secret = $query["oauth_token_secret"];
    
    //出力する
    echo "<p>あなたのアクセストークンは<mark>{$token}</mark>で</p><p>アクセストークンシークレットは<mark>{$secret}</mark>です！</p>";
    exit;
}
    
//リクエストトークンの取得
if(!$query = get_query(tumblr_oauth("http://www.tumblr.com/oauth/request_token","POST",array()))){
    echo "<p>リクエストトークンの取得に失敗しました…。もしかしたら「$consumer_key」「$secret_key」「$callback_url」の設定が違っているかもしれません…。</p>";
    exit;
}
    
//セッションに保存
session_regenerate_id(true);
$_SESSION["oauth_token_secret"] = $query["oauth_token_secret"];
    
// //認証画面へリダイレクト
header("Location: http://www.tumblr.com/oauth/authorize?oauth_token=".$query["oauth_token"]);

echo "a";
exit;