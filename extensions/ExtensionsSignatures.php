<?php

/**
 * Esta clase especifica la interface que un parser genérico debería tener
 * @author Alejandro
 *
 */
abstract class Parser {
	protected $p_object;
	abstract protected function getItems($start=0, $end=0);

	/**
	 * Devuelve el título del feed
	 * @return string
	 */
	abstract function getTitle();
	/**
	 * Devuelve un link a la fuente del feed
	 * @return string
	 */
	abstract function getLink();
	/**
	 * Devuelve una descripción del feed
	 * @return string
	 */
	abstract function getDescription();
	/**
	 * Devuelve el idioma del feed
	 * @return string
	 */
	abstract function getLanguage();
	/**
	 * Devuelve la fecha de última publicación en el feed
	 * @return date
	 */
	abstract function getBuildDate();
	/**
	 * Devuelve el título de la imagen que identifica al feed
	 * @return string
	 */
	abstract function getImageTitle();
	/**
	 * Devuelve la URL de la imágen
	 * @return string
	 */
	abstract function getImageURL();
	/**
	 * Devuelve el link a setear en la imágen
	 * @return string
	 */
	abstract function getImageLink();
	/**
	 * Devuelve el ancho de la imágen
	 * @return integer
	 */
	abstract function getImageWidth();
	/**
	 * Devuelve el alto de la imágen
	 * @return integer
	 */
	abstract function getImageHeight();
	/**
	 * Devuelve el tipo de feed (RSS, ATOM, etc)
	 * @return string
	 */
	abstract function getType();
	/**
	 *
	 * devuelve el encoding del feed
	 */
	abstract function getEncoding();

	abstract function getAuthor();

	/**
	 * Devuelve el nombre de las personas que contribuyeron en el feed
	 * @return array
	 */
	function getContributors(){
		return null;
	}
	/**
	 * Devuelve el copyright del feed
	 * @return string
	 */
	function getCopyright(){
		return null;
	}
	/**
	 * Devuelve la latitud de la geolocalización del feed
	 * @return float
	 */
	function getLatitude(){
		return null;
	}
	/**
	 * Devuelve la latitud de la geolocalización del feed
	 * @return float
	 */
	function getLongitude() {
		return null;
	}

	abstract function enableCache($bool);
	abstract function setCacheDuration($time);
	abstract function setCacheLocation($dir);
	abstract function setFeedUrl($url);
	abstract function init();
}

/**
 * Esta clase es la utilizada a la hora de iterar sobre un conjunto de itemes
 * @author Alejandro
 *
 */
interface ItemIterator extends Iterator {
	/* (non-PHPdoc)
	 * @see Iterator::current()
	 */

	public function getCount();
}


/**
 * Esta clase es la encargada de alamacenar los datos de cada uno de los itemes del RSS
 * @author Alejandro
 *
 */
abstract class Item {
	protected  $p_object;

	abstract function getTitle();
	abstract function getSummary();
	abstract function getLink();
	//abstract protected function getPubDate();
	abstract function getCategory();
	abstract function getEnclosures($start=0, $length=0);


	function getAuthor() {
		return null;
	}
	function getContent(){
		return null;
	}
	function getContributor(){
		return null;
	}
	function getCopyright(){
		return null;
	}
	function getLinks(){
		return null;
	}
	function getPermalink() {
		return null;
	}
	function getLatitude() {
		return null;
	}
	function getLongitude(){
		return null;
	}

}
?>