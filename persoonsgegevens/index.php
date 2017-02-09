<?php
ob_start();

//Set page variables needed for errorhandler
$title = 'erwin85 - persoonsgegevens';
$pagetitle = 'persoonsgegevens';
$modified = '15 januari 2008';

//Include files
require_once 'inc/init.inc.php';
require_once 'inc/template.inc.php';
require_once '/home/erwin85/libs/php/various.inc.php';
$t = new Template('inc/template.html');

//Set template variables
$t->setVar('title', $title);
$t->setVar('scripts', '');
$t->setVar('pagetitle', $pagetitle);
$t->setVar('replag_s1', $db->getReplag('sql-s1'));
$t->setVar('replag_s2', $db->getReplag('sql-s2'));
$t->setVar('replag_s3', $db->getReplag('sql-s3'));
$t->setVar('warning', $db->getWarning());
$t->setVar('modified', $modified);

// Page content
?>
<p>
<b>persoonsgegevens</b> is een aanvulling op <a href="http://nl.wikipedia.org/wiki/Wikipedia:Persoonsgegevens" title="Wikipedia:Persoonsgegevens">Wikipedia:Persoonsgegevens</a>.
</p>
<?php
// Get and check parameters
if (!empty($_SERVER['QUERY_STRING']))
{
        // Get variables
        if (!get_magic_quotes_gpc()) {
                $lang = mysql_real_escape_string($_GET['lang']);
        } else {
        $lang = $_GET['lang'];
        }

        $cluster ='sql-s2';

        if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
            trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
        } else {
        $sql .= " ORDER BY page_len ASC LIMIT " . $offset . ", " . $limit;
        $q = $db->performQuery($sql, $cluster);
        if (!$q)
        {
                trigger_error('Database query failed.', E_USER_ERROR);
        }

        while ($row = mysql_fetch_assoc($q))
        {
                $namespace =  $db->getNamespace($row['page_namespace'], $db_name);
                if(!$namespace == '') $namespace .= ':';
                echo '<tr><td><a href="http://' . $domain . '/wiki/' . str_replace('%2F', '/', urlencode(str_replace(' ', '_', $namespace . $row['page_title']))) . '" title="' . $namespace . $row['page_title'] . '">' . $namespace . $row['page_title'] . '</a></td<td>' .  $row['page_len'] . '</td></tr>';
        }
        echo '</table>';
        $executiontime =  time() - $_SERVER['REQUEST_TIME'];
        echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
    }
} else {
?>
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0"><tbody>
<tr>
        <td style = "text-align: right; width:300px;">
                Type:
        </td>
        <td>
                <select name="type">
                    <option value="birth">Geboren</option>
                    <option value="death">Overleden</option>
                </select>
        </td>
</tr>
<tr>
        <td style = "text-align: right;">
                Tussen
        </td>
        <td>
                <input name="day1" type="text" value="01" style="width:20px">
                <select name="month1">
                    <option value="januari" selected>januari</option>
                    <option value="februari">februari</option>
                </select>
                <input name="year1" type="text" value="1970" style="width:40px">
        </td>
</tr>
<tr>
        <td style = "text-align: right;">
                en
        </td>
        <td>
                <input name="day2" type="text" value="01" style="width:20px">
                <select name="month2">
                    <option value="januari" selected>januari</option>
                    <option value="februari">februari</option>
                </select>
                <input name="year2" type="text" value="1970" style="width:40px">
        </td>
</tr>
<tr>
        <td>&nbsp;</td>
        <td>
                <input type="submit" value="Submit" name="submit">
        </td>
        <td>&nbsp;</td>
</tr></tbody></table></form>
<?php
}
$t->setVar('content', ob_get_clean());
echo $t->toString();
?>
