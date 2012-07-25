<?php

require_once("cMatchClasificator.php");
require_once("cPopularWordsClasificator.php");
require_once("cNews.php");
require_once("cCategory.php");
require_once("cParameters.php");
require_once '../../configure.php';
require_once '../../connector.php';

class cNewsAnalyzer {

	// Porcentajes para calificar noticias
	var $percentageDuplicateNews;
	var $percentageRelatedNews;
	var $percentageUnrelatedNews;
	// Objeto que clasifica noticias por contenido
	var $oMatchClasificator;
	// Objeto que clasifica noticias por palabras populares
	var $oPopularWordsClasificator;

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

	function Process() {

		//Para obtener los parametros
		$oParameter = new cParameters();

		// Cargo parámetros en la clase. Clasificacion de noticias:repetidas, duplicadas
		$this->percentageDuplicateNews = $oParameter->GetParameter("percentageDuplicateNews");
		$this->percentageRelatedNews = $oParameter->GetParameter("percentageRelatedNews");
		$this->percentageUnrelatedNews = $oParameter->GetParameter("percentageUnrelatedNews");

		// Creo el clasificador por coincidencias. Los distintos pesos del titulo, copete y cuerpo
		$this->oMatchClasificator = new cMatchClasificator();
		$this->oMatchClasificator->bodyWeight = $oParameter->GetParameter("bodyWeight");
		$this->oMatchClasificator->summaryWeight = $oParameter->GetParameter("summaryWeight");
		$this->oMatchClasificator->titleWeight = $oParameter->GetParameter("titleWeight");

		// Creo el clasificador por palabras populares
		$this->oPopularWordsClasificator = new cPopularWordsClasificator();

		// Obtiene todas las categorias
		$oCategories  = cCategory::GetCategories($this->db);

		// Recorre cada una de las categorias
		foreach($oCategories as $oCategory) {

			// Obtengo noticias todavia no relacionadas de esta categoria
			$oNotRelatedNews = cNews::GetNews($this->db,$oCategory->CategoryID,"0",0);

			// Depuro las palabras sin relevancia
			foreach($oNotRelatedNews as $oNotRelatedNew){
				$oNotRelatedNew->SummarizeNews();
			}

			// Obtengo noticias ya relacionadas de esta categoria
			// Mejora: obtener las noticias paginadas
			$fromDate = strtotime("-1 months");
			$fromDate = date("Y-m-d", $fromDate);
			$oRelatedNews = cNews::GetNews($this->db,$oCategory->CategoryID,"1",$fromDate);

			// Comienzo recorrido de noticias ya relacionadas para relacionarlas con las NO relacionadas...
			for($i=0; $i<count($oRelatedNews); $i++){
				$oRelated =  $oRelatedNews[$i];
				// Recorro NO relacionadas
				for($j=0; $j<count($oNotRelatedNews); $j++) {
					$oNotRelated =$oNotRelatedNews[$j];
					$this->AnalizeNews($oRelated,$oNotRelated);
				}
			} // fin recorrido noticias ya relacionadas

			// Ahora comparo las no relacionadas, porque entre si todavia no se relacionaron
			for($i=0; $i<count($oNotRelatedNews); $i++) {
				$oNotRelated1 =$oNotRelatedNews[$i];
				for($j= ($i + 1); $j<count($oNotRelatedNews); $j++) {
					$oNotRelated2=$oNotRelatedNews[$j];
					$this->AnalizeNews($oNotRelated1,$oNotRelated2);
				}
				// Ya se relaciono con todas las noticias, seteo IsRelated en 1
				$oNotRelated1->SetIsRelated($this->db,1);
			}
		} // Fin de recorrer categorias
	}

	function AnalizeNews($oNews1, $oNews2) {

		$value1 = $this->oMatchClasificator->ClasificateNews($oNews1, $oNews2);
		$value2 = $this->oPopularWordsClasificator->ClasificateNews($oNews1, $oNews2);
		$finalValue = ($value1 + $value2) / 2;

		if ($finalValue > $this->percentageDuplicateNews){
			// Duplicada
			$oNews1->SetRelation($this->db, $oNews2,1);
			$oNews2->SetRelation($this->db, $oNews1,1);
			echo "[DUPLICADA] #{$oNews1->newsID} -> #{$oNews2->newsID}: ".(round($finalValue*100,2))."%\n";
		}elseif($finalValue > $this->percentageRelatedNews){
			//Relacionada
			$oNews1->SetRelation($this->db, $oNews2,2);
			$oNews2->SetRelation($this->db, $oNews1,2);
			echo "[RELACIONADA] #{$oNews1->newsID} -> #{$oNews2->newsID} ".(round($finalValue*100,2))."%\n";
		}else {
			//No relacionada
		}
	}

}

?>