<?php
function slug($text, $replacement="-") {
	/*$encoding = mb_detect_encoding($text);
	$text = mb_convert_encoding($text, 'ISO')*/
	$return = preg_replace("/[á|à|ä|â]/iu", "a", $text);
	$return = preg_replace("/[é|è|ë|ê]/iu", "e", $return);
	$return = preg_replace("/[í|ì|ï|î]/iu", "i", $return);
	$return = preg_replace("/[ó|ò|ö|ô]/iu", "o", $return);
	$return = preg_replace("/[ú|ù||ü|û]/iu", "a", $return);
	$return = preg_replace("/[^\w\s]/u", "", $return);
	$return = preg_replace("/\s/u", "-", $return);
	$return = strtolower($return);
	return $return;
}