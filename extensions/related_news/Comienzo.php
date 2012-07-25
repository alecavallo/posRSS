<?php
	mb_internal_encoding("UTF-8");
	require_once("cNewsAnalyzer.php");

	$oNews  = new cNewsAnalyzer();
	$oNews->Process();

?>
