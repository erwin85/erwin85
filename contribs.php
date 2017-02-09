<?php
$title = 'contribs';
$pagetitle = 'contribs';
$modified = '16 October 2009';

// Include files
require_once 'inc/webStart.inc.php';

// Start page
require_once 'inc/header.inc.php';
// Page content

function formatRow($row)
{
        global $domain;
        global $db_name;
        global $db;
        $namespace =  $db->getNamespace($row['page_namespace'], $db_name);
        if(!$namespace == '') $namespace .= ':';
        $comment = commentBlock($row['rev_comment'], $namespace . $row['page_title']);
        return "\n" . '<li' . ($color ? ' style="background-color: ' . $color . ';"' : '') . '>' . formatDate($row['rev_timestamp'], 'enspecial') . ' <i>by <a href = "http://' . $domain . '/wiki/User:' . $row['rev_user_text'] . '">' . ($row['newuser'] == True ? '<span style="font-weight:bold;">' . $row['rev_user_text'] . '</span>' : $row['rev_user_text']) . '</a></i> (<a href="http://' . $domain . '/w/index.php?title=' . $namespace . $row['page_title'] . '&amp;action=history" title="' . $namespace . $row['page_title'] . '">hist</a>) (<a href="http://' . $domain . '/w/index.php?title=' . $namespace . $row['page_title'] . '&amp;diff=prev&amp;oldid=' . $row['rev_id'] . '" title="' . $namespace . $row['page_title'] . '">diff</a>)' . ($row['rev_minor_edit'] == '1' ? ' <span class="minor">m</span>' : '') . ' <a href="http://' . $domain . '/wiki/' . $namespace . $row['page_title'] . '" title="' . $namespace . $row['page_title'] . '">' . str_replace('_', ' ', $namespace . $row['page_title']) . '</a> ' . $comment . ($row['page_latest'] == $row['rev_id'] ? ' <strong> (top)</strong>' : '') . '</li>';
}
?>
<div style = "float: right; margin: 5px 0px 5px 5px; clear:right; width: 200px;">
<h3>Legend</h3>
Edits made in short periods are color coded:
<ul style="padding: 5px;"><li style="background-color:red; width: 150px;">Within 10s</li>
<li style="background-color:orange; width: 150px;">Within 30s</li>
<li style="background-color:gold; width: 150px;">Within 60s</li></ul>
A user name is boldfaced if it's different from the previous one.
</div>

