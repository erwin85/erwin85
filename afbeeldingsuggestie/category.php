<?php
$title = 'afbeeldingsuggesties';
$pagetitle = 'Afbeeldingsuggesties';
$modified = '2 januari 2009';

// Include files
date_default_timezone_set('UTC');
require_once '../inc/webStart.inc.php';

$domain = 'nl.wikipedia.org';
$cluster = $db->getCluster($domain);

if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
    trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
}

// Start page
require_once '../inc/header.inc.php';
// Page content
?>
<p>
<p>Met deze tool kun je de suggesties voor een bepaalde categorieboom bekijken.</p>
</p>
<?php
if (!empty($_SERVER['QUERY_STRING']))
{
    $category = mysql_real_escape_string($_GET['category']);  
    $category = ucfirst(str_replace(' ', '_', $category));
    
    if (!$category) {
        trigger_error('Geef a.u.b. een categorie op.', E_USER_ERROR);
    }

    $limit = $_GET['limit'];
    if ($limit) {
        $limit = intval($limit);
        if ($limit<0) {
            $limit = 100;
        } elseif ($limit>500) {
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
	$link = $_SERVER['PHP_SELF'] . '?category=' . $category;  
    
    if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
        trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
    } else {
        $db_name = $db->getDatabase($domain);
    }
    
    $renewtree = false;
    
    $sql = 'SHOW TABLES FROM u_erwin85 LIKE "sc_' . $db_name . '"';
    $q = $db->performQuery($sql, $cluster);

    if (mysql_num_rows($q) != 1) {
        $renewtree = true;
    } else {
        $sql = 'SELECT sc_timestamp as sc_timestamp FROM u_erwin85.sc_' . $db_name . ' WHERE sc_category = \'' . $category . '\'
            AND sc_supercategory = \'' . $category . '\'';
        $q = $db->performQuery($sql, $cluster);
        $result = mysql_fetch_array($q, MYSQL_ASSOC);
        if ($result['sc_timestamp'] && !$purge) {
            echo '<p>Er wordt een oude categorieboom gebruikt. Deze is gegenereerd op ' . date("r", createDateObject($result['sc_timestamp'])) . ', <a href="' . $link . '&purge=1">vernieuw</a>.</p>';
        } else {
            $renewtree = true;
        }
    }
    
    if ($renewtree || $purge) {
        $db->storeCategoryTree($category, $d, $db_name, $cluster);
        echo '<p>Er is een nieuwe categorieboom gegenereerd.</p>';
    }

	echo '&lt; Artikelen in <a href="http://' . $domain . '/w/index.php?title=Category:' . $category . '" title="Category:' . $category . '">Categorie:' . str_replace('_', ' ', $category) . '</a> (inclusief subcategorieën tot niveau ' . $d . '):';
	        

    echo '<div class="rcoptions">Toon <a href="' . $link . '&limit=50">50</a> | <a href="' . $link . '&limit=100">100</a> | <a href="' . $link . '&limit=250">250</a> | <a href="' . $link . '&limit=500">500</a> pagina\'s. </div>';
    
    $sql = 'SELECT p.page_namespace as p_ns, p.page_title, tp.page_namespace as tp_ns
           FROM ' . $db_name . '.page AS tp
           LEFT JOIN ' . $db_name . '.categorylinks as tp_cl
           ON tp.page_id = tp_cl.cl_from
           LEFT JOIN ' . $db_name . '.page AS p
           ON tp.page_title = p.page_title
           LEFT JOIN ' . $db_name . '.categorylinks AS p_cl
           ON p_cl.cl_from = p.page_id
           LEFT JOIN u_erwin85.sc_' . $db_name . '
           ON p_cl.cl_to = sc_category
           WHERE sc_depth < ' . $d . '
           AND sc_supercategory = \'' . $category . '\'
           AND tp_cl.cl_to = \'Wikipedia:Suggestie_voor_afbeelding\'
           AND p.page_namespace = 0
           AND tp.page_namespace = 1
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
        echo '<li> <a href="http://' . $domain . '/wiki/' . $p_namespace . $page_title . '" title="' . $p_namespace . $page_title . '">' . $p_namespace . $page_title . '</a> (<a href="http://' . $domain . '/wiki/' . $tp_namespace . $page_title . '" title="' . $tp_namespace . $page_title . '">Overleg</a>)</li>';
	}
	echo '</ul>';
}
else
{
?>
<form method="get" action="<?=$_SERVER['PHP_SELF'];?>">
<table border="0"><tbody>
<tr>
    <td style = "text-align: right;">
        Categorie
    </td>
    <td>
        <input type="text" name="category">
    </td>
</tr>
<tr>
    <td style = "text-align: right;">
        Onderliggende niveau's (max. 10)
    </td>
    <td>
        <input type="text" name="d" value="10" />
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
require_once '../inc/footer.inc.php';
?>
