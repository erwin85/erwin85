<?php
function getPage($pagename, $forcelive=False)
{
    if(!$forcelive) {
        $ch = curl_init('http://toolserver.org/~daniel/WikiSense/WikiProxy.php?wiki=meta.wikimedia.org&title=' . urlencode($pagename));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
        $output = curl_exec($ch);
        curl_close($ch);
    } else {
        $ch = curl_init('https://meta.wikimedia.org/w/index.php?title=' . urlencode($pagename) . '&action=raw&ctype=text');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Wikipedia Bot - xwiki - http://toolserver.org/~erwin85/xwiki.php');
        $output = curl_exec($ch);
        curl_close($ch);
    }
    return $output;
}

function formatRow($row, $inreport, $title, $domain, $wiki)
{
    // Get global rights
    global $db;
    if ($row['rev_user']) {
        $sql = 'SELECT GROUP_CONCAT(gug_group SEPARATOR \', \') AS grights
                FROM centralauth_p.localuser
                LEFT JOIN centralauth_p.globaluser
                ON gu_name = lu_name
                LEFT JOIN centralauth_p.global_user_groups
                ON gug_user = gu_id
                WHERE lu_name = \'' . mysql_real_escape_string($row['rev_user_text']) . '\'
                AND lu_wiki = \'' . substr($wiki, 0, -2) . '\';';

        $q = $db->performQuery($sql, 'any');
        if (!$q) {
            trigger_error('Database query failed.', E_USER_NOTICE);
            $grights = '';
            //echo $sql;
            //echo mysql_error($db->link[$cluster]);
        } else {
            $result = mysql_fetch_assoc($q);
            $grights = $result['grights'];
        }
    } else {
        $grights = '';
    }

    $comment = commentBlock($row['rev_comment'], $title);
    return "\n" . '<li>' . ($inreport ? '<strong>(R)</strong> ' : '') . formatDate($row['rev_timestamp'], 'en') . ' <i>by <a href = "http://' . $domain . '/wiki/User:' . $row['rev_user_text'] . '">' . $row['rev_user_text'] . '</a></i> (' . number_format($row['editcount']) . ' <a href="http://' . $domain . '/wiki/Special:Contributions/' . $row['rev_user_text'] . '" title="Contributions">edits</a>' . ($row['rights'] ? '; ' . $row['rights'] : '') . ($grights ? '; ' . $grights : '') . ') (<a href="http://' . $domain . '/w/index.php?title=' .  rawurlencode($title) . '&amp;diff=prev&amp;oldid=' . $row['rev_id'] . '" title="' . $title . '">diff</a>)' . ($row['rev_minor_edit'] == '1' ? ' <span class="minor">m</span>' : '') . ' <a href="http://' . $domain . '/wiki/' . $title . '" title="' . $title . '">' . str_replace('_', ' ', $title) . '</a> ' . $comment . ($row['page_latest'] == $row['rev_id'] ? ' <strong> (top)</strong>' : '') . ' (' . ($row['page_latest'] == $row['rev_id'] ? '<a href="http://' . $domain . '/w/index.php?title=' .  rawurlencode($title) . '&amp;diff=prev&amp;oldid=' . $row['rev_id'] . '&amp;diffonly=yes&amp;xwikirollback" title="' . $title . '">rollback</a> | ' : '' ) . '<a href="http://' . $domain . '/w/index.php?title=' . rawurlencode($title) . '&amp;action=edit&amp;undo=' . $row['rev_id'] . '" title="' .  $title . '">undo</a>)</li>';
}
//Set page variables needed for errorhandler
$title = 'xwiki';
$pagetitle = 'xwiki';
$modified = '27 February 2010';
date_default_timezone_set('UTC');

// Include files
require_once 'inc/webStart.inc.php';

