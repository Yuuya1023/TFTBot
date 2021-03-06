<?php

define("CONSUMER_KEY", '');
define("CONSUMER_SECRET", '');
define("OAUTH_TOKEN", '');
define("OAUTH_SECRET", '');

define("BLOG_NAME", 'koroazu.tumblr.com');
 
function oauth_gen($method, $url, $iparams, &$headers) {
     
    $iparams['oauth_consumer_key'] = CONSUMER_KEY;
    $iparams['oauth_nonce'] = strval(time());
    $iparams['oauth_signature_method'] = 'HMAC-SHA1';
    $iparams['oauth_timestamp'] = strval(time());
    $iparams['oauth_token'] = OAUTH_TOKEN;
    $iparams['oauth_version'] = '1.0';
    $iparams['oauth_signature'] = oauth_sig($method, $url, $iparams);
    print $iparams['oauth_signature'];  
    $oauth_header = array();
    foreach($iparams as $key => $value) {
        if (strpos($key, "oauth") !== false) { 
           $oauth_header []= $key ."=".$value;
        }
    }
    $oauth_header = "OAuth ". implode(",", $oauth_header);
    $headers["Authorization"] = $oauth_header;
}
 
function oauth_sig($method, $uri, $params) {
     
    $parts []= $method;
    $parts []= rawurlencode($uri);
    
    $iparams = array();
    ksort($params);
    foreach($params as $key => $data) {
            if(is_array($data)) {
                $count = 0;
                foreach($data as $val) {
                    $n = $key . "[". $count . "]";
                    $iparams []= $n . "=" . rawurlencode($val);
                    $count++;
                }
            } else {
                $iparams[]= rawurlencode($key) . "=" .rawurlencode($data);
            }
    }
    $parts []= rawurlencode(implode("&", $iparams));
    $sig = implode("&", $parts);
    return base64_encode(hash_hmac('sha1', $sig, CONSUMER_SECRET."&". OAUTH_SECRET, true));
}
 
// $img_dir = $post_caption = $post_body = $post_title = $_REQUEST['image_path'];
$LOCAL_DIR=dirname(__FILE__) . "/image/";
 
// if($_REQUEST['url'] != ''){
//     $post_caption = '<a href="'.$_REQUEST['url'].'"> '.$post_caption.'</a>';
// }
// $TAGS = $_REQUEST['tags'];
// $post_tags=mb_convert_encoding($TAGS,'UTF-8','auto');
$drc=dir($LOCAL_DIR);
while($fl=$drc->read()){
    // echo __LINE__."<br />¥n";
    $din=pathinfo($LOCAL_DIR.$fl);
 
    $filename=$LOCAL_DIR.$din['basename'];
    echo "<p><p>";
    echo $filename;
    echo "<p><p>";
 
    //.拡張子より短いファイル名をスキップ
    if(strlen($din['basename'])<=4 or $din['basename']  == ".DS_Store"){
        continue;
    }
 
    $headers = array("Host" => "http://api.tumblr.com/", "Content-type" => "application/x-www-form-urlencoded", "Expect" => "");
    $params = array("data" => array(file_get_contents($filename)),
        "type"  => "photo",
        "title" => "",//$post_title,
        // "tags"  => "",//$post_tags,
        // "caption" => $post_caption,
    );
     
    $blogname = BLOG_NAME;
    oauth_gen("POST", "http://api.tumblr.com/v2/blog/$blogname/post", $params, $headers);
     
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, "PHP Uploader Tumblr v1.0");
    curl_setopt($ch, CURLOPT_URL, "http://api.tumblr.com/v2/blog/$blogname/post");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
     
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: " . $headers['Authorization'],
        "Content-type: " . $headers["Content-type"],
        "Expect: ")
    );
     
    $params = http_build_query($params);
     
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
     
    $response = curl_exec($ch);
    $status=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
            echo "curl_error($c)\n";
    }
    echo $response;

    curl_close($ch);
}