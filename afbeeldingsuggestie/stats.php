<?php
$title = 'afbeeldingsuggesties';
$pagetitle = 'Afbeeldingsuggesties';
$modified = '20 december 2008';

// Include files
require_once '../inc/webStart.inc.php';

$domain = 'nl.wikipedia.org';
$cluster = $db->getCluster($domain);

if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
    trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
}
$db_name = $db->getDatabase($domain);

$sug_total = 9133;

$sql = 'SELECT COUNT(1) AS c FROM (SELECT p.page_id, p.page_title, MIN(IF(il.il_to IS NOT NULL AND ils.il_to IS NULL, 1, 0)) AS nontrans FROM ' . $db_name . '.page AS p JOIN u_erwin85.img_pages AS img ON img.page_id = p.page_id LEFT JOIN ' . $db_name . '.imagelinks AS il ON il.il_from = p.page_id LEFT JOIN ' . $db_name . '.templatelinks ON tl_from = p.page_id LEFT JOIN ' . $db_name . '.page AS tl ON tl.page_title = tl_title AND tl.page_namespace = tl_namespace LEFT JOIN ' . $db_name . '.imagelinks AS ils ON ils.il_from = tl.page_id AND ils.il_to = il.il_to WHERE il.il_to IS NOT NULL AND img.img_done = 1 GROUP BY p.page_id HAVING nontrans = 1) AS rows;';

$q = $db->performQuery($sql, $cluster);

if (!$q) {
    $img_pages = 0;
} else {
    $result = mysql_fetch_assoc($q);
    $img_pages = intval($result['c']);
}

$sql = 'select count(1) as c from ' . $db_name . '.categorylinks where cl_to = \'Wikipedia:Suggestie_voor_afbeelding\';';
$q = $db->performQuery($sql, $cluster);
if (!$q) {
    $sug_open = 0;
} else {
    $result = mysql_fetch_assoc($q);
    $sug_open = intval($result['c']);
}

$sug_done = $sug_total - $sug_open;

if ($sug_done) {
    $sug_suc = round($img_pages / $sug_done * 100, 2);
} else {
    $sug_suc = 0;
}

// Start page
require_once '../inc/header.inc.php';
// Page content
?>
<p>Er zijn in totaal <?=$sug_total;?> suggesties geplaatst. Daarvan zijn er op dit moment nog <?=$sug_open;?> <a href="http://nl.wikipedia.org/wiki/Categorie:Wikipedia:Suggestie voor afbeelding " title="Categorie:Wikipedia:Suggestie voor afbeelding ">onbehandelde suggesties</a>.</p>
<p style="font-style:italic">Aan <?=$img_pages;?> artikelen is een afbeelding toegevoegd. Dit is <?=$sug_suc;?>% van het aantal behandelde suggesties.</p>
<h3>Afgelopen uur</h3>
<img src="hourly.png" alt="Aantal suggesties in het afgelopen uur" />
<h3>Afgelopen dag</h3>
<img src="daily.png" alt="Aantal suggesties in de afgelopen dag" />
<h3>Afgelopen week</h3>
<img src="weekly.png" alt="Aantal suggesties in de afgelopen week" />
<h3>Afgelopen maand</h3>
<img src="monthly.png" alt="Aantal suggesties in de afgelopen maand" />
<h3>Afgelopen jaar</h3>
<img src="yearly.png" alt="Aantal suggesties in het afgelopen jaar" />
<?php
require_once '../inc/footer.inc.php';
?>