$scripts =  '<script type="text/javascript" language="javascript" src="xwiki.js"></script>';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>xwiki</b> checks what wiki's listed in a xwiki spam report still contain the added link.
</p>
<?php
if (!empty($_SERVER['QUERY_STRING']))
{
    $report = mysql_real_escape_string($_GET['report']);
    $limit = mysql_real_escape_string($_GET['limit']);
    $forcelive = mysql_real_escape_string($_GET['forcelive']);
    $forcelive = ($forcelive ? $forcelive : 0);
    if (!$report) {
        trigger_error('Please enter the report name.', E_USER_ERROR);
    }

    if ($limit)    {
        $limit = intval($limit);
        if ($limit > 100) {
            $limit = 100;
        } elseif ($limit < 1) {
            $limit = 1;
        }
    } else {
        $limit = 10;
    }

    if (substr($report, 0, 18) == 'User:COIBot/XWiki/') {
        $spamdomain = substr($report, 18);
    } elseif (substr($report, 0, 18) == 'User:COIBot/Local/') {
        $spamdomain = substr($report, 18);
    } elseif (substr($report, 0, 24) == 'User:COIBot/LinkReports/') {
        $spamdomain = substr($report, 24);
    } elseif (substr($report, 0, 22) == 'User:SpamReportBot/cw/') {
        $spamdomain = substr($report, 22);
    } else {
        trigger_error('This tool only works for subpages of [[User:COIBot/XWiki]].', E_USER_ERROR);
    }

    $remspam = '*.' . $spamdomain;

    $parts = explode('.',$spamdomain);
    $len = count($parts);
    switch($len) {
        case 1:
            trigger_error('Not a valid domain.', E_USER_ERROR);
            break;
        default:
            //$el_index = 'http://com.example.sub.%';
            $el_index = 'http://';
            for ($i = $len - 1; $i >= 0; $i--) {
                $el_index .= $parts[$i] . '.';
            }
            $el_index .= '%';
            break;
    }

    if ($forcelive) {
        $contents = getPage($report, $forcelive);
    } else {
        $contents = getPage($report);
    }

    $iprojects = 0;
    $ilinks = 0;

    if (!$contents) {
        trigger_error('Could not get report. Are you sure the page exists?', E_USER_ERROR);
    }

    $wikis = array();
    //Get all diff links from template.
    preg_match_all('/\{\{User:COIBot\/EditSummary\|.*?wiki=(?P<domain>[^\|]*?)\|.*?revid=(?P<oldid>[^\|]*?)\|.*?\}\}/', $contents, $matches, PREG_SET_ORDER);
    if (count($matches) == 0) {
        // Try the old format.
        echo 'Trying old format';
        preg_match_all('/\[http:\/\/(?P<domain>[^ ]*?)\/w\/index.php\?(?:title\=[^\&]*?\&|)(?:diff=(?P<diff>[^\&]*?)|oldid=(?P<oldid>[^\&]*?))(?:\&[^\ ]*?|) diff\]/', $contents, $matches, PREG_SET_ORDER);

    }
    if (count($matches)) {
        echo '<p>Retrieved ' . count($matches) . ' edits from <a href="http://meta.wikimedia.org/wiki/' . $report . '">' . $report . '</a> (<a href="http://' .$spamdomain. '" rel="nofollow" title="' . $spamdomain . '">' . $spamdomain . '</a>). ' . ($forcelive ? 'Retrieved text directly from Meta. Only use this if s3\'s replag is high. ' : 'Retrieved text from the toolserver. If s3\'s replag is high you might want to <a href="' . $_SERVER['REQUEST_URI'] . '&forcelive=1">force using Meta directly</a>. ') . 'Searching for \'' . $el_index . '\'. Showing at most ' . $limit . ' edits per page.</p>';
        echo '<p>Edits reported in the bot report are marked as <strong>(R)</strong> and pages linking to this domain are marked as <strong>(L)</strong>.<br /><strong>NOTE:</strong> For the "rem"-links to work you need to add <a href="http://meta.wikimedia.org/wiki/User:Mike.lifeguard/removeSpam.js">User:Mike.lifeguard/removeSpam.js</a> to your monobook.js.</p>';
    } else {
        echo '<p>Retreived 0 edits from <a href="http://meta.wikimedia.org/wiki/' . $report . '">' . $report . '</a>. Are you sure the page exists? ' . ($forcelive ? 'Retrieved text directly from Meta. Only use this if s3\'s replag is high. ' : 'Retrieved text from the toolserver. If s3\'s replag is high you might want to <a href="' . $_SERVER['REQUEST_URI'] . '&forcelive=1">force using Meta directly</a>.' ) . '</p>';
    }

    echo '<div id="nolinks" style="font-weight:bold;">Finding links…</div>';
    // Get page titles
    $nopages = 0;
    foreach($matches as $match) {
        $domain = $match['domain'];
        $db_name = $db->getDatabase($domain);
        $cluster = $db->getCluster($domain);

        $revid = (!empty($match['diff']) ? $match['diff'] : $match['oldid']);

        $sql = 'SELECT page_id, page_namespace, ns_name, page_title FROM ' . $db_name . '.revision
        LEFT JOIN ' . $db_name . '.page
        ON page_id = rev_page
        LEFT JOIN toolserver.namespace
        ON ns_id = page_namespace
        WHERE rev_id = \'' . $revid . '\'
        AND dbname = \'' . $db_name . '\'
        LIMIT 1;';

        $q = $db->performQuery($sql, $cluster);

        if (!$q) {
            $nopages += 1;
            continue;
            //trigger_error('Database query failed.', E_USER_NOTICE);
            //echo $sql;
            //echo mysql_error($db->link[$cluster]);
        }

        $result = mysql_fetch_assoc($q);

        if($result['page_title']) {

            $page_namespace = $result['page_namespace'];
            $page_title = $result['page_title'];
            $title = ($page_namespace ? $result['ns_name'] . ':' . $page_title : $page_title);
            $page_id = $result['page_id'];

            $wikis[$domain][$title][] = array('match' => $match[0], 'revid' => $revid, 'page_id' => $page_id, 'page_namespace' => $page_namespace, 'page_title' => $page_title);
        }
    }

    if ($nopages) {
        echo '<p style="margin-top:30px;"><span style="color:red; font-weight: bold;">Could not find the page title for ' . $nopages . ' edit(s).</span></p>';
    }

    //Sort the projects so aa.wikipedia.org is first
    ksort($wikis);
    foreach($wikis as $domain => $pages) {
        $lpages = array();
        $cluster = $db->getCluster($domain);
        echo '<h3>' . $domain . '</h3>';

        if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
            trigger_error('Sorry, the database is not available at the moment.', E_USER_NOTICE);
        } else {
            $replag = $db->getReplag($cluster, $machine = True);
            if ($replag > 300) {
                echo '<span style="color:red; font-weight: bold;">Replag is ' . $db->getReplag($cluster) . '!</span><br />';
            } elseif ($replag > 120) {
                echo '<span style="font-style: italic;">Replag is ' . $db->getReplag($cluster) . '.</span><br />';
            }

            $db_name = $db->getDatabase($domain);

            $sql = 'SELECT ns_name, page_title, el_to
                    FROM ' . $db_name . '.page
                    LEFT JOIN ' . $db_name . '.externallinks
                    ON el_from = page_id
                    LEFT JOIN toolserver.namespace
                    ON ns_id = page_namespace
                    AND dbname = \'' . $db_name . '\'
                    WHERE el_index LIKE \'' . mysql_real_escape_string($el_index) . '\'
                    GROUP BY page_id;';
            $q = $db->performQuery($sql, $cluster);

            if (!$q) {
                echo '<span style="color:red; font-weight: bold;">Could not retrieve pages.</span><br />';
                continue;
                //trigger_error('Database query failed.', E_USER_NOTICE);
                //echo $sql;
                //echo mysql_error($db->link[$cluster]);
            }
            $count = mysql_num_rows($q);

            while ($row = mysql_fetch_assoc($q))
            {
                $page = ($row['ns_name'] ? $row['ns_name'] . ':' . $row['page_title'] : $row['page_title']);
                $lpages[$page] = $row['el_to'];
            }

            echo 'There ' . ($count != 1 ? 'are <b>' . $count . '</b> links' : 'is <b>1</b> link') . ' to this domain. (' . ($count ? '<a href="javascript:showHide(\'' . $domain . '\')" id="l-' . $domain . '">Show</a> | ' : '') . '<a href="http://' . $domain . '/wiki/Special:Linksearch/*.' . $spamdomain . '">Linksearch</a>)';
            //http://nl.wikipedia.org/wiki/Tristram_Shandy?linkmodified=yes&action=edit&remspam=*.tristramshandyweb.it&options=citeweb%2Cinline&usesummary=Removing%20external%20link%3A%20__LINK__%20--%20per%20%5B%5Bm%3ATalk%3ASpam%20blacklist%5D%5D
            if($count) {
                $iprojects += 1;
                $ilinks += $count;
                echo '<div id="' . $domain . '" class="linkingpages" style="font-size:90%; margin:.5em; border: 1px solid #aaa; padding: 5px; clear: both; display:none;">';
                echo '<strong>Linking pages:</strong><ul>';
                $i = 0;
                foreach ($lpages as $page=>$link) {
                    if($i > 50) {
                        echo '<li>etc.</li><li>There are too many pages to include. Use the project\'s linksearch for a complete list.</li>';
                        break;
                    }
                    $removeurl = 'http://' . $domain . '/w/index.php?title=' .rawurlencode($page) . '&amp;action=edit&amp;linkmodified=yes&amp;remspam=' . $remspam . '&amp;report=' . rawurlencode($report);
                    echo '<li><a href="http://' . $domain . '/wiki/' . $page . '" title="' . $page . '">' . str_replace('_', ' ', $page) . '</a> links to <a href="' . $link . '">' . $link . '</a> <span class="removespam" target="_blank"> (<a href="' . $removeurl . '" class="removelink">rem</a>)</span>.</li>';
                    $i++;
                }
                echo '</ul></div>';
            }

            // Reported edits
            echo '<ul>';
            foreach ($pages as $title=>$page) {
                $page_id = $page[0]['page_id'];
                unset($urls);
                unset($diffs);

                foreach($page as $adiff) {
                    $diffs[] = $adiff['revid'];
                    $urls[] = substr($adiff['match'], 1, strlen($adiff['match']) - 6);
                }

                $removeurl = 'http://' . $domain . '/w/index.php?title=' .rawurlencode($title) . '&amp;action=edit&amp;linkmodified=yes&amp;remspam=' . $remspam . '&amp;report=' . rawurlencode($report);
                echo '<li><b>' . str_replace('_', ' ', $title) . '</b> ' . (array_key_exists($title, $lpages) ? '<strong>(L)</strong> ' : '') . '( <a href="http://' . $domain . '/w/index.php?title=' . rawurlencode($title) . '&amp;action=history" title="' .  $title . '">hist</a> | <a href="http://' . $domain . '/w/index.php?title=' . rawurlencode($title) . '&amp;action=edit" title="' .  $title . '">edit</a>' . (array_key_exists($title, $lpages) ? ' <span class="removespam"> | <a href="' . $removeurl . '" class="removelink" target="_blank">rem</a></span>' : '') . ' ) (Reported edits: ';
                foreach($urls as $url) {
                    echo ' <a href="' . $url . '">diff</a>;';
                }
                echo ")\n<ul>";

                $sql = 'SELECT r1.rev_id, r1.rev_user, r1.rev_user_text, r1.rev_comment, r1.rev_timestamp, r1.rev_minor_edit, page_latest,
                        IF(rev_user != 0, (SELECT user_editcount FROM ' . $db_name . '.user WHERE user_id = r1.rev_user), (SELECT COUNT(1) FROM ' . $db_name . '.revision AS r2 WHERE r2.rev_user_text = r1.rev_user_text)) AS editcount,
                        IF (r1.rev_user != 0, (SELECT GROUP_CONCAT(ug_group SEPARATOR \', \') FROM ' . $db_name . '.user_groups WHERE ug_user = r1.rev_user GROUP BY ug_user), \'\') AS rights
                        FROM ' . $db_name . '.revision AS r1
                        LEFT JOIN ' . $db_name . '.page
                        on page_id = r1.rev_page
                        WHERE page_id = \'' . $page_id . '\'
                        AND r1.rev_id >= ' . $diffs[0] . '
                        AND r1.rev_deleted = 0
                        ORDER BY r1.rev_timestamp ASC LIMIT ' . $limit . ';';
                //echo '<pre>' . $sql . '</pre>';
                $q = $db->performQuery($sql, $cluster);
                if (!$q) {
                    trigger_error('Database query failed.', E_USER_NOTICE);
                    echo '</ul>'; // To close list.
                    continue;
                    //echo $sql;
                    //echo mysql_error($db->link[$cluster]);
                }

                $xwikidiffs = array();
                while ($row = mysql_fetch_assoc($q))
                {
                    $inreport = (in_array($row['rev_id'], $diffs));
                    $xwikidiffs[] = $row['rev_id'];
                    echo formatRow($row, $inreport, $title, $domain, $db_name);
                }

                // Include last reported edit
                if (!empty($xwikidiffs) && !in_array(end($diffs), $xwikidiffs)) {
                    echo '</ul><p style="font-style:italic;">Last reported diff</p><ul>';

                    $sql = 'SELECT r1.rev_id, r1.rev_user, r1.rev_user_text, r1.rev_comment, r1.rev_timestamp, r1.rev_minor_edit, page_latest,
                            IF(r1.rev_user != 0, (SELECT user_editcount FROM ' . $db_name . '.user WHERE user_id = r1.rev_user), (SELECT COUNT(1) FROM ' . $db_name . '.revision AS r2 WHERE r2.rev_user_text = r1.rev_user_text)) AS editcount,
                            IF (r1.rev_user != 0, (SELECT GROUP_CONCAT(ug_group SEPARATOR \', \') FROM ' . $db_name . '.user_groups WHERE ug_user = r1.rev_user GROUP BY ug_user), \'\') AS rights
                            FROM ' . $db_name . '.revision AS r1
                            LEFT JOIN ' . $db_name . '.page
                            on page_id = r1.rev_page
                            WHERE page_id = \'' . $page_id . '\'
                            AND r1.rev_id >= ' . end($diffs) . '
                            ORDER BY r1.rev_timestamp ASC LIMIT 5;';

                    $q = $db->performQuery($sql, $cluster);
                    if (!$q) {
                        trigger_error('Database query failed.', E_USER_NOTICE);
                        continue;
                    }

                    while ($row = mysql_fetch_assoc($q))
                    {
                        $inreport = (in_array($row['rev_id'], $diffs));
                        echo formatRow($row, $inreport, $title, $domain, $db_name);
                    }
                }
                echo '</ul>';
            }
            echo '</ul>';
        }
    }

    echo '<hr />';

    if ($ilinks) {
        $nolinks = 'There ' . ($ilinks != 1 ? 'are ' . $ilinks . ' links' : 'is 1 link') . ' on ' . ($iprojects != 1 ? $iprojects . ' projects' : '1 project') . '.';
    } else {
        $nolinks = 'There are no pages linking to this domain.';
    }

    echo '<p style="font-weight:bold;">' . $nolinks . '</p>';
    echo '<script type="text/javascript">noLinks("' . $nolinks . '");</script>';
    $executiontime =  time() - $_SERVER['REQUEST_TIME'];
    echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';

    // Begin database crap
} else {
?>
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0"><tbody>
<tr>
<tr>
    <td style = "text-align: right;">
        Bot report:
    </td>
    <td>
        <input type="text" name="report">
    </td>
</tr>
<tr>
    <td style = "text-align: right;">
        Number of edits to show per page:
    </td>
    <td>
        <input type="text" name="limit" value="10">
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
