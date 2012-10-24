<?php
//mb_internal_encoding("UTF-8");
//ini_set('memory_limit', '128M');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('memory_limit', '256M');
//$mem = memory_get_usage()/1024/1024;
//echo "AL INICIO {$mem}Mb\n\n";
/**
 * url to parse
 * @var string
 */
//$url = 'http://www.ellitoral.com/rss/um.xml';
require_once 'connector.php';
require_once('configure.php');
//include the class used to parse the RSS


//$url = 'http://www.clarin.com/rss/';
date_default_timezone_set("America/Argentina/Buenos_Aires");



//instantiate rss document
$cacheDir = 'tmp/';
$cacheTime = 1197;
try {
	$db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
} catch (Exception $e) {
	die($e->getMessage());
}
//$db->query("SET autocommit=0"); //disable autocommit
$sql = <<<QRY
select users.id as user_id, feeds.* 
from feeds 
inner join sources on sources.id=feeds.source_id
left join users on users.sources_id=sources.id
where enabled in (1,2) and (content_type = 1 or content_type = 2) and (last_processing_date <= DATE_SUB(now(), INTERVAL 1 HOUR) or last_processing_date is null) order by feeds.rating asc, rand();
QRY;

$result = $db->query($sql);
unset($sql);
//unset($db);
//$db->query('lock tables feeds LOW_PRIORITY write, news LOW_PRIORITY write');
//$mem = memory_get_usage()/1024/1024;
//echo "DESPUES DE CARGAR TODO {$mem}Mb\n\n";
echo date("c")." -- INICIANDO EL PROCESAMIENTO DE LOS FEEDS\n";
$created = date('Y-m-d H:00:00',time());
while (($row = mysql_fetch_assoc($result)) == true) {
	echo "Procesando {$row['id']} -- {$row['image_title']} con URL {$row['url']}\n";

	//if (date('"Y-m-d H:i:s"',strtotime($row['last_processing_date'])) < date('"Y-m-d H:i:s"',time()-3600)) {
		$rss = RSSreader::getInstance(RSSreader::SIMPLEPIE);

		// setup transparent cache
		$rss->setCacheLocation($cacheDir);
		$rss->setCacheDuration($cacheTime); // one hour

		// load some RSS file
		//$row['url'] = "http://www.clarin.com/rss/politica/";
		$rss->setFeedUrl(($row['url']));
		$rssdoc = $rss->init();
		//var_dump($rssdoc);
		/*$mem = memory_get_usage()/1024/1024;
		echo "CARGO RSS {$mem}Mb\n\n";*/


		try {
			//$db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
		} catch (Exception $e) {
			die($e->getMessage());
		}

		$fTitle = $rss->getTitle();
		$fSummary = $rss->getDescription();
		//$created = $rss->getBuildDate();
		if (!empty($created)) {
			//$created = date('"Y-m-d H:i:s"',strtotime($created));
		}else {
			//$created = null;
		}

			//loop through each item
			foreach ($rss->getItems() as $itm) {
				$str = $itm->getContent(true);
				$str .= $itm->getSummary();
				$encoding = mb_detect_encoding($str,"UTF-8, ISO-8859-1, GBK, Windows-1251, Windows-1252");
				unset($str);

				$title = $itm->getTitle();
				$title = html_entity_decode($title,ENT_QUOTES,$encoding);
				$title = str_ireplace("\n", "", $title);
				$title = str_ireplace("\r", "", $title);
				$title = strip_tags($title);
				$title = strToHTML($title);

				$summary = $itm->getSummary();
				$summary = html_entity_decode($summary,ENT_QUOTES,$encoding);
				$summary = str_ireplace("\n", "<br>", $summary);
				$summary = str_ireplace("\r", "", $summary);
				$summary = strip_tags($summary,"<i><strong><b>");
				$summary = strToHTML($summary);

				//$created = $currItem->hasElement('pubDate')?date('"Y-m-d H:i:s"', strtotime($currItem->getPubDate())):date('"Y-m-d H:i:s"',time());
				//$created = date('"Y-m-d H:i:s"',time());
				$enclosures = $itm->getEnclosures();
				if (!empty($enclosures)) {
					$mediaType = explode("/", $enclosures[0]['type']);
					if(strtolower($mediaType[0])=="image"){//si son imágenes, chequeo tamaño
						$imgSize = getimagesize($enclosures[0]['link']);
						if ($imgSize[0]>=(328*5/6) && $imgSize[1]>=(238*5/6)) {//si la imágen es mayor que los 5/6 de 390px*300px la dejo pasar 
							$encUrl = $enclosures[0]['link'];
							$encLength = $enclosures[0]['length'];
							$encType = $enclosures[0]['type'];
						}else {
							$encUrl = null;
							$encLength = null;
							$encType = null;
						}
					}else {
						$encUrl = $enclosures[0]['link'];
						$encLength = $enclosures[0]['length'];
						$encType = $enclosures[0]['type'];
					}
					
				}else {
					$encUrl = null;
					$encLength = null;
					$encType = null;
				}
				$body = '';
				$body = $itm->getContent(true);
				$body = html_entity_decode($body,ENT_QUOTES,$encoding);
				$body = str_ireplace("\n", "<br>", $body);
				$body = str_ireplace("\r", "", $body);
				$body = strip_tags($body,"<p><i><strong><b><br /><br>");
				$body = strToHTML($body);
				$body = $body==$summary?'':$body;
				$link = $itm->getPermalink();
				$link = mysql_real_escape_string($link);
				$title = mysql_real_escape_string($title);
				$summary = mysql_real_escape_string($summary);
				$rslt = $db->query("SELECT id FROM news WHERE feed_id = {$row['id']} AND title LIKE '{$title}' AND summary LIKE '{$summary}' and link like '{$link}';");
				if (mysql_affected_rows($db->getConnection()) > 0) {
					continue;
				}
				$hasImage = !empty($encUrl)?1:0;
				if (empty($summary)) {//si la noticia no tiene copete, continuar con la siguiente
					continue;
				}
				if (str_word_count($summary,0) <= 15) {//si la noticia tiene un copete menor o igual a 15 palabras, continuar con la siguiente
					continue;
				}
				$row['city_id'] = !empty($row['city_id'])?$row['city_id']:'null';
				$row['state_id'] = !empty($row['state_id'])?$row['state_id']:'null';

				/*escapando los strings para insertarlos en la DB*/

//				$summary = mysql_real_escape_string($summary);
				$body = mysql_real_escape_string($body);
				$rating = mysql_real_escape_string($rating);
				//$created = mysql_real_escape_string($created);
				$encType = mysql_real_escape_string($encType);
				$encUrl = mysql_real_escape_string($encUrl);
				//$link = mysql_real_escape_string($link);
				$hasImage = mysql_real_escape_string($hasImage);
				
				if(empty($row['user_id'])){
					$userId = 'null';
				}else {
					$userId=$row['user_id'];
				}

				
				$newsSql="INSERT LOW_PRIORITY IGNORE INTO `news`
				(`title`, `summary`, `body`, `rating`, `visits`, `votes`, `created`, `modified`, `user_id`, `city_id`, `state_id`,
				`repeated_url`, `feed_id`, `related_news_id`, `media_type`, `media_url`, `link`, `hasImages`, `category_id`)
				VALUES ( '{$title}', '{$summary}', '{$body}', 30, 0, 0, '{$created}', null, {$userId}, {$row['city_id']}, {$row['state_id']},
				null, {$row['id']}, null, '{$encType}', '{$encUrl}', '{$link}', {$hasImage}, {$row['category_id']})
				ON DUPLICATE KEY UPDATE title='{$title}', summary='{$summary}', body='{$body}';";

				$db->query($newsSql);
				$newsId = mysql_insert_id($db->getConnection());
				if ($newsId == 0) { //si es verdadero es porque se ha actualizado un registro en vez de haberse insertado uno nuevo
					$update = true;
				}else {
					$update = false;
				}
				/*if (!$update) {
					$updNewsCate="insert LOW_PRIORITY ignore into news_categories (news_id, category_id) values ({$newsId}, {$row['category_id']});";
					echo "\t\t   * {$newsId} -- {$title}\n";
					$db->query($updNewsCate);
				}*/

				$mediaType = explode("/", $encType);
				if (!empty($encUrl) || strtolower($mediaType[0])=="image") {
					if (!$update) {
						$addImg = "insert LOW_PRIORITY ignore into medias (url, news_id) values ('{$encUrl}', {$newsId});";
						$db->query($addImg);
						$hasImage = !empty($encUrl)?1:0;
						//$updRating = "update news set rating=3, hasImages={$hasImage} where id={$newsId};";
						//$db->query($updRating);
					}else {
						$qry = "select id from medias where url='{$encUrl}';";
						$db->query($qry);
						if (mysql_affected_rows($db->getConnection()) == 0) {
							$addImg = "insert LOW_PRIORITY into medias (url, news_id) values ('{$encUrl}', {$newsId})";
							$db->query($addImg);
							$hasImage = !empty($encUrl)?1:0;
							//$updRating = "update news set rating=3, hasImages={$hasImage} where id={$newsId};";
							//$db->query($updRating);
						}
					}

				}else {//si no es una imagen
					//inserto lo que haya en el enclosure
					$imgs=$itm->getEnclosures();
					if (!empty($imgs)) {
						foreach ($imgs as $rowImg){
							if(empty($rowImg['link']) || empty($newsId)){
								continue;
							}
							if (!$update) {
								$addImg = "insert LOW_PRIORITY into medias (url, news_id, type) values ('{$rowImg['link']}', {$newsId}, '{$rowImg['type']}');";
								$db->query($addImg);
							}else {
								$qry = "select id from medias where url='{$rowImg['link']}';";
								$db->query($qry);
								if (mysql_affected_rows($db->getConnection()) == 0) {
									$addImg = "insert LOW_PRIORITY into medias (url, news_id, type) values ('{$rowImg['link']}', {$newsId}, '{$rowImg['type']}');";
									$db->query($addImg);
								}
							}
	
						}
					}
					
					
					
					//trato de obtener una imágen desde la descripción de la noticia
					//$imgsDesc = getImgFromDesc($itm->getContent(true));
					$imgsDesc = null; //desactivo la obtención de imágenes desde la noticia
					if (!empty($imgsDesc)) {
						foreach ($imgsDesc as $rowImg) {
							$imgSize = getimagesize($rowImg);
							if ($imgSize[0]<(390*3/4) || $imgSize[1]<(300*3/4)) {//si la imágen es menor que los 3/4 de 390px*300px la descarto
								continue;	
							}
							if (!$update) {
								$rowImg = mysql_real_escape_string($rowImg);
								$addImg = "insert LOW_PRIORITY into medias (url, news_id) values ('{$rowImg}', {$newsId});";
								$db->query($addImg);
							}else {
								$qry = "select id from medias where url='{$rowImg}';";
								$db->query($qry);
								if (mysql_affected_rows($db->getConnection()) == 0) {
									$addImg = "insert LOW_PRIORITY into medias (url, news_id) values ('{$rowImg}', {$newsId});";
									$db->query($addImg);
								}
							}
	
						}
	
						$updRating = "update news set rating=3, hasImages=1 where id={$newsId};";
						$db->query($updRating);
					}
				}
				
			}

			$status=$rss->getItems()->getCount();
			if ($status == 0) {
				$status = 2;
			}else {
				$status=1;
			}

		//}
		//check if image element exists
		$imgUrl = $rss->getImageURL();
		//get the image from twitter and put it as blogger profile image
		$sql = "select feeds.url, feeds.image_url from feeds where feeds.source_id={$row['source_id']} AND content_type=3 LIMIT 1";
		$twitterUser = $db->query($sql);
		//if exists at least 1 twitter account get the profile image URL
		if (mysql_affected_rows($db->getConnection()) > 0 && empty($twitterUser['image_url'])) {
			$twitterUser = mysql_fetch_assoc($twitterUser);
			$url = "http://search.twitter.com/search.json?q=from:{$twitterUser['url']}&rpp=1";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$twResult = curl_exec($ch);
			$error = curl_errno($ch);

			if (!$error) {
				$twObj = json_decode($twResult);
				$imgUrl = $twObj->results[0]->profile_image_url;
				$imgUrl = mysql_real_escape_string($imgUrl);
				$iWidth = "48";
				$iHeight = "48";
			}
			else {
				echo "No se puede obtener imagen de twitter: ".curl_error($ch);
			}

			curl_close ($ch);
		}

		if (!empty($imgUrl)) {
			//echo title of the image
	  		$iTitle = mysql_real_escape_string(strToHTML($rss->getImageTitle()));
	  		$iTitle = empty($iTitle)? mysql_real_escape_string($fTitle):$iTitle;
	  		//echo link of the image
	  		$iLink = mysql_real_escape_string(strToHTML($rss->getImageLink()));
	  		//echo URL of the image
	  		$iUrl = mysql_real_escape_string($imgUrl);
	  		//echo width of the image
	  		$iWidth = $rss->getImageWidth();
	  		$iWidth = empty($iWidth)?'null':$iWidth;
	  		$iWidth = mysql_real_escape_string($iWidth);
	  		//echo height of the image
	  		$iHeight = $rss->getImageHeight();
	  		$iHeight = empty($iHeight)?'null':$iHeight;
	  		$iHeight = mysql_real_escape_string($iHeight);
	  		//echo description of the image
	  		$iDesc = mysql_real_escape_string(strToHTML($rss->getImageTitle()));
		}else {
	  		$iUrl = null;
	  		$iLink = null;
	  		$iTitle = null;
	  		$iWidth = 'null';
	  		$iHeight = 'null';
	  		$iDesc = null;
		}
		$lastProcDate=date('"Y-m-d H:i:s"',time());
		$copyright = strToHTML($rss->getCopyright());
		//$ttl = !empty($rss->get)?$currChannel->getTTL():'null';
		$ttl=null;
		$ttl = intval($ttl);
		$ttl = empty($ttl)?'null':$ttl;
		//$rating = $currChannel->hasElement('rating')?$currChannel->getRating():1;
		$language = $rss->getLanguage();
		$language = !empty($language)?$language:'sp';
		//$webmaster = $currChannel->hasElement('webMaster')?$currChannel->getWebMaster():'';
		//$editor = $currChannel->hasElement('managingEditor')?$currChannel->getManagingEditor():'';
		$author = mysql_real_escape_string($rss->getAuthor());

		$updateSql="update feeds
				set last_processing_date={$lastProcDate},
				image_url = '{$iUrl}',
				image_title = '{$iTitle}',
				image_link = '{$iLink}',
				image_width = {$iWidth},
				image_height = {$iHeight},
				copyright = '{$copyright}',
				ttl = {$ttl},
				language = '{$language}',
				webmaster = '{$webmaster}',
				editor = '{$author}',
				enabled = {$status}
				where id = {$row['id']};";
		//var_dump($updateSql);
		try {
			//echo $rawRSS;
			$db->query($updateSql);

		} catch (Exception $e) {
			$db->query('unlock tables');
			die($e->getMessage());
		}
		//$mem = memory_get_usage()/1024/1024;
		//echo "ANTES DE LIBERAR MEMORIA {$mem}Mb\n\n";
		/*}*/
		$db->query('unlock tables');
		//unset($db);
	/*}else{
		echo "  No es necesario procesar URL {$row['url']}\n";
	}*/

	try {
		//$db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
	} catch (Exception $e) {
		die($e->getMessage());
	}
	$lastProcDate=date('"Y-m-d H:i:s"',time());
	$updateSql="update feeds
			set last_processing_date={$lastProcDate}
			where id = {$row['id']};";
	//var_dump($updateSql);
	try {
		//echo $rawRSS;
		$db->query($updateSql);

	} catch (Exception $e) {
		$db->query('unlock tables');
		die($e->getMessage());
	}
	echo "{$row['url']} se ha procesado correctamente\n";
	$rss->__destruct();
	unset($rss);
	$db->query('unlock tables');

}
//$mem = memory_get_usage()/1024/1024;
//echo "AL LIBERAR TODO {$mem}Mb\n\n";

