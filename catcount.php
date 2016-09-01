<?php
$title = 'catcount';
$pagetitle = 'catcount';
$modified = '6 November 2007';

// Include files
require_once 'inc/webStart.inc.php';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>Catcount</b> is a helper script for <a href="http://en.wikipedia.org/wiki/User:Erwin85/CatCount" title="User:Erwin85/CatCount">User:Erwin85/CatCount</a>. Simply enter the name of the category and the namespaces to include or exclude and this script will generate the code you need to use for Erwin85Bot.
</p>
<?php
$namespaces = array();
$sql = 'SELECT ns_id, ns_name FROM toolserver.namespace WHERE domain = \'en.wikipedia.org\' AND ns_id > 0 ORDER BY ns_id ASC';
$q = $db->performQuery($sql, 'sql');
if (!$q) {
    echo '<p>Failed to get namespaces.</p>';
} else {
    if (mysql_num_rows($q) == 0 ) {
        echo '<p>Failed to get namespaces.</p>';
    } else { 
        $namespaces[0] = 'Main';
        while ($row = mysql_fetch_assoc($q)) {
            $namespaces[$row['ns_id']] = $row['ns_name'];
        }
    }
}
    
if (!empty($_SERVER['QUERY_STRING'])) {
    
    // Get variables
    $category = mysql_real_escape_string($_GET['category']);
    
    $include = '';
    $include_ids = '';
    $exclude = '';
    $exclude_ids = '';
    $syntax = '<!-- count:' . str_replace(' ', '_', $category) . '; ns:';
    
    foreach($namespaces as $ns_id => $ns_name) {
        if (intval($_GET[$ns_id]) == 1) {
            $include_ids .= $ns_id . ',';
            $include .= $ns_name . ', ';
        } elseif (intval($_GET[$ns_id]) == -1) {
            $exclude_ids .= '-' . $ns_id . ',';
            $exclude .= $ns_name . ', ';
        }
    }
    if (!empty($include)) {
        $syntax .= substr($include_ids, 0, -1);
        echo '<p>Count all pages in ' . $category . ' in the ' . substr($include, 0, -2) . ' namespace(s).</p>';
    } elseif(!empty($exclude)) {
        $syntax .= substr($exclude_ids, 0, -1);
        echo '<p>Count all pages in ' . $category . ' not in the ' . substr($exclude, 0, -2) . ' namespace(s).</p>';
    }
    
    $syntax .= ' -->0<!-- end -->';
    echo '<p>The code to use is:</p><p><code>' . htmlspecialchars($syntax) . '</code></p>';
} else {
?>
<form method="get">
<table border="0"><tbody>
<tr>
	<td>
		Category (without namespace):
	</td>
	<td>
		<input type="text" name="category">
	</td>
</tr>
<tr>
    <td colspan = "2">
        <h3>Namespaces</h3>
        Select which namespaces you want to include or exclude. Note: including certain namespaces means excluding all namespaces you didn't select. To include all namespaces simply leave all checkboxes blank.
    </td>
</tr>
<?php
foreach($namespaces as $ns_id => $ns_name) {
?>
<tr>
	<td>&nbsp;</td>
	<td>
		<input type="checkbox" name="<?=$ns_id;?>" value="1"> (I) <input type="checkbox" name="<?=$ns_id;?>" value="-1"> (E) <?=$ns_name;?>
	</td>
</tr>
<?php
}
?>
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