<p><b>contribs</b> shows the contributions for multiple users. You can also use <a href="/magnustools/herding_sheep.php">Herding sheep</a> for a simpler and faster list.</p>
<?php
// Get and check parameters
if (!empty($_SERVER['QUERY_STRING']))
{
        // Get variables
        $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $uselang = mysql_real_escape_string($_GET['uselang']);
    $users = mysql_real_escape_string($_GET['users']);

    $ausers = explode('|', $users);

    // Get domain name and check project
        if ($family == 'commons') {
                $domain = 'commons.wikimedia.org';
        } elseif ($family =='meta') {
                $domain = 'meta.wikimedia.org';
        } else {
                if (!$lang || !$family) {
                trigger_error("Please select a project.", E_USER_ERROR);
        }
            $domain = $lang . '.' . $family . '.org';
        }

        $order = mysql_real_escape_string($_GET['order']);
        $order = ($order != 'ASC' ? 'DESC' : 'ASC');

        //The number of contributions to return
        $limit = mysql_real_escape_string($_GET['limit']);

        if ($limit)     {
                $limit = intval($limit);
                if ($limit>1000) $limit=1000;
                if ($limit<1) $limit=1;
        } else {
                $limit = 100;
        }

        if (!$users) {
                trigger_error("Please provide a list of users.", E_USER_ERROR);
        }

    $cluster = $db->getCluster($domain);

        if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
            trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
        } else {
        $db_name = $db->getDatabase($domain);
    }

        if (count($ausers) > 100) {
                trigger_error('Too many users at once! Are you sure this is appropriate?', E_USER_ERROR);
        }
        if (count($ausers) == 1) {
                trigger_error('For a single user you\'re better off just using [[Special:Contributions]].', E_USER_ERROR);
        } else {
                $sql = '';

                foreach($ausers as $user) {
                    $user = str_replace('_', ' ', $user);
                        $sql .= '(SELECT * FROM ' . $db_name . '.revision LEFT JOIN ' . $db_name . '.page ON rev_page = page_id WHERE rev_user_text = \'' . $user . '\' ORDER BY rev_timestamp ' . $order . ' LIMIT ' . $limit . ') UNION ALL ';
                }

                $sql = substr($sql, 0, -10) . 'ORDER BY rev_timestamp ' . $order . ' LIMIT ' . $limit;
        }

    $q = $db->performQuery($sql, $cluster);
        if (!$q) {
                trigger_error('Database query failed.', E_USER_ERROR);
        }

    echo '<div id="contentSub">For ';

        foreach ($ausers as $user) {
                $foutput .= '<b><a href="http://' . $domain . '/wiki/User:' . $user . '" title="">' . $user . '</a></b> (<a href="http://' . $domain . '/wiki/User_talk:' . $user . '" title="User talk:' . $user . '">Talk</a> | <a href="http://' . $domain . '/w/index.php?title=Special:Log&amp;type=block&amp;page=User:' . str_replace(' ', '_', $user) . '" title="http://' . $domain . 'Special:Log">Block log</a> | <a href="http://' . $domain . '/w/index.php?title=Special:Log&amp;user=' . str_replace(' ', '_', $user) . '" title="Special:Log">Logs</a>), ';
        }
        $foutput = substr($foutput, 0, -2) . '</div>';

        echo $foutput;


        $userlink = $_SERVER['PHP_SELF'] . '?users=' . $users . '&lang=' . $lang . '&family=' . $family . ($uselang ? '&uselang=' . $uselang : '');

        echo '<p>(<a href="' . $userlink . '&limit=' . $limit . '" title="' . $_SERVER['PHP_SELF'] . '">Newest</a> | <a href="' . $userlink . '&limit=' . $limit . '&order=ASC" title="' . $userlink . '">Oldest</a>) (<a href="' . $userlink . '&limit=20" title="' . $_SERVER['PHP_SELF'] . '">20</a> | <a href="' . $userlink . '&limit=50" title="' . $_SERVER['PHP_SELF'] . '">50</a> | <a href="' . $userlink . '&limit=100" title="' . $_SERVER['PHP_SELF'] . '">100</a> | <a href="' . $userlink . '&limit=250" title="' . $_SERVER['PHP_SELF'] . '">250</a> | <a href="' . $userlink . '&limit=500" title="' . $_SERVER['PHP_SELF'] . '">500</a>).</p>';

        echo '<ul>';

        $rows = array(mysql_fetch_assoc($q)); // Get current row.
        $tgroups = array(   array('t' => 0, 'c' => 'white'),
                            array('t' => 10, 'c' => 'red'),
                            array('t' => 30, 'c' => 'orange'),
                            array('t' => 60, 'c' => 'gold'));
        $tmax = count($tgroups) - 1;
        while ($nrow = mysql_fetch_assoc($q)) // Loop over next rows and keep setting them to current.
        {
            $irow = count($rows)-1;
            unset($newGroup);
            unset($outLast);
            unset($nlim);
        $su = ($nrow['rev_user_text'] != $rows[$irow]['rev_user_text'] ? True : False); //Switched user?

        // User switched in current and next row.
        if ($su) {
            // Time difference between current and next row.
            $tdiff = abs(createDateObject($nrow['rev_timestamp']) - createDateObject($rows[$irow]['rev_timestamp']));

            // If difference in time is larger than the one for the biggest group
            // we just output the previous row.
            if ($tdiff > $tgroups[$tmax]['t']) {
                $outLast = True;
                $newGroup = True;
            } else {
                for ($i = 0; $i <= $tmax; $i++) {
                    // Difference is smaller than one of the groups.
                    if ($tdiff < $tgroups[$i]['t']) {
                        $nlim = $i;
                        break;
                    }
                }

                // Edits belong to a group with smaller time diff.
                if (isset($tlim) && $nlim < $tlim) {
                    $outLast = False;
                    $newGroup = True;
                // Edits belong to existing series.
                } elseif (isset($tlim) && $nlim == $tlim) {
                    $newGroup = False;
                // Edits belong to new group, so print existing smaller group.
                } elseif (isset($tlim)) {
                    $outLast = True;
                    $newGroup = True;
                } else {
                    $outLast = False;
                    $newGroup = True;
                }
            }
        } else { // No user switch
            $outLast = True;
            $newGroup = True;
        }

        $nrows = ($outLast == True ? count($rows) : count($rows) - 1);
        if ($newGroup && $nrows >= 1) {
            echo (isset($tlim) ? '</ul>' . "\n" . '<ul style="background-color: ' . $tgroups[$tlim]['c'] . '";>' : '');
            for($j = 1; $j <= $nrows; $j++) {
                //FIXME: Switch users?
                echo formatRow(array_shift($rows));
            }
            echo (isset($tlim) ? '</ul>' . "\n" . '<ul>' : '');
        }

        if (isset($nlim)) {
            $tlim = $nlim;
        } else {
            unset($tlim);
        }

        // Add new row.
        $nrow['newuser'] = $su;
        $rows[] = $nrow;
        }

        echo '</ul>';

        $executiontime =  time() - $_SERVER['REQUEST_TIME'];
        echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
}
else
{
?>
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0"><tbody>
<tr>
        <td style = "text-align: right; width:300px;">
                Project:
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
                        <option value = "wikiversity">.wikiversity.org</option>
                </select>
        </td>
</tr>
<tr>
        <td style = "text-align: right;">
                Users (use | as seperator, e.g. "A|B|C"):
        </td>
        <td>
                <input type="text" name="users">
        </td>
</tr>
<tr>
        <td style = "text-align: right;">
                Number of contributions:
        </td>
        <td>
                <input type="text" name="limit" value="100">
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
