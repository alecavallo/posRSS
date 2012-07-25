<?php
require_once 'extensions/facebook/src/facebook.php';
$config = array ();
$config ['appId'] = '122010617872934';
$config ['secret'] = '9ded61b4c40c0e6baf4b6ab5b52c4746';
$config ['fileUpload'] = true; // optional
$facebook = new Facebook ( $config );
$accessToken = "AAACnABbWnVkBADafGhkXse8YCZBOoZBBl3sdhXl0Y8zl7zGzEWKCimuUa6IbB8Clz0pTT0YPrfmErdT3Y0pZB468uHsUPs59DiZBuSvhOAZDZD";
$ret_obj = $facebook->api ( '/sancorsalud/feed', 'GET', array ('access_token'=>$accessToken ) );

var_dump($ret_obj);
?>
