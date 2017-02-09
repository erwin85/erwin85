<?php
$title = 'related changes';
$pagetitle = 'related changes';
$modified = '28 November 2007';

// Include files
require_once 'inc/webStart.inc.php';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>related changes</b> shows the changes related to a category and its subcategories.
</p>
<?php
if (!empty($_SERVER['QUERY_STRING']))
{
    $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $category = mysql_real_escape_string($_GET['category']);
    $ignore = mysql_real_escape_string($_GET['ignore']);
    
    $category = str_replace(' ', '_', $category);
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
    if (!$category) {
        trigger_error("Please provide a category.", E_USER_ERROR);
    }

    $days = $_GET['days'];
    if ($days) {
        $days = intval($days);
        if ($days<0) {
            $days = 7;
        }
    } else {
        $days = 7;
    }

    $limit = $_GET['limit'];
    if ($limit) {
        $limit = intval($limit);
        if ($limit<0) {
            $limit = 100;
        }elseif ($limit>500) {
            $limit = 500;
        }
    } else {
        $limit = 100;
    }
    
    $purge = $_GET['purge'];
    if ($purge) {
        $purge = true;
    }
    else {
        $purge = false;
    }
      
    $hideminor = $_GET['hideminor'];
    if ($hideminor) {
        $hideminor = 'AND rc_minor = 0 ';
    } else {
        $hideminor = '';
    }

    $hidebots = $_GET['hidebots'];
    if ($hidebots) {
        $hidebots = 'AND rc_bot = 0 ';
    } else {
        $hidebots = '';
    }
    
    $hideanons = $_GET['hideanons'];
    if ($hideanons) {
        $hideanons = 'AND rc_user != 0 ';
    } else {
        $hideanons = '';
    }
    
    $hideliu = $_GET['hideliu'];
    if ($hideliu) {
        $hideliu = 'AND rc_user = 0 ';
    } else {
        $hideliu = '';
    }
    
    $hidepatrolled = $_GET['hidepatrolled'];
    if ($hidepatrolled) {
        $hidepatrolled = 'AND rc_patrolled = 0 ';
    } else {
        $hidepatrolled = '';
    }
    
    $hidewikidata = $_GET['hidewikidata'];
    if ($hidewikidata) {
        $hidewikidata = 'AND rc_type != 5 ';
    } else {
        $hidewikidata = '';
    }
    
    $d = $_GET['d'];
    if ($d) {
        $d = intval($d);
        if ($d>10) $d = 10;
        if ($d<0) $d=1;
    } else {
        $d = 10;
    }

	//Link to local page with variables
	$link = $_SERVER['PHP_SELF'] . '?lang=' . $lang . '&family=' . $family . '&category=' . $category . '&d=' . $d . '&ignore=' . $ignore;
    $hide = ($hideminor ? '&hideminor=1' : '') . ($hideanons ? '&hideanons=1' : '') . ($hidebots ? '&hidebots=1' : '') . ($hideliu ? '&hideliu=1' : '') . ($hidepatrolled ? '&hidepatrolled=1' : '') . ($hidewikidata ? '&hidewikidata=1' : '');
    
    $cluster = $db->getCluster($domain);
    
    if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
        trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
    } else {
        $db_name = $db->getDatabase($domain);
    }
    
    $renewtree = false;
    $category = ucfirst(str_replace(' ', '_', $category));
    $namespacecond = ($namespace != -1 ? 'page_namespace ' . ($invert ? '!= ' : '= ' ) . intval($namespace) : '');
    
    $sql = 'SHOW TABLES FROM s51362__erwin85 LIKE "sc_' . $db_name . '"';
    $q = $db->performQuery($sql, $cluster);

    if (mysql_num_rows($q) != 1) {
        $renewtree = true;
    } else {
        $sql = 'SELECT sc_timestamp as sc_timestamp FROM s51362__erwin85.sc_' . $db_name . ' WHERE sc_category = \'' . $category . '\'
            AND sc_supercategory = \'' . $category . '\'';
        $q = $db->performQuery($sql, $cluster);
        $result = mysql_fetch_array($q, MYSQL_ASSOC);
        if ($result['sc_timestamp'] && !$purge) {
            echo '<p>Retreived category tree from cache. This tree was generated at ' . date("r", createDateObject($result['sc_timestamp'])) . ', <a href="' . $link . $hide . '&purge=1" title="relatedchanges">purge</a>.</p>';
        } else {
            $renewtree = true;
        }
    }
    
    if ($renewtree || $purge) {
        $db->storeCategoryTree($category, $d, $db_name, $cluster);
        echo '<p>Generated new category tree.</p>';
    }

	echo '&lt; <a href="//' . $domain . '/w/index.php?title=Category:' . $category . '" title="Category:' . $category . '">Category:' . str_replace('_', ' ', $category) . '</a> (including subcategories up to level ' . $d . ')';
	        

    $cutoff =  date("YmdHis", time() - $days * 86400);
    
    if (!timeoffset($lang)) {
        echo '<p style="font-weight:bold">NOTE: All times are in <a href="//en.wikipedia.org/wiki/UTC" title="UTC at Wikipedia">UTC</a>.</p>';
    } 
    echo '<div class="rcoptions">Show last <a href="' . $link . $hide . '&limit=50" title="relatedchanges">50</a> | <a href="' . $link . $hide . '&limit=100" title="relatedchanges">100</a> | <a href="' . $link . $hide . '&limit=250" title="relatedchanges">250</a> | <a href="' . $link . $hide . '&limit=500" title="relatedchanges">500</a> changes in last <a href="' . $link . $hide . '&days=1" title="relatedchanges">1</a> | <a href="' . $link . $hide . '&days=3" title="relatedchanges">3</a> | <a href="' . $link . $hide . '&days=7" title="relatedchanges">7</a> | <a href="' . $link . $hide . '&days=14" title="relatedchanges">14</a> | <a href="' . $link . $hide . '&days=30" title="relatedchanges">30</a> days';

    echo '<br>';
    echo '<a href="' . $link . ($hideanons ? '&hideanons=1' : '') . ($hidebots ? '&hidebots=1' : '') . ($hideliu ? '&hideliu=1' : '') . ($hidepatrolled ? '&hidepatrolled=1' : '') . ($hidewikidata ? '&hidewikidata=1' : '') . '&hideminor=' . ($hideminor ? '0" title="relatedchanges">Show</a>' : '1" title="relatedchanges">Hide</a>' ) . ' minor edits | ';
    echo '<a href="' . $link . ($hideanons ? '&hideanons=1' : '') . ($hideminor ? '&hideminor=1' : '') . ($hideliu ? '&hideliu=1' : '') . ($hidepatrolled ? '&hidepatrolled=1' : '') . ($hidewikidata ? '&hidewikidata=1' : '') . '&hidebots=' . ($hidebots ? '0" title="relatedchanges">Show</a>' : '1" title="relatedchanges">Hide</a>' ) . ' bots | ';
    echo '<a href="' . $link . ($hideminor ? '&hideminor=1' : '') . ($hidebots ? '&hidebots=1' : '') . ($hideliu ? '&hideliu=1' : '') . ($hidepatrolled ? '&hidepatrolled=1' : '') . ($hidewikidata ? '&hidewikidata=1' : '') . '&hideanons=' . ($hideanons ? '0" title="relatedchanges">Show</a>' : '1" title="relatedchanges">Hide</a>' ) . ' anonymous users | ';
    echo '<a href="' . $link . ($hideanons ? '&hideanons=1' : '') . ($hidebots ? '&hidebots=1' : '') . ($hideminor ? '&hideminor=1' : '') . ($hidepatrolled ? '&hidepatrolled=1' : '') . ($hidewikidata ? '&hidewikidata=1' : '') . '&hideliu=' . ($hideliu ? '0" title="relatedchanges">Show</a>' : '1" title="relatedchanges">Hide</a>' ) . ' logged-in users | ';
    echo '<a href="' . $link . ($hideanons ? '&hideanons=1' : '') . ($hidebots ? '&hidebots=1' : '') . ($hideminor ? '&hideminor=1' : '') . ($hideliu ? '&hideliu=1' : '') . ($hidewikidata ? '&hidewikidata=1' : '') . '&hidepatrolled=' . ($hidepatrolled ? '0" title="relatedchanges">Show</a>' : '1" title="relatedchanges">Hide</a>' ) . ' patrolled edits | ';
    echo '<a href="' . $link . ($hideanons ? '&hideanons=1' : '') . ($hidebots ? '&hidebots=1' : '') . ($hideminor ? '&hideminor=1' : '') . ($hideliu ? '&hideliu=1' : '') . ($hidewikipatrolled ? '&hidepatrolled=1' : '') . '&hidewikidata=' . ($hidewikidata ? '0" title="relatedchanges">Show</a>' : '1" title="relatedchanges">Hide</a>' ) . ' wikidata edits';

    echo '</div><ul>';
    
    $sql = 'SELECT rc_id, rc_this_oldid, rc_title, rc_timestamp, rc_comment,
           rc_namespace, rc_minor, rc_new, rc_user_text, rc_bot, rc_patrolled,
           rc_type, rc_params
           FROM ' . $db_name . '.recentchanges
           LEFT JOIN ' . $db_name . '.categorylinks
           ON rc_cur_id = cl_from
           LEFT JOIN s51362__erwin85.sc_' . $db_name . '
           ON cl_to = sc_category
           WHERE sc_depth < ' . $d . '
           AND sc_supercategory = \'' . $category . '\'' .
           $hideminor . $hidebots . $hideanons . $hideliu . $hidepatrolled . $hidewikidata .
           ((isset($ignore) && !empty($ignore)) ? ' AND rc_user_text != \'' . $ignore . '\'' : '') .
           ' AND rc_timestamp > ' . $cutoff . ' GROUP BY rc_id ORDER BY rc_timestamp DESC LIMIT ' . $limit;

    $q = $db->performQuery($sql, $cluster);
    if (!$q) {
        mysql_close();
        trigger_error('Database query failed.', E_USER_ERROR);
    }
    
    while ($row = mysql_fetch_assoc($q))
	{
        $time = date('H:i', createDateObject($row['rc_timestamp']) + timeoffset($lang));
	    $namespace =  $db->getNamespace($row['rc_namespace'], $db_name);
    	if(!$namespace == '') $namespace .= ':';
    	$page_title = $namespace . str_replace('_', ' ', $row['rc_title']);
    	$comment = commentBlock($row['rc_comment'], $namespace . $row['rc_title']);
    	$date = date("d F Y", createDateObject($row['rc_timestamp']) + timeoffset($lang));
    	if ($date != $prevdate) {
    	    echo '</ul><h4 style="font-weight:bold">' . $date . '</h4><ul>';
    	}
    	$prevdate = $date;
	if ($row['rc_type'] == 5) {
	    $rowparams = unserialize($row['rc_params']);
            $wikidatatitle = htmlspecialchars($rowparams['wikibase-repo-change']['object_id']);
            echo '<li>(<a href="//www.wikidata.org/w/index.php?title=' . $wikidatatitle . '&amp;diff=prev&amp;oldid=' . $rowparams['wikibase-repo-change']['rev_id'] . (!$row['rc_patrolled'] ? '&amp;rcid='. $row['rc_id'] : '') . '" title="" tabindex="1">diff</a>) (<a href="//www.wikidata.org/w/index.php?title=' . $wikidatatitle . '&amp;action=history" title="">hist</a>) . . ' . '<span class="wikibase-edit">D</span> <a href="//' . $domain . '/wiki/' . htmlspecialchars($page_title) . '" title="' . $page_title . '">' . $page_title . '</a> (<a href="//www.wikidata.org/wiki/' . $wikidatatitle . '" title="' . $wikidatatitle . '">' . $wikidatatitle . '</a>)‎ ' . $time . '. . <a href="//www.wikidata.org/wiki/User:' . $row['rc_user_text'] . '" title="User:' . $row['rc_user_text'] . '">' . $row['rc_user_text'] . '</a> (<a href="//www.wikidata.org/wiki/User_talk:' . $row['rc_user_text'] . '" title="User talk:' . $row['rc_user_text'] . '">Talk</a> | <a href="//www.wikidata.org/wiki/Special:Contributions/' . $row['rc_user_text'] . '" title="Special:Contributions/' . $row['rc_user_text'] . '">contribs</a>) ' . $comment . '</li>';
	} else {
            echo '<li>(<a href="//' . $domain . '/w/index.php?title=' . htmlspecialchars($page_title) . '&amp;diff=prev&amp;oldid=' . $row['rc_this_oldid'] . (!$row['rc_patrolled'] ? '&amp;rcid='. $row['rc_id'] : '') . '" title="" tabindex="1">diff</a>) (<a href="//' . $domain . '/w/index.php?title=' . htmlspecialchars($page_title) . '&amp;action=history" title="">hist</a>) . . ' . ($row['rc_new'] ? '<span class="newpage">N</span>' : '') . ($row['rc_minor'] ? '<span class="minor">m</span>' : '') . ($row['rc_bot'] ? '<span class="bot">b</span>' : '') . (!$row['rc_patrolled'] ? '<span class="unpatrolled">!</span>' : '') . ' <a href="//' . $domain . '/wiki/' . htmlspecialchars($page_title) . '" title="' . $page_title . '">' . $page_title . '</a>‎ '. $time . '. . <a href="//' . $domain . '/wiki/User:' . $row['rc_user_text'] . '" title="User:' . $row['rc_user_text'] . '">' . $row['rc_user_text'] . '</a> (<a href="//' . $domain . '/wiki/User_talk:' . $row['rc_user_text'] . '" title="User talk:' . $row['rc_user_text'] . '">Talk</a> | <a href="//' . $domain . '/wiki/Special:Contributions/' . $row['rc_user_text'] . '" title="Special:Contributions/' . $row['rc_user_text'] . '">contribs</a>) ' . $comment . '</li>';
        }
	}
	echo '</ul>';
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
        Category
    </td>
    <td>
        <input type="text" name="category">
    </td>
</tr>
<tr>
    <td style = "text-align: right;">
        Maximum search depth (&lt; 10)
    </td>
    <td>
        <input type="text" name="d" value="10" />
    </td>
</tr>
<tr>
    <td style = "text-align: right;">
        User to ignore
    </td>
    <td>
        <input type="text" name="ignore" />
    </td>
</tr>
<tr>
	<td style = "text-align: right;">
		Hide minor edits:
	</td>
	<td>
		<input type="checkbox" name="hideminor" value="1">
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Hide anonymous users:
	</td>
	<td>
		<input type="checkbox" name="hideanon" value="1">
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		 Hide logged-in users:
	</td>
	<td>
		<input type="checkbox" name="hideliu" value="1">
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Hide bots:
	</td>
	<td>
		<input type="checkbox" name="hidebots" value="1">
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Hide patrolled edits:
	</td>
	<td>
		<input type="checkbox" name="hidepatrolled" value="1">
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Hide wikidata edits:
	</td>
	<td>
		<input type="checkbox" name="hidewikidata" value="1">
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
$executiontime =  time() - $_SERVER['REQUEST_TIME'];
echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
require_once 'inc/footer.inc.php';
?>
