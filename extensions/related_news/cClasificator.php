<?php


Class cClasificator {	
	
	// Peso que tiene el titulo al calcular el porcentaje total de coincidencias
	var $titleWeight;
	//Peso que tiene el copete al calcular el porcentaje total de coincidencias
	var $summaryWeight;
	//Peso que tiene el cuerpo al calcular el porcentaje total de coincidencias
	var $bodyWeight;
	
	function ClasificateNews($pNews1,$pNews2) {}
	
	function ClasificateText($Text1,$Text1Len, $Text2,$Text2Len){}		
}

?>