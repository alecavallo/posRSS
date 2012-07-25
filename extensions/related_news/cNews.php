<?php
require_once 'cTrivialWords.php';

class cNews {

	// Id de la noticia
	var $newsID;

	//Titulo
	var $title;
	var $summarizedTitle;
	var $summarizedTitleLength;

	//Copete
	var $summary;
	var $summarizedSummary;
	var $summarizedSummaryLength;

	//Cuerpo
	var $body;
	var $summarizedBody;
	var $summarizedBodyLength;

	function GetNews($db, $pCategoria = 0, $isRelated = "", $pFromDate = "") {


		//Array de resultado
		$ArrayRetorno = array();

		// Armo la consulta
		$sql = " Select id, coalesce(title,'') as title, coalesce(summary,'') as summary, coalesce(body,'') as body ,";
		$sql = $sql." coalesce(ktitle,'') as ktitle, coalesce(ksummary,'') as ksummary, coalesce(kbody,'') as kbody  ";
   	    $sql = $sql." from news left join news_categories cat on news.id = cat.news_id where 1 = 1 ";

		if ($pCategoria != 0) {
			$sql = $sql . " and cat.category_id = $pCategoria";
		}
		if ($isRelated != "") {
			$sql = $sql . " and news.isRelated = $isRelated";
		}
		if ($pFromDate != "") {
			$sql = $sql . " and news.created ='$pFromDate'";
		}

		$i = 0;
		$result = $db->query($sql);
		if ($result != false) {
			while (($reg = mysql_fetch_array($result)) == true){
				$oNews = new cNews();
				$oNews->Load($reg);
				$ArrayRetorno[] = $oNews;
				//$i = $i + 1;
			}
		}

		return $ArrayRetorno;

	}

	function Load($assocArray) {
		//Como mejora se podria NO cargar el body title y summary cuando la noticia
		// ya fue relacionada ya que se usan solo los campos resumidos
		$this->newsID = $assocArray ["id"];
		$this->title = $assocArray ["title"];
		$this->summary = $assocArray ["summary"];
		$this->body = $assocArray ["body"];
		$this->summarizedTitle = $assocArray ["ktitle"];
		$this->summarizedSummary = $assocArray ["ksummary"];
		$this->summarizedBody = $assocArray ["kbody"];
		$this->summarizedBodyLength = str_word_count($this->summarizedBody) ;
		$this->summarizedSummaryLength =  str_word_count($this->summarizedSummary) ;
		$this->summarizedTitleLength =  str_word_count($this->summarizedTitle) ;
	}

	function SummarizeNews() {

		$this->summarizedTitle = $this->SummarizeText($this->title);
		$this->summarizedTitleLength = str_word_count($this->summarizedTitle);

		$this->summarizedBody = $this->SummarizeText($this->body);
		$this->summarizedBodyLength =str_word_count($this->summarizedBody);

		$this->summarizedSummary = $this->SummarizeText($this->summary);
		$this->summarizedSummaryLength =str_word_count($this->summarizedSummary);
	}

	function SummarizeText($text) {
		if (empty($text)) {
			return "";
		}

		$SummarizedText = strtoupper(" ".html_entity_decode($text,ENT_COMPAT,'UTF-8')." ");
		$SummarizedText = utf8_encode($SummarizedText);

		// Quito primero signos de puntuacion
		$SignsToDelete = cTrivialWords::SignsToDelete();
		for($i=0;$i<count($SignsToDelete);$i++) {
			$SummarizedText = str_replace($SignsToDelete[$i]," ",$SummarizedText);
		}

		// Luego quito palabras
		$WordsToDelete = cTrivialWords::WordsToDelete();
		for($i=0;$i<count($WordsToDelete);$i++) {
			$SummarizedText = str_replace(" ".$WordsToDelete[$i]." "," ",$SummarizedText);
		}

		//Reemplaza varios espacios seguidos por uno solo
		$SummarizedText = ereg_replace( "  +", " ", $SummarizedText);;

		//Reemplaza espacios al comienzo y final
		$SummarizedText = trim($SummarizedText );

		return $SummarizedText;


	}

	function SetRelation($db, $pNews, $pRelationType){
		// Elimino si hay relaci�n...
		$sql = "delete from related_news where news_id = ".$this->newsID." and related_new_id = ".$pNews->newsID;
		$db->query($sql);
		// Ingresar la relaci
		$sql = "insert into related_news(news_id ,related_new_id, relation_type) values({$this->newsID},{$pNews->newsID},{$pRelationType})";
		$db->query($sql);
	}

	function SetIsRelated($db, $pValue = 1){
		// Conexi�n a la base

		$sql = "update news set ";
		$sql = $sql." isRelated =".$pValue.",";
		$sql = $sql." ktitle = '".mysql_real_escape_string($this->summarizedTitle)."',";
		$sql = $sql." ksummary = '".mysql_real_escape_string($this->summarizedSummary)."',";
		$sql = $sql." kbody = '".mysql_real_escape_string($this->summarizedBody)."' ";
		$sql = $sql." where id = ".$this->newsID;

		$db->query($sql);
	}



}
?>