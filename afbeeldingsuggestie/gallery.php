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

// Start page
require_once '../inc/header.inc.php';
// Page content
?>
<p>Door de links bij de suggesties te volgen, kun je hier een galerij van de afbeeldingen bekijken.</p>
<?php
if (!empty($_SERVER['QUERY_STRING']))
{
    $title = mysql_real_escape_string($_GET['title']);
    $title = str_replace(' ', '_', $title);
    $title = ucfirst($title);

    $sql = 'SELECT il_to, ll_lang, il_commons, COUNT(ll_lang) AS ll_langs
            FROM u_erwin85.img_imagelinks
            WHERE page_title = \'' . $title . '\'
            GROUP BY il_to
            ORDER BY il_commons DESC, ll_langs DESC, ll_lang ASC;';

    $q = $db->performQuery($sql, $cluster);

    if (!$q) {
        trigger_error('Database query failed.', E_USER_NOTICE);
    }
    if (mysql_num_rows($q) > 0) {
        echo 'Onderstaande afbeeldingen zijn gesuggereerd voor <a href="http://nl.wikipedia.org/wiki/' . $title . '">' . $title . '</a>.';
        echo '<table class="gallery" cellpadding="0" cellspacing="0">';
        echo '<tbody><tr>';
        $i = 1;
        while ($row = mysql_fetch_assoc($q))
        {
            if ($i % 4 == 1) {
                echo '</tr><tr>';
            }
            $image = $row['il_to'];
            if ($row['il_commons'] == 1) {
                $furl = 'http://commons.wikimedia.org/w/thumb.php';
                $url = 'http://commons.wikimedia.org/wiki/';
                $project = '<span style="font-weight:bold">(C)</span>';
            } else {
                $furl = 'http://' . $row['ll_lang'] . '.wikipedia.org/w/thumb.php';
                $url = 'http://' . $row['ll_lang'] . '.wikipedia.org/wiki/';
                $project = '<span style="font-weight:bold">(' . $row['ll_lang'] . ')</span>';
            }
            $furl .= '?f=' . $image . '&w=120';
            $url .= 'Image:' . $image;
            $image = str_replace('_', ' ', $image);
            echo <<<END
<td><div class="gallerybox" style="width: 155px;">
<div class="thumb" style="padding: 13px 0pt; width: 150px;">
<div style="margin-left: auto; margin-right: auto; width: 120px;"><a href="$url" class="image" title=""><img alt="$image" src="$furl" style="max-height:120px; max-width:120px;"></a></div>
</div>
<div class="gallerytext">
<p>$image $project</p>
</div>
</div>
</td>
END;
            $i++;
        }
        echo '</tr></tbody></table>';
    } else {
        echo '<p style="font-style:italic;">Het is helaas niet meer mogelijk een galerij voor deze pagina te bekijken.</p>';
    }
}
require_once '../inc/footer.inc.php';
?>
