<?php
//Set page variables needed for errorhandler
$title = 'projects';
$pagetitle = 'projects';
$modified = '29 March 2009';
date_default_timezone_set('UTC');

// Include files
require_once 'inc/webStart.inc.php';

$scripts = '<script type="text/javascript" language="javascript" src="delete.js"></script>';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>projects</b> lists all the projects from the Wikimedia Foundation.
</p>
<?php
// Get variables
if (!empty($_SERVER['QUERY_STRING'])) {
    $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $dbname = mysql_real_escape_string($_GET['dbname']);

    if ($family) {
        switch($family) {
            case 'commons':
                $domain = 'commons.wikimedia.org';
                break;
                case 'meta':
                        $domain = 'meta.wikimedia.org';
                        break;
            default:
                if ($lang) {
                    $domain = $lang . '.' . $family . '.org';
                }
                break;
        }
        } elseif ($dbname) {
            $dbname = (substr($dbname, -2) != '_p') ? $dbname . '_p' : $dbname;
            $domain = $db->getDomain($dbname);
        }

        if (!isset($domain)) {
        $sysops = mysql_real_escape_string($_GET['sysops']);
    } else {
        $sysops = -1;
    }
} else {
    $sysops = -1;
}

// Get list of databases
if (!isset($domain)) {
    $sql = 'SELECT dbname, lang, family, url, slice FROM meta_p.wiki where is_closed = 0;';
    $q = $db->performQuery($sql, 'any');
    if (!$q)
    {
            trigger_error('Database query failed.', E_USER_ERROR);
    }

    $itotalwikis = mysql_num_rows($q); //Anzahl durchsuchende Projekte

    while ($row = mysql_fetch_assoc($q)) {
        $adbname[] = $row['dbname'].'_p';
        $alang[] = $row['lang'];
        $afamily[] = $row['family'];
        $adomain[] = $row['url'];
        $acluster[] = $row['slice'];
    }
} else {
    $itotalwikis = 1;
    $adbname[] = $db->getDatabase($domain);
    $alang[] = $lang;
    $afamily[] = $family;
    $adomain[] = 'http://'.$domain;
    $acluster[] = $db->getCluster($domain);
}

$progress = 0; //Progress bar
$iwikis = 0;
echo 'Checking ' . $itotalwikis . ' wiki\'s.';
?>
<!--
<div id="laden">
<img style="width: 24px; height: 24px;" alt="Spinning wheel throbber"
 src="Spinning_wheel_throbber.gif" /> ... <i id="procent">0 %</i> <small><small id="projectload">...</small></small><br>
<?php
$count100 = 0;
do {
    echo "<span style=\"color: rgb(204, 204, 204);\" id=\"balken$count100\">|</span>";
    $count100 = $count100 +1;
} while ($count100 <= 100);

?>
<br /><small>Loading bar based on Luxo's <a href="//toolserver.org/~luxo/contributions/contributions.php">User contributions</a>.</small></div>
-->
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0"><tbody>
<tr>
        <td style = "text-align: right; width:300px;">
                Project (optional):
        </td>
        <td>
                <input type="text" name="lang" style = "width: 50px">
                <select name="family" >
                        <option value = "commons">Commons</option>
                        <option value = "meta">Meta</option>
                        <option value = "wikipedia" selected>.wikipedia.org</option>
                        <option value = "wikibooks">.wikibooks.org</option>
                        <option value = "wikisource">.wikisource.org</option>
                        <option value = "wikinews">.wikinews.org</option>
                        <option value = "wikiquote">.wikiquote.org</option>
                        <option value = "wiktionary">.wiktionary.org</option>
                </select>
        </td>
</tr>
<tr>
        <td style = "text-align: right; vertical-align:top" rowspan="2">
                Maximum number of sysops<br />(-1 to include all)
        </td>
        <td>
                <input name="sysops" type="text" value="<?=$sysops;?>">
        </td>
</tr>
<tr>
        <td>
                <input type="submit" value="Submit" name="submit">
        </td>
        <td>&nbsp;</td>
</tr></tbody></table></form>
<br clear="all" />
<table class="wikitable sortable">
<tr><th>Project</th><th>Sysops</th><th>Bureaucrats</th><th>Checkusers</th><th>Oversighters</th></tr>
<?php
foreach($adbname as $key => $dbname) {
    $cluster = $acluster[$key];
    $domain = preg_replace ( '/^http:\/\//' , 'https://' , $adomain[$key]);
    $procent = 100 / $itotalwikis * $progress;
#   echo"<script type=\"text/javascript\"> actuallload(\"".round($procent)." %\", \"(".$adomain[$key].")\",\"".round($procent)."\"); </script>\n";
    $progress += 1;
    if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
        continue;
        //trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
    } else {
        $sql = 'SELECT sum(if(ug_group = \'sysop\', 1, 0)) AS sysop, sum(if(ug_group = \'bureaucrat\', 1, 0)) AS bureaucrat, sum(if(ug_group = \'checkuser\', 1, 0)) AS checkuser, sum(if(ug_group = \'oversight\', 1, 0)) AS oversight FROM ' . $dbname . '.user_groups;';
        $q = $db->performQuery($sql, $cluster);
        if (!$q)
        {
                continue;
                //trigger_error('Database query failed: '.$sql . ' on ' . $cluster, E_USER_ERROR);
        }

        $isysop = mysql_result($q, 0, 'sysop');
        $ibureaucrat = mysql_result($q, 0, 'bureaucrat');
        $icheckuser = mysql_result($q, 0, 'checkuser');
        $ioversight = mysql_result($q, 0, 'oversight');

        if ($isysop <= $sysops || $sysops < 0) {
            echo '<tr><td><a href="' . $domain . '">' . $domain . '</a></td><td><a href="' . $domain . '/wiki/Special:Listusers/sysop">' . $isysop . '</a></td><td><a href="' . $domain . '/wiki/Special:Listusers/bureaucrat">' . $ibureaucrat . '</a></td><td><a href="' . $domain . '/wiki/Special:Listusers/checkuser">' . $icheckuser . '</a></td><td><a href="' . $domain . '/wiki/Special:Listusers/oversight">' . $ioversight . '</a></td></tr>';
        }
    }
}
echo '</table>';
$executiontime =  time() - $_SERVER['REQUEST_TIME'];
echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
require_once 'inc/footer.inc.php';
?>
