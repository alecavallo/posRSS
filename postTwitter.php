<?php
require_once 'extensions/tmhOAuth/tmhOAuth.php';
require_once 'connector.php';
require_once('configure.php');
global $cacheFilename;
$cacheFilename = "TWdailyposts.tmp";

$consumer_key = "BPTgFFS7bjGZh0QvQjP0cA";
$consumer_secret = "4U9dPmpwAsaja6IiiWlrehryFYNyMfBQW2uIAmWg0U";
$oauth_token = "152806093-I7ynzUxQiH4DHNYEx8hLnH7mkI0r3p6i9KSV4c9E";
$oauth_token_secret = "Bmqn1sbjqGD67V4V7lTsTYzVhffy4Km59VUAiBLn6Y";
$twPlaceId = "4d3b316fe2e52b29";

$bitLyApiKey = "R_9bb3e60d235c765d7763c0ebeacabd7f";
$bitLyUsr = "posteamos";
$bitLyUrl = "http://api.bitly.com/";
$categoriesCount = 0;

date_default_timezone_set("America/Argentina/Buenos_Aires");
try {
	$db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
} catch (Exception $e) {
	die($e->getMessage());
}
if (($newsMix = readCache($cacheFilename, '12 hours')) === false || true) {
	
	
	//$sql = "select id, title, created, modified, user_id, link from news where (created >= DATE_SUB(now(), INTERVAL 12 HOUR) or created is null) order by rating DESC, rand() limit 48;";
	$sql = "select users.alias,sources.name as source, sources.twtr_username twitter, news.id, news.title, news.created, news.modified, news.user_id, news.link, news.category_id, news.rating, categories.name from news inner join feeds on feeds.id=news.feed_id	inner join sources on sources.id=feeds.source_id inner join categories on categories.id=feeds.category_id left join users on users.sources_id=sources.id where news.processed <> 2 and (news.created >= DATE_SUB(now(), INTERVAL 24 HOUR)) and news.category_id in (select id from categories) and news.rating > 10 and feeds.content_type <> 3 group by sources.id order by news.rating asc, rand()";
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
	$index = 10;
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

for ($i=0; $i < ($categoriesCount*1); $i++) { //muestro 2 noticias de cada categoría
	if (count($newsMix)== 0) {
		break;
	}
	echo "Quedan por procesar: ".count($newsMix)." noticias\n";
	echo "*********************************************\n\n";
	$row = array_shift($newsMix);
	$shortener = $bitLyUrl."v3/shorten?login=".$bitLyUsr."&apiKey=".$bitLyApiKey."&longUrl=".urlencode($row['link']);
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $shortener); 
	curl_setopt($ch, CURLOPT_HEADER, FALSE); 
	//curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	$url = curl_exec($ch);
	
	$url = json_decode($url);
	if($url->status_code != 200){
		echo "No se puede acortar la url: {$row['link']}. Motivo: ".$url->status_txt;
		continue;
	}
	$link = $url->data->url;
	if(empty($row['twitter'])){
		$row['twitter'] = "@".$row['alias'];
	}
             
	$tweet = "#".str_ireplace(" & ", "", $row['name'])." ".$row['twitter'].": ".$row['title']." ".$link."";
	if(strlen($tweet)>123){
		$removeChars = strlen($tweet)-(123);
		if(strlen($row['title'])-$removeChars <=0){
			continue;
		}
		$shortTitle = substr($row['title'], 0,strlen($row['title'])-$removeChars)."...";
		$tweet = "#".str_ireplace(" & ", "", $row['name'])." ".$row['twitter'].": ".$shortTitle." ".$link."";
	}
	
	$connection = new tmhOAuth(array(
		    'consumer_key' => $consumer_key,
		    'consumer_secret' => $consumer_secret,
		    'user_token' => $oauth_token,
		    'user_secret' => $oauth_token_secret,
	));
	$connection->request('POST', 
    	$connection->url('1/statuses/update'), 
    	array('status' => $tweet,
    		'place_id'	=> $twPlaceId
    	)
    );
	$aux = $connection->response['response'];
	$aux = json_decode($aux);
	$sql = "update news set news.processed=2 where news.id = {$row['id']}";
	$db->query($sql);
	writeCache($cacheFilename, $newsMix);
	echo "[".date("c")."] Publicado[#{$aux->id_str}]: {$row['title']} // [{$row['created']}]\n";
	
		
		sleep(60*2); //publicar 1 noticia cada 10 minutos;	
		echo "\n";
}
unset($db);
echo "**FIN**\n\n";
//die('nada');

function writeCache($name, $data){
	$filename = explode(".", $name);
	if (count($filename) < 2) {
		$name.= ".tmp";
	}
	$name = "tmp/".$name;
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
	$expTimestamp = strtotime($expires, $fileCreation);
	if ($expTimestamp <= time()) {
		return false;
	}
	
	$handler = fopen($name, 'r');
	$value = unserialize(fread($handler, filesize($name)));
	fclose($handler);
	return $value;
}
?>