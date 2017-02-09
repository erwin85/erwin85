<?php
$title = 'new pages';
$pagetitle = 'new pages';
$modified = '5 March 2007';

// Include files
require_once 'inc/webStart.inc.php';

$scripts =  '<script type="text/javascript" language="javascript" src="getnamespaces.js"></script>';

// Start page
require_once 'inc/header.inc.php';
// Page content

function formatRow($row, $lang)
{
	global $domain;
	global $db_name;
	global $db;
	global $uselang;
    $namespace =  $db->getNamespace($row['page_namespace'], $db_name);
	$time = formatDate($row['timestamp'], ($uselang ? $uselang : $lang), timeoffset($lang));
	if(!$namespace == '') $namespace .= ':';
	$urltitle = str_replace('%2F', '/', urlencode(str_replace(' ', '_', $namespace . $row['page_title'])));
	$comment = commentBlock($row['comment'], $namespace . $row['page_title']);
	switch(($uselang ? $uselang : $lang))
	{
		case 'nl':
	        return '<li>' . $time . ' <a href="//' . $domain . '/wiki/' . $urltitle . '" title="' . $namespace . $row['page_title'] . '">' . $namespace . str_replace('_', ' ', $row['page_title']) . '</a> (<a href="//' . $domain . '/w/index.php?title=' . $urltitle . '&amp;action=history" title="' . $namespace . $row['page_title'] . '">gesch</a>) ' . ($row['len'] ? '[' . $row['len'] . ' bytes] ' : '') . '<a href="//' . $domain . '/wiki/Gebruiker:' . $row['user_text'] . '" title="Gebruiker:' . $row['user_text'] . '">' . $row['user_text'] . '</a> (<a href="//' . $domain . '/wiki/Overleg_gebruiker:' . $row['user_text'] . '" title="Overleg_gebruiker:' . $row['user_text'] . '">Overleg</a> | <a href="//' . $domain . '/wiki/Special:Contributions/' . $row['user_text'] . '" title="Bijdragen ' . $row['user_text'] . '">Bijdragen</a>) ' . $comment . '</li>';
			break;
		default:
	        return '<li>' . $time . ' <a href="//' . $domain . '/wiki/' . $urltitle . '" title="' . $namespace . $row['page_title'] . '">' . $namespace . str_replace('_', ' ', $row['page_title']) . '</a> (<a href="//' . $domain . '/w/index.php?title=' . $urltitle . '&amp;action=history" title="' . $namespace . $row['page_title'] . '">hist</a>) ' . ($row['len'] ? '[' . $row['len'] . ' bytes] ' : '') . '<a href="//' . $domain . '/wiki/User:' . $row['user_text'] . '" title="User:' . $row['user_text'] . '">' . $row['user_text'] . '</a> (<a href="//' . $domain . '/wiki/User_talk:' . $row['user_text'] . '" title="User_talk:' . $row['user_text'] . '">Talk</a> | <a href="//' . $domain . '/wiki/Special:Contributions/' . $row['user_text'] . '" title="Contributions ' . $row['user_text'] . '">contribs</a>) ' . $comment . '</li>';
			break;
	}
}

