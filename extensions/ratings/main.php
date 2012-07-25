<?php
require_once '../../configure.php';
require_once '../../connector.php';
require_once 'clasificator.php';

if (!isset($db)) {
	try {
		$db = new Connector($GLOBALS['mysqlServer'], $GLOBALS['mysqlUsr'], $GLOBALS['$mysqlPwd'], $GLOBALS['$database']);
	} catch (Exception $e) {
		die($e->getMessage());
	}
}
$sql="select c.name as Category, f.id as FeedId, n.id as NewsId, n.title as NewsTitle, n.summary as Summary, n.rating as NewsRating, n.visits as NewsVisits, n.votes as NewsVotes, n.created as NewsCreated, n.modified as NewsModified, n.related_news_id as NewsRelated, n.hasImages as NewsHasImage, n.processed as Processed
from categories c
inner join feeds f on c.id=f.category_id
inner join news n on n.feed_id=f.id
where n.visits > 1 and (n.rating > 1 or
n.created >= DATE_SUB(CURDATE(), INTERVAL 3 DAY))
order by n.rating asc;";
$categories = $db->query($sql, true);
$classificator = new Clasificator();
while (($cateRow = mysql_fetch_assoc($categories)) == true) {
	echo ($cateRow['NewsCreated']>$cateRow['NewsModified']?$cateRow['NewsCreated']:$cateRow['NewsModified'])."\n";
	$rating = $classificator->classify($cateRow);
	echo "La noticia con ID={$cateRow['NewsId']} tiene un rating de {$rating}\n\n";
}

//seteo las noticias de ayer con un rating inferior en un punto a la de menor puntaje de hoy
$sql ="select rating from news n 
where n.created>=curdate()
order by rating asc
limit 1;";
$rnk = $db->query($sql);
$lowerRating = mysql_fetch_assoc($rnk);
if (!empty($lowerRating)) {
	$yesterdayRating = $lowerRating['rating']-1;
	$sql ="update news n set rating={$yesterdayRating} where n.created<curdate() and n.created>=DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
	$db->query($sql);
}

//seteo a las noticias de antes de ayer y anteriores un valor de 1 en el rating
$sql ="update news n set rating=1 where n.created<DATE_SUB(CURDATE(), INTERVAL 2 DAY)";
$db->query($sql);


//ranqueo los feeds de acuerdo a la sumatoria de los ratings de cada una de sus noticias
$sql = "update feeds, (select news.feed_id, sum(news.rating)as totRating from news group by news.feed_id) as N
set feeds.rating=N.totRating
where feeds.id=N.feed_id;";
$db->query($sql);

echo "Fin del procesamiento\n\n";
?>