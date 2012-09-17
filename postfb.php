<?php
require_once 'extensions/facebook/src/facebook.php';
require_once 'connector.php';
require_once('configure.php');
require_once('extras.php');
global $cacheFilename;
$cacheFilename = "FBdailyposts.tmp";
$categoriesCount = 0;

date_default_timezone_set("America/Argentina/Buenos_Aires");
try {
	$db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
} catch (Exception $e) {
	die($e->getMessage());
}
if ((($newsMix = readCache($cacheFilename, '7 hours')) === false)||empty($newsMix)) {
	
	
	//$sql = "select id, title, created, modified, user_id, link from news where (created >= DATE_SUB(now(), INTERVAL 12 HOUR) or created is null) order by rating DESC, rand() limit 48;";
	$sql = "select news.id, news.title, news.created, news.modified, news.user_id, news.link, news.category_id, news.rating, categories.name, sources.name as sourcename from news inner join feeds on feeds.id=news.feed_id	inner join sources on sources.id=feeds.source_id inner join categories on categories.id=feeds.category_id where news.processed <> 2 and (news.created >= DATE_SUB(now(), INTERVAL 12 HOUR)) and news.category_id in (select id from categories) and news.rating > 10 and feeds.content_type <> 2 group by sources.id order by news.rating desc, news.created desc, rand()";
	$result = $db->query($sql);
	$data = array();
	while (($row = mysql_fetch_assoc($result)) == true) {
		$data[$row['category_id']][] = $row;
	}
	$categoriesCount = count($data);
	writeCache('categoriesCount', $categoriesCount);
	unset($sql);
	//unset($db);
	//var_dump($data);
	$index = 1;
	$newsMix = array();
	for ($i = 0; $i < $index; $i++) {
		foreach ($data as $row) {
			if (array_key_exists($i, $row)) {
				$newsMix[]=$row[$i];
			}
		}
	}
	unset($data);
	try {
		writeCache($cacheFilename, $newsMix);	
	} catch (Exception $e) {
		echo $e->getMessage();
	}
	
}else {
	$categoriesCount = readCache('categoriesCount', '12 hours');
	//echo "DESDE CACHE\n\n";
}

/*configuración acceso a facebook*/
$config = array ();
$config ['appId'] = '122010617872934';
$config ['secret'] = '9ded61b4c40c0e6baf4b6ab5b52c4746';
$config ['fileUpload'] = true; // optional

$facebook = new Facebook ( $config );
//$accessToken = "AAACnABbWnVkBAAOiMaXFi5ZBV7XM2t34o7TQn83oM6XDAsCBBuaTcPj16MOiGT0T9BIQYJeeZBhLlkPdersZCMe3972O53iaBZAAZCie7fwZDZD"; //posteamos test
				  
$accessToken = "AAACnABbWnVkBADafGhkXse8YCZBOoZBBl3sdhXl0Y8zl7zGzEWKCimuUa6IbB8Clz0pTT0YPrfmErdT3Y0pZB468uHsUPs59DiZBuSvhOAZDZD";

echo "Quedan por procesar: ".count($newsMix)." noticias\n";
echo "*********************************************\n\n";
try {
	$row = array_shift($newsMix);
	$link = "/medios/".slug($row['sourcename'])."/noticia/".$row['id']."-".slug($row['title']);
	$link = "http://www.posteamos.com".$link.".html";
	$ret_obj = $facebook->api ( '/me/feed', 'POST', array ('link' => $link, 'message' => $row['name'], 'access_token'=>$accessToken ) );
	writeCache($cacheFilename, $newsMix);
	$sql = "update news set news.processed=2 where news.id = {$row['id']}";
	$db->query($sql);
	echo "[".date("c")."] Publicado[#{$ret_obj ['id']}]: {$row['title']} // [{$row['created']}]\n";
} catch ( FacebookApiException $e ) {
	// If the user is logged out, you can have a 
	// user ID even though the access token is invalid.
	// In this case, we'll get an exception, so we'll
	// just ask the user to login again here.
	
	echo "\n   * Error!: {$e->getType()} - {$e->getMessage()}\n\n";
}
echo "\n";

unset($db);
//die('nada');

function writeCache($name, $data){
	$filename = explode(".", $name);
	if (count($filename) < 2) {
		$name.= ".tmp";
	}
	$name = "tmp/".$name;
	//si existe, elimino el archivo para que la fecha de creacion sea reciente
	if (file_exists($name)) {
		unlink($name);
	}
	$cache = fopen($name, 'w+');
	if ($cache === false) {
		throw new Exception("No se puede crear el archivo temporario", 0);
	}
	
	$success = fwrite($cache, serialize($data));
	fclose($cache);
	if ($success===false) {
		return false;
	}else {
		return true;
	}
	
}

function readCache($name, $expires) {
	$filename = explode(".", $name);
	if (count($filename) < 2) {
		$name.= ".tmp";
	}
	$name = "tmp/".$name;
	if (!file_exists($name)) {
		return false;
	}
	
	$fileCreation = filectime($name);
	//$hdate = date("c",$fileCreation);
	$expTimestamp = strtotime("+".$expires, $fileCreation);
	//$hexpire = date("c",$expTimestamp);
	//echo $hdate."  --  ".$hexpire;
	if ($expTimestamp <= time()) {
		if (file_exists($name)) {
			unlink($name);
		}
		return false;
	}
	
	$handler = fopen($name, 'r');
	$value = unserialize(fread($handler, filesize($name)));
	fclose($handler);
	return $value;
}
?>