<?php
//Set page variables needed for errorhandler
$title = 'hidden edits';
$pagetitle = 'hidden edits';
$modified = '27 September 2010';
date_default_timezone_set('UTC');

// Include files
require_once 'inc/webStart.inc.php';

// Start page
require_once 'inc/header.inc.php';

function formatRow($row)
{
    global $domain;
    preg_match('/revision\s(?P<rev>\d*?)\sofield/', $row['log_params'], $m);
    if ($m['rev']) {
        $diff = '<a href="http://' . $domain . '/w/index.php?diff=' . $m['rev'] . '&unhide=1">diff</a>; ';
    }
    $title = ($row['log_namespace'] ? $row['ns_name'] . ':' . $row['log_title'] : $row['log_title']);
    $comment = commentBlock($row['log_comment'], $title);
    return '<li>' . formatDate($row['log_timestamp'], 'nl') . ' <a href="http://' . $domain . '/wiki/User:' . $row['user_name'] . '">' . $row['user_name'] . '</a> changed revision visibility of <a href="http://' . $domain . '/wiki/' . $title . '">' . $title . '</a>'. $comment . ' (' . $diff . '<a href="http://' . $domain . '/w/index.php?title=Special:Log&type=delete&user=' . rawurlencode($row['user_name']) . '&offset=' . ($row['log_timestamp'] + 1). '&limit=1">log</a>)</li>';
}
// Page content
?>
<p>
<b>hidden edits</b> filters the deletion log.
</p>
<?php
// Hardcoded for nlwiki.
$domain = 'nl.wikipedia.org';
$db_name = $db->getDatabase($domain);
$cluster = $db->getCluster($domain);
$sql = 'SELECT log_timestamp, log_deleted, user_name, log_namespace, ns_name, log_title, log_comment, log_params
        FROM ' . $db_name . '.logging
        JOIN ' . $db_name . '.user
        ON user_id = log_user
        LEFT JOIN toolserver.namespace
        ON ns_id = log_namespace
        AND dbname = \'' . $db_name . '\'
        WHERE log_type = \'delete\'
        AND log_action = \'revision\'
        ORDER BY log_timestamp DESC LIMIT 10;';

$q = $db->performQuery($sql, $cluster);
if (!$q)
{
    trigger_error('Database query failed.', E_USER_ERROR);
}

// Output
echo '<ul>';
while ($row = mysql_fetch_assoc($q))
{
    echo formatRow($row);
}
//27 sep 2010 19:55 Erwin (Overleg | bijdragen | blokkeren) zichtbaarheid van bewerkingen is gewijzigd voor Gebruiker:Erwin/Klad1: heeft inhoud verborgen voor 1 versie ‎ (Test) (wijz | Zichtbaarheid wijzigen)
echo '</ul>';
$executiontime =  time() - $_SERVER['REQUEST_TIME'];
echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
require_once 'inc/footer.inc.php';
?>
