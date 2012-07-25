<?php

require_once ('extensions/ExtensionsSignatures.php');
require_once 'extensions/simplepie/simplepie.inc';

/**
 * @author Alejandro
 *
 *
 */
final class SimplePieWrapper extends Parser {
	/* (non-PHPdoc)
	 * @see Parser::getBuildDate()
	 */

	function __construct(){
		$this->p_object = new SimplePie();
	}

	function __destruct() {
		if (!empty($this->p_object)) {
			$this->p_object->__destruct();
		}

		unset($this->p_object);
	}

	/* Devuelve una lista de contribuyentes del feed.
	 * @return array
	 * @see Parser::getContributors()
	 */
	public function getContributors() {
		$aux = array();
		foreach ($this->p_object->get_contributors() as $contributor) {
			$aux[] = array('name' => $contributor->get_name(), 'email' => $contributor->get_email());
		}
		return $aux;
	}

	/* (non-PHPdoc)
	 * @see Parser::getCopyright()
	 */
	public function getCopyright() {
		return $this->p_object->get_copyright();

	}

	/* (non-PHPdoc)
	 * @see Parser::getDescription()
	 */
	function getDescription() {
		return $this->p_object->get_description();
	}

	/* (non-PHPdoc)
	 * @see Parser::getImageHeight()
	 */
	function getImageHeight() {
		return $this->p_object->get_image_height();
	}

	/* (non-PHPdoc)
	 * @see Parser::getImageLink()
	 */
	function getImageLink() {
		return $this->p_object->get_image_link();
	}

	/* (non-PHPdoc)
	 * @see Parser::getImageTitle()
	 */
	function getImageTitle() {
		return $this->p_object->get_image_title();
	}

	/* (non-PHPdoc)
	 * @see Parser::getImageURL()
	 */
	function getImageURL() {
		return $this->p_object->get_image_url();
	}

	/* (non-PHPdoc)
	 * @see Parser::getImageWidth()
	 */
	function getImageWidth() {
		$this->p_object->get_image_width();

	}

	/* (non-PHPdoc)
	 * @see Parser::getItems()
	 */
	function getItems($start=0, $end=0) {
		return new SpItemIterator($this->p_object->get_items($start, $end));
	}

	/* (non-PHPdoc)
	 * @see Parser::getLanguage()
	 */
	function getLanguage() {
		return $this->p_object->get_language();

	}

	/* (non-PHPdoc)
	 * @see Parser::getLatitude()
	 */
	public function getLatitude() {
		return $this->p_object->get_latitude();
	}

	/* (non-PHPdoc)
	 * @see Parser::getLink()
	 */
	function getLink() {
		return $this->p_object->get_link();

	}

	/* (non-PHPdoc)
	 * @see Parser::getLongitude()
	 */
	public function getLongitude() {
		return $this->p_object->get_longitude();

	}

	/* (non-PHPdoc)
	 * @see Parser::getTitle()
	 */
	function getTitle() {
		return $this->p_object->get_title();

	}

	/* (non-PHPdoc)
	 * @see Parser::getType()
	 */
	function getType() {
		return $this->p_object->get_type();

	}
/* (non-PHPdoc)
	 * @see Parser::getBuildDate()
	 */
	function getBuildDate() {
		// TODO Auto-generated method stub

	}
/* (non-PHPdoc)
	 * @see Parser::getEncoding()
	 */
	function getEncoding() {
		return $this->p_object->get_encoding();

	}
/* (non-PHPdoc)
	 * @see Parser::enableCache()
	 */
	function enableCache($val) {
		$this->p_object->enable_cache($val);

	}

/* (non-PHPdoc)
	 * @see Parser::setCacheDuration()
	 */
	function setCacheDuration($time) {
		$this->p_object->set_cache_duration($time);

	}

/* (non-PHPdoc)
	 * @see Parser::setCacheLocation()
	 */
	function setCacheLocation($dir) {
		$this->p_object->set_cache_location($dir);

	}

/* (non-PHPdoc)
	 * @see Parser::setFeedUrl()
	 */
	function setFeedUrl($url) {
		$this->p_object->set_feed_url($url);

	}

	function init(){
		$this->p_object->set_useragent("Mozilla/5.0 (Windows; U; Windows NT 6.0; es-AR; rv:1.9.2) Gecko/20100115 Firefox/3.6 FirePHP/0.5");
		return $this->p_object->init();
	}
/* (non-PHPdoc)
	 * @see Parser::getEditor()
	 */
	function getAuthor() {
		$aux = "";
		$authors = $this->p_object->get_authors();
		if (empty($authors)) {
			return "";
		}
		foreach ( $authors as $val) {
			$name = $val->get_name();
			$email = $val->get_email();
			if (!empty($name) && !empty($email)) {
				$aux.="{$name} <{$email}>, ";
			}elseif (empty($email)) {
				$aux.="{$name}, ";
			}else {
				$aux.="{$name}, ";
			}
		}
		if (!empty($aux)) {
			return substr($aux, 0, -2);
		}else {
			return "";
		}
	}

}

