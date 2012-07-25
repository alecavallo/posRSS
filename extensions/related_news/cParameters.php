<?php
require_once '../../configure.php';
require_once '../../connector.php';
class cParameters {
	private $db;

	function __construct(){
		try {
			$this->db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	function __destruct(){
		unset($this->db);
	}

	function GetParameter($Key){
		// Conexión a la base
		//$link = mysql_connect ( "localhost", "root" );
		//mysql_select_db ( "posteamos", $link );

		$returnValue = "";
		$sql = "select * from parameters where `key` = '$Key'; ";
		$result = $this->db->query($sql);
		if ($result != false) {
				if ($reg = mysql_fetch_array($result)) {
		 			$returnValue = $reg["value"];
				}
				mysql_free_result($result);
				return $returnValue;
		}else{
			return null;
		}
	}

}
?>