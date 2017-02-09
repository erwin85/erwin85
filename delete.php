<?php
//Set page variables needed for errorhandler
$title = 'delete';
$pagetitle = 'delete';
$modified = '17 March 2009';
date_default_timezone_set('UTC');

// Include files
require_once 'inc/webStart.inc.php';

$scripts = '<script type="text/javascript" language="javascript" src="multiproject.js"></script>';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>delete</b> lists pages nominated for speedy deletion. See also an <a href="/stewardbots/hat-web-tool/delete.php">alternative</a> with sorting by last edit date.
</p>
<?php
// Get variables
$lang = mysql_real_escape_string($_GET['lang']);
$family = mysql_real_escape_string($_GET['family']);
$dbname = mysql_real_escape_string($_GET['dbname']);
$sysops = mysql_real_escape_string($_GET['sysops']);
$timestamp = mysql_real_escape_string($_GET['timestamp']);
$namespaceFilter = mysql_real_escape_string($_GET['namespace']);

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
    $timestamp = (strlen($timestamp) == 8 ? $timestamp . '000000' : $timestamp);
    if (!preg_match('/\d{14}/', $timestamp) && !empty($timestamp)) {
        trigger_error('Invalid timestamp syntax. Usage: YYYYMMDD or YYYYMMDDHHMMSS.', E_USER_ERROR);
    } else {
        if ($sysops > 50) $sysops = 50; // It takes quite some time to check log actions, so limit sysops to 50.
    }
    if (empty($sysops)) $sysops = 0;
    if ($sysops > 50) $sysops = 50;
} else {
    $sysops = 0; // Default preference
}
$d1 = strftime('%Y%m%d', strtotime('-1 month'));
$d2 = strftime('%Y%m%d', strtotime('-3 months'));
?>
<div style="text-align:center; margin: 10px"><span style="font-weight:bold;">Quick links:</span> <a href="<?=$_SERVER['PHP_SELF'];?>?sysops=0">0 sysops</a> - <a href="<?=$_SERVER['PHP_SELF'];?>?sysops=3&timestamp=<?=$d1;?>">3 sysops / 1 month ago</a> - <a href="<?=$_SERVER['PHP_SELF'];?>?sysops=5&timestamp=<?=$d2;?>">5 sysops / 3 months ago</a></div>
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
    <table border="0">
        <tbody>
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
            <td style = "text-align: right; vertical-align:top">
                Maximum number of sysops:<br />(max. 50)
            </td>
            <td>
                <input name="sysops" type="text" value="<?=$sysops;?>">
            </td>
        </tr>
        <tr>
            <td style = "text-align: right; vertical-align:top">
                Latest sysop action before:<br />(Syntax: YYYYMMDD, YYYYMMDDHHMMSS or blank to ignore)
            </td>
            <td>
                <input name="timestamp" type="text" value="<?=$timestamp;?>">
            </td>
        </tr>
        <tr>
            <td style = "text-align: right; vertical-align:top">
                Namespace of the pages<br />(numerical ID)
            </td>
            <td>
                <input name="namespace" type="text" value="<?=$namespace;?>">
            </td>
        </tr>

        <tr>
            <td colspan="2">Note: When using a timestamp the number of sysops is limited to 50.</td>
        <tr>
            <td>&nbsp;</td>
            <td>
                <input type="submit" value="Submit" name="submit">
            </td>
        </tr>
        </tbody>
    </table>
