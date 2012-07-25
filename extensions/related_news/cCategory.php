<?php

class cCategory {

	var $CategoryID;



	function GetCategories($db) {
		// Conexión a la base
		if (empty($db)){
			throw new Exception("No se seteó ninguna base de datos", "200");
		}
		// Armo la consulta
		$sql = "Select * from categories where 1 = 1";
		$result = $db->query($sql);
		$i = 0;
		while (($reg = mysql_fetch_array($result)) == true){
			$oCategory = new cCategory();
			$oCategory ->Load($reg);
			$ArrayRetorno[] = $oCategory;
			//$i = $i + 1;
		}
		return $ArrayRetorno;
	}

	function Load($assocArray) {
		$this->CategoryID = $assocArray ["id"];
	}

}

?>