// Page content
/* TO DO:
Use [[:en:Special:Newpages]] layout
Use [[MediaWiki:1movedto2]] and [[MediaWiki:1movedto2 redir]], pref. in user db, to determine if rev is a page move
*/
?>
<p>
<b>new pages</b> shows new pages within a given time frame.
</p>
<?php
// Get and check parameters
if (!empty($_SERVER['QUERY_STRING']))
{
	// Get variables
	$lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $uselang = mysql_real_escape_string($_GET['uselang']);
    $t1 = mysql_real_escape_string($_GET['t1']);
    $t2 = mysql_real_escape_string($_GET['t2']);
   	$namespace = mysql_real_escape_string($_GET['namespaces']);
   	$invert = mysql_real_escape_string($_GET['invert']);
   	$limit = mysql_real_escape_string($_GET['limit']);
   	$offset = mysql_real_escape_string($_GET['offset']);
    
    // Get domain name and check project
	if ($family == 'commons') {
		$domain = 'commons.wikimedia.org';
	} elseif ($family =='meta') {
		$domain = 'meta.wikimedia.org';
	} else {
	   	if (!$lang || !$family) {
    		trigger_error('Please select a project.', E_USER_ERROR);
    	}
	    $domain = $lang . '.' . $family . '.org';
	}

	if (!preg_match('/\d{14,14}/', $t1) || !preg_match('/\d{14,14}/', $t2)) {
	    trigger_error('Invalid time syntax.', E_USER_ERROR);
	}
	
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
    	
    	//What table to use?       
        $sql = "select rc_timestamp from " . $db_name . ".recentchanges where rc_timestamp < '" . $t1 . "' order by rc_timestamp asc limit 1";
        $q = $db->performQuery($sql, $cluster);
    	if (!$q)
    	{
    		trigger_error('Database query failed.', E_USER_ERROR);
    	}
        
        if (mysql_num_rows($q) == 1) {
            //Use recentchanges table
            if ($namespace != '-1') {
                if (!$namespace) {
                    $namespace = 0;
                }
                if ($invert != 1) {
	                $sqlcond = ' AND rc_namespace = ' . $namespace;
                } else {
                    $sqlcond = ' AND rc_namespace != ' . $namespace;
                }
            } else {
                $sqlcond = '';
            }
        	$sql = "SELECT rc_timestamp as timestamp, rc_user_text as user_text, rc_comment as comment, rc_title as page_title, rc_namespace as page_namespace, rc_new_len as len FROM " . $db_name . ".recentchanges WHERE rc_timestamp >= '" . $t1 . "' AND rc_timestamp <= '" . $t2 . "' AND rc_new = 1" . $sqlcond . "  ORDER BY rc_timestamp DESC LIMIT " . $offset . ", " . $limit;
        } else {
            // Use revision, slower, but all dates.
            if ($namespace != '-1') {
                if (!$namespace) {
                    $namespace = 0;
                }
                if ($invert != 1) {
                    $sqlcond = ' AND page_namespace = ' . $namespace;
                } else {
                    $sqlcond = ' AND page_namespace != ' . $namespace;
                }
            } else {
                $sqlcond = '';
            }
        	$sql = "SELECT rev_timestamp as timestamp, rev_user_text as user_text, rev_comment as comment, page_title, page_namespace, rev_len as len FROM " . $db_name . ".revision AS r1 LEFT JOIN " . $db_name . ".page ON r1.rev_page = page_id WHERE r1.rev_timestamp >= '" . $t1 . "' AND r1.rev_timestamp <= '" . $t2 . "' AND rev_comment NOT LIKE 'Titel van %' AND NOT EXISTS (SELECT rev_page FROM " . $db_name . ".revision AS r2 WHERE r2.rev_timestamp < r1.rev_timestamp AND r1.rev_page = r2.rev_page)" . $sqlcond . " ORDER BY rev_timestamp DESC LIMIT " . $offset . ", " . $limit;
        }
        $q = $db->performQuery($sql, $cluster);
    	if (!$q)
    	{
    	    //echo mysql_error($db->link[$cluster]);
    		trigger_error('Database query failed.', E_USER_ERROR);
    	}
    	
    	//echo '<table class="prettytable">';
    	//echo '<tr><th>Page</th><th>Page length</th><th>Time</th><th>User</th><th>Comment</th></tr>';
    	$link = $_SERVER['PHP_SELF'] . '?lang=' . $lang . '&family=' . $family . '&t1=' . $t1 . '&t2=' . $t2 . '&namespaces=' . $namespace . '&invert=' . $invert;
        if (!timeoffset($lang)) {
            echo '<p style="font-weight:bold">NOTE: All times are in <a href="//en.wikipedia.org/wiki/UTC" title="UTC at Wikipedia">UTC</a>.</p>';
        } 
    	$newoffset = $offset - $limit;
    	$oldoffset = $offset + $limit;
    	$newoldlinks = '(' . ($offset >= $limit ? '<a href="' . $link . '&offset=' . $newoffset . '&limit=' . $limit . '" title="' . $_SERVER['PHP_SELF'] . '">Previous ' . $limit . '</a>' : 'Previous ' . $limit) . ') (<a href="' . $link . '&offset=' . $oldoffset . '&limit=' . $limit . '" title="' . $_SERVER['PHP_SELF'] . '">Next ' . $limit . '</a>) ';

    		echo '<p>' . $newoldlinks . '(<a href="' . $link . '&limit=20" title="' . $_SERVER['PHP_SELF'] . '">20</a> | <a href="' . $link . '&limit=50" title="' . $_SERVER['PHP_SELF'] . '">50</a> | <a href="' . $link . '&limit=100" title="' . $_SERVER['PHP_SELF'] . '">100</a> | <a href="' . $link . '&limit=250" title="' . $_SERVER['PHP_SELF'] . '">250</a> | <a href="' . $link . '&limit=500" title="' . $_SERVER['PHP_SELF'] . '">500</a>).</p>';
    		
    	echo '<ul>';
    	while ($row = mysql_fetch_assoc($q))
    	{
    		echo formatRow($row, $lang);
    	}
    	//echo '</table>';
    	echo '</ul>';
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
		From (UTC)
	</td>
	<td>
		<input name="t1" type="text" value="YYYYMMDDhhmmss">
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		to (UTC)
	</td>
	<td>
		<input name="t2" type="text" value="YYYYMMDDhhmmss">
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