</form>
<?php
// Get list of databases
if (!isset($domain)) {
    $sql = 'SELECT dbname, lang, family, url, slice FROM meta_p.wiki WHERE is_closed = 0;';
    $q = $db->performQuery($sql);
    if (!$q)
    {
        trigger_error('Database query failed.', E_USER_ERROR);
    }

    $itotalwikis = mysql_num_rows($q);

    while ($row = mysql_fetch_assoc($q)) {
        $adbname[] = $row['dbname'];
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
    $adomain[] = 'https://' . $domain;
    $acluster[] = $db->getCluster($domain);
}

$progress = 0; //Progress bar
$iwikis = 0;
$ipages = 0;
?>
<div id="progress">
<p>Checking <?=$itotalwikis;?> wiki's.</p>
<img style="width: 24px; height: 24px;" alt="Spinning wheel throbber"
 src="Spinning_wheel_throbber.gif" /> ... <i id="procent">0 %</i> <small><small id="projectload">...</small></small><br>
<?php
for ($i = 0; $i <= 100; $i++) {
    echo '<span style="color: rgb(204, 204, 204);" id="bar' . $i . '">|</span>';
}
?>
<br /><small>Loading bar based on Luxo's <a href="//toolserver.org/~luxo/contributions/contributions.php">User contributions</a>.</small></div>
<br clear="all" />
<?php
$outputTable = array();
foreach($adbname as $key => $dbname) {
    unset($ltimestamp);
    unset($isysops);
    $slast = '';
    $cluster = $acluster[$key];
    $domain = preg_replace ( '/^https?:\/\//' , '' , $adomain[$key]);
    $domain_url = preg_replace ( '/^http:\/\//' , 'https://' , $adomain[$key]);
    $procent = 100 / $itotalwikis * $progress;
    echo"<script type=\"text/javascript\">updateProgress(\"".round($procent)." %\", \"(".$domain.")\",\"".round($procent)."\"); </script>\n";
    $progress += 1;
    if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
        //trigger_error('Sorry, this database is not available at the moment.', E_USER_WARNING);
    } else {
        // Disabled for large projects
        if ($domain == 'en.wikipedia.org') {
            continue;
        }

        $sql = 'SELECT count(1) AS sysops FROM ' . $dbname . '_p.user_groups WHERE ug_group = \'sysop\'';
        $q = $db->performQuery($sql, $cluster);
        if (!$q) {
            //trigger_error('Database query failed.', E_USER_ERROR);
            continue;
        }

        $isysops = mysql_result($q, 0);

        if ($isysops > $sysops) {
            continue;
        }

        if (!empty($timestamp)) {
            $sql = 'SELECT user_name, log_timestamp
                    FROM ' . $dbname . '_p.logging_userindex
                    JOIN ' . $dbname . '_p.user
                    ON user_id = log_user
                    JOIN ' . $dbname . '_p.user_groups
                    ON ug_user = user_id
                    WHERE log_type IN (\'delete\', \'block\', \'protect\')
                    AND ug_group = \'sysop\'
                    ORDER BY log_timestamp DESC LIMIT 1;';
            $q = $db->performQuery($sql, $cluster);
            if (!$q) {
                //trigger_error('Database query failed.', E_USER_ERROR);
            } else {
                $result = mysql_fetch_assoc($q);
                $ltimestamp = $result['log_timestamp'];
                if ($ltimestamp > $timestamp) {
                    continue;
                }
                $slast = 'Last sysop action: ' . formatDate($ltimestamp) . ' by ' . $result['user_name'] . '; ';
            }
        }

        // Get template
        $sql = 'SELECT pl_title FROM ' . $dbname . '_p.pagelinks
                LEFT JOIN ' . $dbname . '_p.page
                ON page_id = pl_from
                WHERE page_title = \'Delete\'
                AND page_namespace = 10
                AND page_is_redirect = 1
                LIMIT 1';
        $q = $db->performQuery($sql, $cluster);
        if (!$q) {
            $template = 'Delete';
        } elseif (mysql_num_rows($q) == 1) {
            $template = mysql_result($q, 0);
        } else {
            $template = 'Delete';
        }

        if ( is_numeric( $namespaceFilter ) ) {
                $sqlNs = 'AND tl_from_namespace = ' . $namespaceFilter;
        } else {
                $sqlNs = '';
        }

        $sql = 'SELECT ns_name, page_title, rev_id, rev_timestamp, rev_user_text, rev_comment, rev_minor_edit
                FROM ' . $dbname . '_p.page
                LEFT JOIN ' . $dbname . '_p.templatelinks
                ON tl_from = page_id
                LEFT JOIN ' . $dbname . '_p.revision
                ON rev_page = page_id
                LEFT JOIN s51892_toolserverdb_p.namespace
                ON ns_id = page_namespace
                WHERE tl_title = "' . $template . '"
                AND tl_namespace = 10 ' . $sqlNs . '
                AND dbname = "' . $dbname . '_p"
                AND rev_timestamp = (SELECT max(rev_timestamp) FROM ' . $dbname . '_p.revision AS r
                                    WHERE rev_page = page_id)
                GROUP BY page_id';
        $q = $db->performQuery($sql, $cluster);
        if (!$q)
        {
            //echo mysql_error($db->link[$cluster]);
            //trigger_error('Database query failed.', E_USER_ERROR);
            continue;
        }
        //echo '<pre>' . $sql . '</pre>';
        //Number of results
        $iresults = mysql_num_rows($q);

        //Output results
        if ($iresults) {
            $ipages += $iresults;
            $iwikis += 1;
            $ioutput = '<h3>' . $domain . '</h3>
            There are <b>' . $iresults . '</b> linking pages to {{<a href="' . $domain_url . '/w/index.php?title=Special:Whatlinkshere/Template:' . $template . '&hideredirs=1&hidelinks=1" title="Pages linking to Template:' . $template . '">' . $template . '</a>}}.
             The project has <b>' . $isysops . '</b> <a href="' . $domain_url . '/w/index.php?title=Special:Userlist&group=sysop" title="Sysop list">sysops</a>.<br /><small>(' . $slast . '<a href="//toolserver.org/~pathoschild/stewardry/?wiki=' . $domain . '">Stewardry</a>)</small>
            <ul>';

            $i = 0;
            while ($row = mysql_fetch_assoc($q))
            {
                $namespace = str_replace(' ', '_', $row['ns_name']);
                if(!$namespace == '') $namespace .= ':';
                $title = $namespace . $row['page_title'];
                $comment = commentBlock($row['rev_comment'], $namespace . $row['page_title']);

                $ioutput .= '<li>' . ($row['rev_minor_edit'] ? '<span class="minor">m</span>' : '') . ' <a href="' . $domain_url . '/wiki/' . titleLink($title) . '" title="' . $title . '">' . str_replace('_', ' ', $title) . '</a>‎ (<a href="' . $domain_url . '/w/index.php?title=' . titleLink($page_title) . '&amp;diff=prev&amp;oldid=' . $row['rev_id'] . '">diff</a> | <a href="' . $domain_url . '/w/index.php?title=' . titleLink($title) . '&amp;action=history">hist</a> | <a href="' . $domain_url . '/w/index.php?title=Special:Log&page=' . titleLink($title) . '" title="logs">logs</a>) . . ' . formatDate($row['rev_timestamp'], 'en') . '. . <a href="' . $domain_url . '/wiki/User:' . $row['rev_user_text'] . '" title="User:' . $row['rev_user_text'] . '">' . $row['rev_user_text'] . '</a> (<a href="' . $domain_url . '/wiki/User_talk:' . $row['rev_user_text'] . '" title="User talk:' . $row['rev_user_text'] . '">talk</a> | <a href="' . $domain_url . '/wiki/Special:Contributions/' . $row['rev_user_text'] . '" title="Special:Contributions/' . $row['rev_user_text'] . '">contribs</a>) ' . $comment . '</li>';
                $i++;
            }
            $ioutput .= '</ul>';
            // echo $ioutput;
            $outputTable[] = array( 'number' => $iresults, 'output' => $ioutput );
        }
    }
}
// Obtain a list of columns
foreach ($outputTable as $key => $row) {
    $number[$key]  = $row['number'];
    $output[$key] = $row['output'];
}
array_multisort($number, SORT_ASC, $outputTable);
foreach ($outputTable as $key => $row) {
    echo $row['output'];
}

echo '<br /><hr />';
echo 'In total ' . $ipages . ' pages on ' . $iwikis . ' projects are nominated for speedy deletion. Checked ' . $itotalwikis . ' projects.';
$executiontime =  time() - $_SERVER['REQUEST_TIME'];
echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
require_once 'inc/footer.inc.php';
?>
