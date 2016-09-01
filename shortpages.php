<?php
$title = 'short pages';
$pagetitle = 'short pages';
$modified = '13 October 2007';

// Include files
require_once 'inc/webStart.inc.php';

$scripts =  '<script type="text/javascript" language="javascript" src="getnamespaces.js"></script>';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>short pages</b> shows a list of pages with a length less than 50 bytes.
</p>
<?php
// Get and check parameters
if (!empty($_SERVER['QUERY_STRING']))
{
	$lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $category = mysql_real_escape_string($_GET['category']);
    $limit = mysql_escape_string($_GET['limit']);
    $offset = mysql_escape_string($_GET['offset']);
   	$namespace = mysql_escape_string($_GET['namespaces']);
   	$invert = mysql_escape_string($_GET['invert']);
   	$filter = mysql_escape_string($_GET['filter']);
    
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
	
	/*
	if ($domain == 'en.wikipedia.org') {
        trigger_error('Sorry, this tool has been disabled for the English Wikipedia.', E_USER_ERROR);
    }
    */
    
	//The number of contributions to return
	if ($limit)	{
		$limit = intval($limit);
		if ($limit>500) $limit=500;
		if ($limit<1) $limit=1;
	} else {
		$limit = 100;
	}
	
	//A limit like LIMIT 10, 100
	if ($offset) {
		$offset = intval($offset);
		if ($offset<1) $offset=0;
	} else {
		$offset = 0;
	}
	
	$cluster = $db->getCluster($domain);
	
	if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
	    trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
	} else {
    	$db_name = $db->getDatabase($domain);

    	if ($namespace != '-1') {
    	    if ($invert != 1) {
       			$sql = "SELECT page_namespace, page_title, page_len FROM " . $db_name . ".page WHERE page_len < 50 AND page_is_redirect = 0 AND page_namespace = " . $namespace;
       		} else {
       		    $sql = "SELECT page_namespace, page_title, page_len FROM " . $db_name . ".page WHERE page_len < 50 AND page_is_redirect = 0 AND page_namespace != " . $namespace;
       		}
    	} else {
    		$sql = "SELECT page_namespace, page_title, page_len FROM " . $db_name . ".page WHERE page_len < 50 AND page_is_redirect = 0";
    	}
    	if ($filter == 'notemplates') {
    	    $sql .= " AND (SELECT COUNT(1) FROM " . $db_name . ".templatelinks WHERE tl_from = page_id) = 0";
    	} elseif ($filter == 'su') {
    	    $sql .= " AND (SELECT COUNT(DISTINCT rev_user_text) FROM " . $db_name . ".revision WHERE rev_page = page_id) = 1";
    	}
    	$sql .= " ORDER BY page_len ASC LIMIT " . $offset . ", " . $limit;
        $q = $db->performQuery($sql, $cluster);
    	if (!$q)
    	{
    		trigger_error('Database query failed.', E_USER_ERROR);
    	}
    	
    	$link = $_SERVER['PHP_SELF'] . '?lang=' . $lang . '&family=' . $family . '&namespaces=' . $namespace . '&filter=' . $filter;

    	$newoffset = $offset - $limit;
    	$oldoffset = $offset + $limit;
    	$newoldlinks = '(' . ($offset >= $limit ? '<a href="' . $link . '&offset=' . $newoffset . '&limit=' . $limit . '" title="' . $_SERVER['PHP_SELF'] . '">Previous ' . $limit . '</a>' : 'Previous ' . $limit) . ') (<a href="' . $link . '&offset=' . $oldoffset . '&limit=' . $limit . '" title="' . $_SERVER['PHP_SELF'] . '">Next ' . $limit . '</a>) ';

    		echo '<p>' . $newoldlinks . '(<a href="' . $link . '&limit=20" title="' . $_SERVER['PHP_SELF'] . '">20</a> | <a href="' . $link . '&limit=50" title="' . $_SERVER['PHP_SELF'] . '">50</a> | <a href="' . $link . '&limit=100" title="' . $_SERVER['PHP_SELF'] . '">100</a> | <a href="' . $link . '&limit=250" title="' . $_SERVER['PHP_SELF'] . '">250</a> | <a href="' . $link . '&limit=500" title="' . $_SERVER['PHP_SELF'] . '">500</a>).</p>';

    	echo '<table class="prettytable">';
    	echo '<tr><th>Page</th><th>Page length</th></tr>';
    	while ($row = mysql_fetch_assoc($q))
    	{
    		$namespace =  $db->getNamespace($row['page_namespace'], $db_name);
    		if(!$namespace == '') $namespace .= ':';
    		echo '<tr><td><a href="http://' . $domain . '/wiki/' . str_replace('%2F', '/', urlencode(str_replace(' ', '_', $namespace . $row['page_title']))) . '" title="' . $namespace . $row['page_title'] . '">' . $namespace . $row['page_title'] . '</a></td><td>' .  $row['page_len'] . '</td></tr>';
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
	<td style = "text-align: right;">
		Namespace
	</td>
	<td>
		<!-- <a href="#" onclick="NamespaceLookup(event); return false;">Lookup namespaces</a><br /> -->
		<span id="namespacesstatus">Enter project information to select a namespace.</span>
		<select name="namespaces" style="visibility:hidden">
		</select>
		<input name="invert" value="1" type="checkbox"> <label for="nsinvert">Invert selection</label>
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Filter (optional):
	</td>
	<td>
		<select name="filter" >
			<option value = "none" selected>None</option>
			<option value = "su">Pages with a single contributor</option>
			<option value = "notemplates">Pages that don't transclude templates</option>
		</select>
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