echo date("c")." -- FIN PROCESAMIENTO DE RSS\n\n";
$mem = memory_get_peak_usage()/1024/1024;
echo "PICO DE CONSUMO {$mem}Mb\n\n";

//se actualizan los ratings de los feeds y las fuentes
$sqlUpdFeed = "update feeds, (select news.feed_id, sum(news.rating) as feed_rating from news group by news.feed_id) as nrat
set feeds.rating = nrat.feed_rating
where feeds.id=nrat.feed_id;";
$db->query($sqlUpdFeed);

$sqlUpdSources = "update sources, (select feeds.source_id, sum(feeds.rating) as source_rating from feeds group by feeds.source_id) as frat
set sources.rating = frat.source_rating
where sources.id = frat.source_id;";
$db->query($sqlUpdSources);
//unset($db);

function strToHTML($string, $charsetArray=array('iso-8859-1','ISO-8859-1','WINDOWS-1252','UTF-8')){
	if (empty($charsetArray)) {
		$charsetArray=array('iso-8859-1','ISO-8859-1','WINDOWS-1252','UTF-8');
	}
		$fromEncoding = mb_detect_encoding($string, $charsetArray, false);

		$string = mb_convert_encoding($string, 'UTF-8', $fromEncoding);

		$string = utf8_decode($string);


	return $string;
}

function getImgFromDesc($html){
	$doc = new DOMDocument();
	@$doc->loadHTML($html);
	$img = $doc->getElementsByTagName('img');
	$src=array();
	foreach ($img as $node) {
		if ($node->hasAttribute('src')) {
			$src[] = $node->getAttribute("src");
		}
	}

	return $src;
}

class RSSreader {
	const SIMPLEPIE = 0;
	const LAST_RSS = 1;
	const DOMIT = 2;

	static function getInstance($inst) {
		//inicializo los objetos, de corresponder
		switch ($inst) {
			case RSSreader::SIMPLEPIE:
				require_once 'extensions/SimplePieWrapper.php';
				return  new SimplePieWrapper();
			break;
			case RSSreader::LAST_RSS:
				/*require_once('extensions/lastRSS/lastRSS.php');
				$this->prefix="lastrss";
				$this->p_object = new lastRSS();*/
			break;
			case RSSreader::DOMIT:
				/*require_once('extensions/domit_rss/xml_domit_rss.php');
				$this->prefix="domit";*/
			break;
			default:
				require_once 'extensions/simplepie/simplepie.inc';
				$this->prefix="simplepie";
				return  new SimplePieWrapper();
			break;
		}
	}

}
?>