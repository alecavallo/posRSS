<?php

/**
 * @author Alejandro
 *
 *
 */
require_once '../../connector.php';

class Clasificator {
	//TODO - Insert your code here
	private $news;

	private $visitsWeight;
	private $lenghtWeight;
	private $votesWeight;
	private $commentsWeight;
	private $agingRate;
	private $period;

	private $db;


	function __construct() {
		require_once '../../configure.php';
		//creo la conexion a la DB para obtener las tasas.
		try {
			$this->db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
		} catch (Exception $e) {
			die($e->getMessage());
		}

		$sql="select value from parameters p where p.key like 'visitsWeight'";
		$aux = $this->db->query($sql);
		$this->visitsWeight = mysql_fetch_assoc($aux);
		$this->visitsWeight = $this->visitsWeight['value'];
		
		$sql="select value from parameters p where p.key like 'lenghtWeight'";
		$aux = $this->db->query($sql);
		$aux = mysql_fetch_assoc($aux);
		$this->lenghtWeight = $aux['value'];

		$sql="select value from parameters p where p.key like 'votesWeight'";
		$aux = $this->db->query($sql);
		$this->votesWeight = mysql_fetch_assoc($aux);
		$this->votesWeight = $this->votesWeight['value'];

		$sql="select value from parameters p where p.key like 'commentsWeight'";
		$aux = $this->db->query($sql);
		$this->commentsWeight = mysql_fetch_assoc($aux);
		$this->commentsWeight = $this->commentsWeight['value'];

		$sql="select value from parameters p where p.key like 'agingRate'";
		$aux = $this->db->query($sql);
		$this->agingRate = mysql_fetch_assoc($aux);
		$this->agingRate = $this->agingRate['value'];

		$sql="select value from parameters p where p.key like 'period'";
		$aux = $this->db->query($sql);
		$this->period = mysql_fetch_assoc($aux);
		$this->period = $this->period['value'];

		unset($aux);
	}


	/**
	 *
	 */
	function __destruct() {
		unset($this->news);
		unset($this->db);
	}

	/**
	 * @return the $news
	 */
	public function getNews() {
		return $this->news;
	}

	/**
	 * @param array $news Arreglo de datos necesarios para calificar la noticias. No debe contemer menos de 10 elementos.
	 */
	public function setNews($news) {
		if (count($news)>=11) {
			$this->news=$news;
		}else {
			throw new Exception("Se esperan como mínimo un array de 11 elementos", 100);
		}
	}

	public function classify($news){
		if (!empty($news)) {
			$this->setNews($news);
		}
		$pVisits = $this->calculateVisits();
		$pVotes = $this->calculateVotes();
		$pComments = $this->calculateComments();
		$pAging = $this->calculateAging();
		//$pChars = $this->calculateLength();
		$pChars = 0;

		if ($this->news['Processed']==0) {
			if ($this->news['NewsHasImage']==1) {
				$rate=30;
			}else {
				$rate = 29;
			}
			$rate=$news['NewsRating'];
		}
		$rate = (int) (($rate+$pVisits+$pVotes+$pComments+$pChars-$pAging)< 100 ? ($rate+$pVisits+$pVotes+$pComments+$pChars-$pAging) : 100);
		$rate = $rate <=0 ? 1 : $rate;
		$sql = "update news set rating = {$rate} where id = {$this->news['NewsId']};";
		$this->db->query($sql);
		return $rate;
	}

	private function calculateVisits(){
		return $this->visitsWeight*$this->news['NewsVisits'];
	}

	private function calculateVotes(){
		return 0;
		return $this->votesWeight*$this->news['NewsVotes'];
	}

	private function calculateComments(){
		//return $this->commentsWeight * $this->news['NewsComments'];
		return 0;
	}
	private function calculateLength(){
		$lenght = count($this->news['Summary'])%100;
		return (double) $lenght * (double) $this->lenghtWeight;
	}

	private function calculateAging(){
		return 0;
		$created = strtotime($this->news['NewsCreated']);
		$modified = strtotime($this->news['NewsModified']);
		$currtime = strtotime('now');
		$lastModification = ($modified-$created)>0? $modified : $created;

		return ($currtime - $lastModification)/60/$this->period * $this->agingRate;
	}

}

?>