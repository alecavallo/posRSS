<?php
class cTrivialWords {

	function WordsToDelete() {
		return array("EL","LA","LOS","LAS","LO","LE","LES","AL","ES","SE","A","ANTE","BAJO","CABE","CON",
			"CONTRA","DE","DESDE","DURANTE","EN","ENTRE",
			"HACIA","HASTA","MEDIANTE","PARA","POR","SEGUN",
			"SO","SOBRE","TRAS","VIA","QUE","Y","O");
	}

	function SignsToDelete() {
		return array(",", ".", ";", "\"", "?", "'", "!",":","•");
	}
}

?>