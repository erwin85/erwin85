<?php
$title = 'talk page category intersect - erwin85';
$pagetitle = 'talk page category intersect';
$modified = '29 February 2008';

// Include files
require_once 'inc/webStart.inc.php';

// Start page
require_once 'inc/header.inc.php';
// Page content?>
<p>
<b>Talk page category intersect</b> shows articles in one category with its talk page in another category.
</p>
<?php
if (!empty($_SERVER['QUERY_STRING']))
{
    $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $pcategory = mysql_real_escape_string($_GET['pcategory']);
    $tpcategory = mysql_real_escape_string($_GET['tpcategory']);
    
    $pcategory = str_replace(' ', '_', $pcategory);
    $tpcategory = str_replace(' ', '_', $tpcategory);
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

    if ($domain == 'en.wikipedia.org') {
        trigger_error('Sorry, this tool has been disabled for the English Wikipedia.', E_USER_ERROR);
    }
    
    if (!$pcategory || !$tpcategory) {
        trigger_error("Please provide the categories.", E_USER_ERROR);
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
          
    $d = $_GET['d'];
    if ($d) {
        $d = intval($d);
        if ($d>10) $d = 10;
        if ($d<0) $d=1;
    } else {
        $d = 10;
    }
   
	//Link to local page with variables
	$link = $_SERVER['PHP_SELF'] . '?lang=' . $lang . '&family=' . $family . '&pcategory=' . $pcategory . '&tpcategory=' . $tpcategory;
    
    $cluster = $db->getCluster($domain);
    
    if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
        trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
    } else {
        $db_name = $db->getDatabase($domain);
    }
    
    $renewtree = false;
    $pcategory = ucfirst(str_replace(' ', '_', $pcategory));
    $namespacecond = ($namespace != -1 ? 'page_namespace ' . ($invert ? '!= ' : '= ' ) . intval($namespace) : '');
    
    $sql = 'SHOW TABLES FROM s51362__erwin85 LIKE "sc_' . $db_name . '"';
    $q = $db->performQuery($sql, $cluster);

    if (mysql_num_rows($q) != 1) {
        $renewtree = true;
    } else {
        $sql = 'SELECT sc_timestamp as sc_timestamp FROM s51362__erwin85.sc_' . $db_name . ' WHERE sc_category = \'' . $pcategory . '\'
            AND sc_supercategory = \'' . $pcategory . '\'';
        $q = $db->performQuery($sql, $cluster);
        $result = mysql_fetch_array($q, MYSQL_ASSOC);
        if ($result['sc_timestamp'] && !$purge) {
            echo '<p>Retrieved category tree from cache. This tree was generated at ' . date("r", createDateObject($result['sc_timestamp'])) . ', <a href="' . $link . '&purge=1">purge</a>.</p>';
        } else {
            $renewtree = true;
        }
    }
    
    if ($renewtree || $purge) {
        $db->storeCategoryTree($pcategory, $d, $db_name, $cluster);
        echo '<p>Generated new category tree.</p>';
    }

	echo '&lt; Articles in <a href="http://' . $domain . '/w/index.php?title=Category:' . $pcategory . '" title="Category:' . $pcategory . '">Category:' . str_replace('_', ' ', $pcategory) . '</a> (including subcategories up to level ' . $d . ') with their talk page in <a href="http://' . $domain . '/w/index.php?title=Category:' . $tpcategory . '" title="Category:' . $tpcategory . '">Category:' . str_replace('_', ' ', $tpcategory) . '</a>';
	        

    echo '<div class="rcoptions">Show last <a href="' . $link . '&limit=50">50</a> | <a href="' . $link . '&limit=100">100</a> | <a href="' . $link . '&limit=250">250</a> | <a href="' . $link . '&limit=500">500</a> pages. </div>';
    
    $sql = 'SELECT p.page_namespace as p_ns, p.page_title, tp.page_namespace as tp_ns
           FROM ' . $db_name . '.page AS tp
           LEFT JOIN ' . $db_name . '.categorylinks as tp_cl
           ON tp.page_id = tp_cl.cl_from
           LEFT JOIN ' . $db_name . '.page AS p
           ON tp.page_title = p.page_title
           AND p.page_namespace = tp.page_namespace - 1
           LEFT JOIN ' . $db_name . '.categorylinks AS p_cl
           ON p_cl.cl_from = p.page_id
           LEFT JOIN s51362__erwin85.sc_' . $db_name . '
           ON p_cl.cl_to = sc_category
           WHERE sc_depth < ' . $d . '
           AND sc_supercategory = \'' . $pcategory . '\'
           AND tp_cl.cl_to = \'' . $tpcategory . '\'
           GROUP BY p.page_id
           LIMIT ' . $limit;

    $q = $db->performQuery($sql, $cluster);
    if (!$q) {
        mysql_close();
        trigger_error('Database query failed.', E_USER_ERROR);
    }
    echo '<ul>';
    while ($row = mysql_fetch_assoc($q))
	{
	    $p_namespace = $db->getNamespace($row['p_ns'], $db_name);
	    $p_namespace = ($p_namespace ? $p_namespace . ':' : '');
	    $tp_namespace = $db->getNamespace($row['tp_ns'], $db_name);
	    $tp_namespace = ($tp_namespace ? $tp_namespace . ':' : '');
    	$page_title = $namespace . str_replace('_', ' ', $row['page_title']);
        echo '<li> <a href="http://' . $domain . '/w/index.php?title=' . $p_namespace . $page_title . '" title="' . $p_namespace . $page_title . '">' . $p_namespace . $page_title . '</a> (<a href="http://' . $domain . '/w/index.php?title=' . $tp_namespace . $page_title . '" title="' . $tp_namespace . $page_title . '">Talk</a>)</li>';
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
        Article category
    </td>
    <td>
        <input type="text" name="pcategory">
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
        Talk page category
    </td>
    <td>
        <input type="text" name="tpcategory">
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
