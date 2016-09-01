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

$sql = 'select count(1) as c from ' . $db_name . '.categorylinks where cl_to = \'Wikipedia:Suggestie_voor_afbeelding\';';
$q = $db->performQuery($sql, $cluster);
if (!$q) {
    $sug_open = 0;
} else {
    $result = mysql_fetch_assoc($q);
    $sug_open = intval($result['c']);
}

// Start page
require_once '../inc/header.inc.php';
// Page content
?>
<p>Deze pagina is onderdeel van <a href="http://nl.wikipedia.org/wiki/Wikipedia:Wikiproject/Afbeeldingsuggestie" title="Wikipedia:Wikiproject/Afbeeldingsuggestie">Wikipedia:Wikiproject/Afbeeldingsuggestie</a>. Een project met als doel om artikelen van geschikte afbeeldingen te voorzien op basis van andere Wikipedia's. Er zijn op dit moment <?=$sug_open;?> <a href="http://nl.wikipedia.org/wiki/Categorie:Wikipedia:Suggestie voor afbeelding " title="Categorie:Wikipedia:Suggestie voor afbeelding ">onbehandelde suggesties</a>. Op deze pagina's kun je de <a href="stats.php" title="Voortgang">voortgang</a> volgen, een <a href="gallery.php" title="Galerij">galerij</a> bekijken en <a href="category.php" title="Categorie">suggesties voor een bepaalde categorieboom</a> bekijken.</p>
<?php
require_once '../inc/footer.inc.php';
?>