final class SpItemIterator implements ItemIterator {
	private $pos;
	private $items;

	function __construct($obj){
		$this->items = array();
		$this->pos=0;
		if (!is_array($obj)) {
			throw new Exception("Objecto no válido. Se esperaba un array", 103);
		}

		foreach ($obj as $spitem) {
			$this->items[] = new SpItem($spitem);
		}
		unset($obj);
	}

	function SpItemIterator($obj) {
		if (!is_array($obj)) {
			throw new Exception("Objecto no válido. Se esperaba un array", 103);
		}

		foreach ($obj->get_items() as $spitem) {
			$this->items[] = new SpItem($spitem);
		}
		unset($obj);
	}
	function current() {
		return $this->items[$this->pos];
	}

	/* (non-PHPdoc)
	 * @see ItemIterator::key()
	 */
	function key() {
		return $this->pos;
	}

	/* (non-PHPdoc)
	 * @see ItemIterator::next()
	 */
	function next() {

		if (($this->pos +1) > count($this->items)) {
			return false;
		}else {
			$this->pos++;
			return $this->pos;
		}

	}

	/* (non-PHPdoc)
	 * @see ItemIterator::rewind()
	 */
	function rewind() {
		$this->pos=0;
		return $this->pos;

	}

	/* (non-PHPdoc)
	 * @see ItemIterator::valid()
	 */
	function valid() {
		if (($this->pos >= 0) && ($this->pos < count($this->items))) {
			return true;
		}else {
			return false;
		}

	}
/* (non-PHPdoc)
	 * @see ItemIterator::getCount()
	 */
	function getCount() {
		return count($this->items);

	}



}

class SpItem extends Item {

	function __construct($obj) {
		//$this->p_object= new SimplePie_Item();
		if (!is_a($obj, 'SimplePie_Item')) {
			throw new Exception("Se esperaba un objecto SimplePie_Item", 104);
		}
		$this->p_object=$obj;
	}

	public function Item($obj){
		if (!is_a($obj, 'SimplePie_Item')) {
			throw new Exception("Se esperaba un objecto SimplePie_Item", 104);
		}
		$this->p_object=$obj;
	}
	/* (non-PHPdoc)
	 * @see Item::getAuthor()
	 */
	public function getAuthor() {
		return $this->p_object->get_author(0);

	}

	/* (non-PHPdoc)
	 * @see Item::getCategory()
	 */
	function getCategory() {
		return $this->p_object->get_category(0);

	}

	/* (non-PHPdoc)
	 * @see Item::getContent()
	 */
	public function getContent($contentOnly=true) {
		return $this->p_object->get_content($contentOnly);

	}

	/* (non-PHPdoc)
	 * @see Item::getContributor()
	 */
	public function getContributor() {
		return $this->p_object->get_contributor(0);

	}

	/* (non-PHPdoc)
	 * @see Item::getCopyright()
	 */
	public function getCopyright() {
		return $this->p_object->get_copyright();

	}


	/* (non-PHPdoc)
	 * @see Item::getEnclosures()
	 */
	function getEnclosures($start=0, $length=0) {
		$enclosures = array();
		$spenc = $this->p_object->get_enclosures($start,$length);
		if (empty($spenc)) {
			return $enclosures;
		}
		foreach (@$this->p_object->get_enclosures($start,$length) as $encl) {
			$aux['link']=$encl->get_link();
			$aux['length']=$encl->get_length();
			$type=$encl->get_type();
			switch ($type) {
				case 'image/jpg':
					$aux['type']=1;
				break;
				case 'image/jpeg':
					$aux['type']=1;
				break;
				case 'image/gif':
					$aux['type']=1;
				break;
				case 'image/png':
					$aux['type']=1;
				break;

				default:
					$aux['type']=-1;
				break;
			}
			$enclosures[]=$aux;
		}
		return $enclosures;
	}


	/* (non-PHPdoc)
	 * @see Item::getLatitude()
	 */
	public function getLatitude() {
		return $this->p_object->get_latitude();

	}

	/* (non-PHPdoc)
	 * @see Item::getLink()
	 */
	function getLink() {
		// TODO Auto-generated method stub

	}

	/* (non-PHPdoc)
	 * @see Item::getLinks()
	 */
	public function getLinks() {
		// TODO Auto-generated method stub

	}

	/* (non-PHPdoc)
	 * @see Item::getLongitude()
	 */
	public function getLongitude() {
		return $this->p_object->get_longitude();

	}

	/* (non-PHPdoc)
	 * @see Item::getPermalink()
	 */
	public function getPermalink() {
		return $this->p_object->get_permalink();

	}

	/* (non-PHPdoc)
	 * @see Item::getSummary()
	 */
	function getSummary() {
		return $this->p_object->get_description();
	}

	/* (non-PHPdoc)
	 * @see Item::getTitle()
	 */
	function getTitle() {
		return $this->p_object->get_title();

	}


}
?>