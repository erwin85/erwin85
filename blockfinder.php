<?php
//Set page variables needed for errorhandler
$title = 'block finder';
$pagetitle = 'block finder';
$modified = '27 April 2009';
date_default_timezone_set('UTC');

// Include files
require_once 'inc/webStart.inc.php';
require_once 'inc/MediaWiki-IP.php';

$scripts = '<script type="text/javascript" language="javascript" src="multiproject.js"></script>';

$cip = new IP();
// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>block finder</b> finds the block responsible for blocking a given IP address. It checks both global and local blocks as well as range and normal blocks.
</p>
<?php

if (!empty($_SERVER['QUERY_STRING']))
{
    $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $ip = mysql_real_escape_string($_GET['ip']);

    if ($family == 'commons') {
        $domain = 'commons.wikimedia.org';
    } elseif ($family =='meta') {
        $domain = 'meta.wikimedia.org';
    } else {
        if ($lang && $family) {
            $domain = $lang . '.' . $family . '.org';
        }
    }

    if (!$ip || !$cip->isIPAddress($ip)) {
        trigger_error("Please provide a valid IP address.", E_USER_ERROR);
    }

    $cluster = $db->getCluster($domain);

    if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
        trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
    } else {
        $db_name = $db->getDatabase($domain);
    }

    $iphex = $cip->toHex($ip);

    /*
    Only scan ranges which start in this /16, this improves search speed
    Blocks should not cross a /16 boundary.
    */
    $range = substr($iphex, 0, 4);

    // Global blocks
    $odomain = $domain; //Need this domain later. Should pass domain to commentBlock.
    $domain = 'meta.wikimedia.org';
    $sql = 'SELECT gb_address, gb_by, gb_reason, gb_timestamp, gb_anon_only, gb_expiry, gb_range_start, gb_range_end
        FROM centralauth_p.globalblocks
        WHERE ( gb_range_start LIKE \'' . $range . '%\'
                AND gb_range_start <= \'' . $iphex . '\'
                AND gb_range_end >= \'' . $iphex . '\'
              ) OR
              (
                gb_address = \'' . $ip . '\'
              )
        ORDER BY gb_timestamp ASC;';

    $q = $db->performQuery($sql, $db->getCluster($domain));
    //echo '<pre>' . $sql . '</pre>';
    if (!$q) {
        trigger_error('Database query failed.', E_USER_NOTICE);
        //echo '<pre>' . $sql . '</pre>';
    }

    echo '<h3>Global blocks</h3>';
    if(mysql_num_rows($q)) {
        echo '<ul>';
        while ($row = mysql_fetch_assoc($q))
        {
            $blockoptions = '';
            $blockoptions .= ($row['gb_anon_only'] ? 'anon. only, ' : '');
            $blockoptions = ($blockoptions ? ' (' . substr($blockoptions, 0, -2) . ')' : '');
            $reason = commentBlock($row['gb_reason'], '');
            $expiry = (preg_match('/\d{14,14}/', $row['gb_expiry']) ? formatDate($row['gb_expiry']) : $row['gb_expiry']);
            echo '<li>At ' . formatDate($row['gb_timestamp']) . ' <a href="http://' . $domain . '/wiki/User:' . $row['gb_by'] . '" title="User:' . $row['gb_by'] . '">' . $row['gb_by'] . '</a> blocked <a href="http://' . $domain . '/w/index.php?title=Special:GlobalBlockList&ip=' . $row['gb_address'] . '" title="Blocklist">' . $row['gb_address'] . '</a>' . $blockoptions . $reason . ' until ' . $expiry . '.</li>';
        }
        echo '</ul>';
    } else {
        echo '<ul><li><span style="font-style:italic">None</span></li></ul>';
    }

    // Local blocks
    $domain = $odomain;
    // Get list of databases
    if (!isset($domain)) {
        $sql = 'SELECT dbname, lang, family, url, slice FROM meta_p.wiki WHERE is_closed = 0 ORDER BY size DESC;';
        $q = $db->performQuery($sql, 'any');
        if (!$q)
        {
                trigger_error('Database query failed.', E_USER_ERROR);
        }

        $itotalwikis = mysql_num_rows($q); //Anzahl durchsuchende Projekte

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
        $adomain[] = 'http://' . $domain;
        $acluster[] = $db->getCluster($domain);
    }

    echo '<h3>Local blocks</h3>' . "\n";
    $progress = 0; //Progress bar
    $iwikis = 0;
    echo 'Checking ' . $itotalwikis . ' wiki\'s.' . "\n";
    ?>
    <div id="progress">
    <img style="width: 24px; height: 24px;" alt="Spinning wheel throbber"
     src="Spinning_wheel_throbber.gif" /> ... <i id="procent">0 %</i> <small><small id="projectload">...</small></small><br />
    <?php
    for ($i = 0; $i <= 100; $i++) {
        echo '<span style="color:rgb(204, 204, 204);" id="bar' . $i . '">|</span>';
    }
    echo '<br /><small>Loading bar based on Luxo\'s <a href="http://toolserver.org/~luxo/contributions/contributions.php">User contributions</a>.</small></div>';

    foreach($adbname as $key => $db_name) {
        $cluster = $acluster[$key];
        $domain = preg_replace ( '/^http:\/\//' , '' , $adomain[$key]);
        $domain_url = preg_replace ( '/^http:\/\//' , 'https://' , $adomain[$key]);

        $procent = 100 / $itotalwikis * $progress;
        echo"<script type=\"text/javascript\">updateProgress(\"".round($procent)." %\", \"(".$domain.")\",\"".round($procent)."\"); </script>\n";
        $progress += 1;

        $sql = 'SELECT ipb_address, user_name, ipb_reason, ipb_timestamp, ipb_auto, ipb_anon_only, ipb_create_account, ipb_expiry, ipb_range_start, ipb_range_end, ipb_enable_autoblock, ipb_block_email
                FROM ' . $db_name . '.ipblocks
                LEFT JOIN ' . $db_name . '.user
                ON user_id = ipb_by
                WHERE ( ipb_range_start LIKE \'' . $range . '%\'
                        AND ipb_range_start <= \'' . $iphex . '\'
                        AND ipb_range_end >= \'' . $iphex . '\'
                      ) OR
                      (
                        ipb_address = \'' . $ip . '\'
                      )
                ORDER BY ipb_timestamp ASC;';
        $q = $db->performQuery($sql, $cluster);

        //echo '<pre>' . $sql . '</pre>';
        if (!$q) {
            //trigger_error('Database query failed.', E_USER_NOTICE);
            continue;
        }

        if(mysql_num_rows($q)) {
            echo '<h4>' . $domain . '</h4>';
            echo '<ul>' . "\n";
            while ($row = mysql_fetch_assoc($q))
            {
                $blockoptions = '';
                $blockoptions .= ($row['ipb_create_account'] ? 'account creation blocked, ' : '');
                $blockoptions .= ($row['ipb_anon_only'] ? 'anon. only, ' : '');
                $blockoptions = ($blockoptions ? ' (' . substr($blockoptions, 0, -2) . ')' : '');
                $reason = commentBlock($row['ipb_reason'], '');
                $expiry = (preg_match('/\d{14,14}/', $row['ipb_expiry']) ? formatDate($row['ipb_expiry']) : $row['ipb_expiry']);
                echo '<li>At ' . formatDate($row['ipb_timestamp']) . ' <a href="' . $domain_url . '/wiki/User:' . $row['user_name'] . '" title="User:' . $row['user_name'] . '">' . $row['user_name'] . '</a> blocked <a href="' . $domain_url . '/w/index.php?title=Special:BlockList&ip=' . $row['ipb_address'] . '" title="Blocklist">' . $row['ipb_address'] . '</a>' . $blockoptions . $reason . ' until ' . $expiry . '.</li>';
            }
            echo '</ul>' . "\n";
        }
    }
    $executiontime =  time() - $_SERVER['REQUEST_TIME'];
    echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
} else {
?>
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0"><tbody>
<tr>
    <td style = "text-align: right; width:300px;">
        Project:
    </td>
    <td>
        <input type="text" name="lang" style = "width: 50px" onchange="NamespaceLookup(event); return false;">
        <select name="family" onchange="NamespaceLookup(event); return false;">
            <option value = "commons">Commons</option>
            <option value = "meta">Meta</option>
            <option value = "wikipedia" selected>.wikipedia.org</option>
            <option value = "wikibooks">.wikibooks.org</option>
            <option value = "wikisource">.wikisource.org</option>
            <option value = "wikinews">.wikinews.org</option>
            <option value = "wikiquote">.wikiquote.org</option>
            <option value = "wiktionary">.wiktionary.org</option>
            <option value = "wikiversity">.wikiversity.org</option>
        </select>
    </td>
</tr>
<tr>
    <td style = "text-align: right; width:300px; font-style:italic;">
        Leave project blank to scan all projects.
    </td>
    <td></td>
</tr>
<tr>
    <td style = "text-align: right;">
        IP Address
    </td>
    <td>
        <input type="text" name="ip">
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
require_once 'inc/footer.inc.php';
?>
