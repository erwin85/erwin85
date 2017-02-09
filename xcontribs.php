<?php
$title = 'xContribs';
$pagetitle = 'xContribs';
$modified = '25 January 2010';

// Include files
require_once 'inc/webStart.inc.php';

$css = '<link href="xcontribs.css" rel="stylesheet" type="text/css" />';

// Start page
require_once 'inc/header.inc.php';

// Page content
?>
<p>
<b>xContribs</b> gives an overview of a user's <i>xWikiness</i>, i.e., the spread of their contributions over the various projects.
</p>
<?php
// Get data

if (!empty($_SERVER['QUERY_STRING']))
{
    $user = ucfirst(mysql_real_escape_string(trim($_GET['user'])));
    $user = str_replace('_', ' ', $user);

    if ( $_GET['logs'] ) {
        $including_log = ' (including logs)';
    } else {
        $including_log = '';
    }

    $sql = 'SELECT dbname, lang, family, url, slice FROM meta_p.wiki where is_closed = 0;';
    $q = $db->performQuery($sql);
    if (!$q)
    {
        trigger_error('Database query failed.', E_USER_ERROR);
    }

    $itotalwikis = mysql_num_rows($q);

    $projects = array();

    while ($row = mysql_fetch_assoc($q)) {
        $projects[$row['dbname']] = $row;
    }

    foreach($projects as $dbname => $project) {
        $cluster = $project['slice'];
        $domain = $project['url'];

        $sql = 'SELECT user_editcount, user_id
                FROM ' . $dbname . '_p.user
                WHERE user_name = \'' . $user . '\';';

        $q = $db->performQuery($sql, $cluster);
        if (!$q)
        {
            continue;
        }

        if (mysql_num_rows($q) > 0) {
            $row = mysql_fetch_assoc($q);

            if ($row['user_editcount'] > 0) {
                if ( $_GET['logs'] ) {
                        $log_count = 0;
                        $sql = 'SELECT count(log_id) as log_count
                                FROM ' . $dbname . '_p.logging_userindex
                                WHERE log_user = \''. $row['user_id'] . '\';';
                        $q2 = $db->performQuery($sql);

                        if ($q2) {
                                if (mysql_num_rows($q2) > 0) {
                                        $logs = mysql_fetch_assoc($q2);
                                        $counts[] = $row['user_editcount'] + $logs['log_count'];
                                }
                        }

                } else {
                        $counts[] = $row['user_editcount'];
                }
            }
        }
    }

        $avg = array_sum($counts)/count($counts);
    if (count($counts) > 1) {
        // Statistics
        sort($counts);

        // Number of projects with at least X edits.
        $mcounts = array(5, 10, 25, 50, 100, 1000, 10000);
        $totals = array();
        foreach ($mcounts as $o) {
            $i = 0;
            foreach ($counts as $c) {
                if (($c) > $o) {$i++;}
            }

            if ($i > 0) {
                $totals[$o] = $i;
            }
        }

        #rsort($counts);
        #$sigma = 0.0;
        $Ts = 0.0;
        $i = 1;
        $N = count($counts);
        foreach ($counts as $c) {
                #$sigma += $i * $c;
                #$i += 1;
                $Ts += ( $c / $avg)  * log( $c / $avg );
        }
        # http://www.fao.org/docs/up/easypol/329/gini_index_040en.pdf
        # $gini = 1 + 1 / $N - 2 * $sigma / ( $N * array_sum($counts) );
        # Theil index is more realistic for our case
        $theil = $Ts / ( $N * log($N) ) ;
        ?>
<table class="stats">
<tbody>
<tr><th>Username</th><td colspan="2"><a href="//tools.wmflabs.org/quentinv57-tools/tools/sulinfo.php?username=<?=$user;?>" title="SULutil"><?=$user;?></a></td></tr>
<tr><th>Home wiki</th><td colspan="2"><?=max($counts);?> (<?=round(100 * max($counts) / array_sum($counts));?>%)</td></tr>
<tr><th>Average</th><td colspan="2"><?=round($avg, 2);?></td></tr>
<tr><th>Sum</th><td colspan="2"><?=array_sum($counts);?></td></tr>
<!--<tr><th><a href="https://en.wikipedia.org/wiki/Gini_coefficient">Gini index</a></th><td colspan="2"><?=round($gini, 2);?></td></tr>-->
<tr><th><a href="https://en.wikipedia.org/wiki/Theil_index">Inequality</a> (0 is best)</th><td colspan="2"><?=round($theil, 2);?></td></tr>
<tr><th rowspan="<?=count($total);?>" style="font-style:italic;">xWikiness</th><th>Edit count<?=$including_log;?></th><th>Projects</th></tr>
<tr class="xwikiness"><td class="editcount">&gt; 1</td><td class="nprojects"><?=count($counts);?></td></tr>
<?php
        foreach ($totals as $o => $t) {
            echo '<tr class="xwikiness"><td class="editcount">&gt; ' . $o . '</td><td class="nprojects">' . $t . '</td></tr>';
        }
?>
</tbody>
</table>
<?php
        // Plot edit counts
        rsort($counts);
        include_once 'xcontribs.graph.php';
        echo '<img src="' . $FileName . '" alt="Plot of edits per project" />';
    } else {
        echo 'User has no contributions or only on a single project.';
    }
} else {
?>
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0"><tbody>
<tr>
<tr>
    <td style = "text-align: right;">
        Username:
    </td>
    <td>
        <input type="text" name="user">
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
echo '<p style="font-size:80%;">Execution time: ' . (time() - $_SERVER['REQUEST_TIME']) . ' seconds.</p>';
require_once 'inc/footer.inc.php';
?>
