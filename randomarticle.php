<?php
$title = 'random article';
$pagetitle = 'random article';
$modified = '25 June 2014';

// Include files
require_once 'inc/webStart.inc.php';

$scripts =  '<script type="text/javascript" language="javascript" src="getnamespaces.js"></script>';

if (!empty($_SERVER['QUERY_STRING']))
{
    // Get variables
    $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $categories = mysql_real_escape_string($_GET['categories']);
    $namespace = mysql_real_escape_string($_GET['namespaces']);
    $invert = mysql_real_escape_string($_GET['invert']);

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
    if (!$categories) {
        trigger_error("Please provide a list of categories.", E_USER_ERROR);
    }

    $acategories = explode('|', $categories);

    $d = $_GET['d'];
    if ($d) {
        $d = intval($d);
        if ($d>10) $d = 10;
        if ($d<0) $d=0;
    } else {
        $d = 10;
    }

    $sub = $_GET['subcats'];
    if ($sub) {
        $sub = True;
    } else {
        $sub = False;
    }

    if ($_GET['action'] == 1) {
        $url = '//tools.wmflabs.org/erwin85/randomarticle.php?lang=' . $lang . '&family=' . $family . '&categories=' . $categories . '&namespaces=' . $namespace;
        if ($sub == True) {
            $url .= '&subcats=1&d=' .$d;
        }
        echo '<a href="'. $url . '">' . $url . '</a>';
        die();
    }

    $cluster = $db->getCluster($domain);

    if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
        trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
    } else {
        $db_name = $db->getDatabase($domain);
    }

    $allcats = array();
    $newcats = $acategories;

    $namespacecond = ($namespace != -1 ? ' AND page_namespace ' . ($invert ? '!= ' : '= ' ) . intval($namespace) : '');
    if ($sub && count($acategories) == 1)
    {
        $renewtree = false;
        $category = ucfirst(str_replace(' ', '_', $acategories[0]));
        $sql = 'SHOW TABLES FROM s51362__erwin85 LIKE "sc_' . $db_name . '"';
        $q = $db->performQuery($sql, $cluster);

        if (mysql_num_rows($q) != 1) {
            $renewtree = true;
        } else {
            $sql = 'SELECT sc_timestamp FROM s51362__erwin85.sc_' . $db_name . ' WHERE sc_category = \'' . $category . '\'
                    AND sc_supercategory = \'' . $category . '\'';
            $q = $db->performQuery($sql, $cluster);
            $result = mysql_fetch_array($q, MYSQL_ASSOC);
            if (!$result['sc_timestamp']) {
                $renewtree = true;
            }
        }

        if ($renewtree) {
            $db->storeCategoryTree($category, $d, $db_name, $cluster);
        }
        $sqlsuffix = 'FROM ' . $db_name . '.page LEFT JOIN ' . $db_name . '.categorylinks ON page_id = cl_from
                LEFT JOIN s51362__erwin85.sc_' . $db_name . ' ON cl_to = sc_category
                WHERE page_is_redirect = 0 ' . $namespacecond . '
                AND sc_supercategory = \'' . $category . '\'
                AND sc_depth < ' . $d;
    } else {
        $sqlsuffix = 'FROM ' . $db_name . '.page LEFT JOIN ' . $db_name . '.categorylinks ON page_id = cl_from
                WHERE page_is_redirect = 0' . $namespacecond . '
                AND NOT EXISTS (SELECT 1 FROM ' . $db_name . '.templatelinks
                                WHERE tl_from = page_id
                                AND tl_namespace = 10
                                AND tl_title IN ("Dp", "Dpintro", "DP", "Disambig"))
                AND cl_to IN (';

        foreach($acategories as $category)
        {
            $sqlsuffix .= '"' . str_replace(' ', '_', $category) . '", ';
        }

        $sqlsuffix = substr($sqlsuffix, 0, -2) . ')';
    }

    $sql = 'SELECT COUNT(1) AS count ' . $sqlsuffix;
    $q = $db->performQuery($sql, $cluster);
    if (!$q) {
        /*
        if (preg_match('/sshunet\.nl/', gethostbyaddr($_SERVER['REMOTE_ADDR']))) {
            echo '<pre>' . $sql . '</pre>';
            echo mysql_error($db->link[$cluster]);
        }
        */
        mysql_close;
        trigger_error('Database query failed.', E_USER_ERROR);
    }
    $result = mysql_fetch_array($q, MYSQL_ASSOC);
    $random = rand(0, $result['count']);
    $sql = 'SELECT page_namespace, page_title ' . $sqlsuffix . ' LIMIT ' . $random . ',1';
    $q = $db->performQuery($sql, $cluster);
    if (!$q) {
        mysql_close;
        trigger_error('Database query failed.', E_USER_ERROR);
    }
    $result = mysql_fetch_array($q, MYSQL_ASSOC);

    if (!empty($result['page_title'])) {
        header('Location: //' . $domain . '/w/index.php?title=' . $db->getNamespace($result['page_namespace'], $db_name) . ':' . urlencode($result['page_title']));
    } else {
        echo '<p>Could not get an article matching these conditions.</p>';
    }
} else {
// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>random article</b> redirects to a random article in a category. Note: if this tool doesn't work you can try <a href="randomarticle.simple.php">random article - simple</a>.
</p>
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
        Categories (use | as seperator, e.g. "A|B|C"):
    </td>
    <td>
        <input type="text" name="categories">
    </td>
</tr>
<tr>
    <td style = "text-align: right;">
        Include subcategories (only for a single category):
    </td>
    <td>
        <input type="checkbox" name="subcats" value="1" Checked>
    </td>
</tr>
<tr>
    <td style = "text-align: right;">
        Maximum search depth (&lt; 10)
    </td>
    <td>
        <input type="text" name="d" value="0" />
    </td>
</tr>
<tr>
    <td style = "text-align: right;">
        Action:
    </td>
    <td>
        <select name="action">
        <option selected="selected" value="0">Redirect to random page</option>
        <option value="1">Show link to random page</option>
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
