<?php
$title = 'categorycount';
$pagetitle = 'categorycount';
$modified = '28 November 2007';

// Include files
require_once 'inc/webStart.inc.php';

$scripts =  '<script type="text/javascript" language="javascript" src="getnamespaces.js"></script>';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>Categorycount</b> counts the number of articles in a category.
</p>
<?php
if (!empty($_SERVER['QUERY_STRING'])) {

    // Get variables
        $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $category = mysql_real_escape_string($_GET['category']);
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

        // Check category
        if (!$category) {
            trigger_error("Please enter a category.", E_USER_ERROR);
        }

        $sub = $_GET['subcats'];
    if ($sub) {
        $sub = True;
    } else {
        $sub = False;
    }

    $d = $_GET['d'];
    if ($d) {
        $d = intval($d);
        if ($d>10) $d = 10;
        if ($d<0) $d=1;
    } else {
        $d = 10;
    }

    $purge = $_GET['purge'];
    if ($purge) {
        $purge = true;
    }
    else {
        $purge = false;
    }

        $cluster = $db->getCluster($domain);

        if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
            trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
        } else {
        $db_name = $db->getDatabase($domain);
        $ns_name = $db->getNamespace(14, $db_name);
            $renewtree = false;
            $category = ucfirst(str_replace(' ', '_', $category));
            $namespacecond = ($namespace != -1 ? 'page_namespace ' . ($invert ? '!= ' : '= ' ) . intval($namespace) : '');

            if ($sub) {
            $sql = 'SHOW TABLES FROM s51362__erwin85 LIKE "sc_' . $db_name . '"';
            $q = $db->performQuery($sql, $cluster);

            if (mysql_num_rows($q) != 1) {
                $renewtree = true;
            } else {
                $sql = 'SELECT sc_timestamp FROM s51362__erwin85.sc_' . $db_name . ' WHERE sc_category = \'' . $category . '\'
                        AND sc_supercategory = \'' . $category . '\'';
                $q = $db->performQuery($sql, $cluster);
                $result = mysql_fetch_array($q, MYSQL_ASSOC);
                if ($result['sc_timestamp'] && !$purge) {
                    echo '<p>Retrieved category tree from cache. This tree was generated at ' . date("r", createDateObject($result['sc_timestamp'])) . ', <a href="http://tools.wikimedia.de/~erwin85/categorycount.php?lang=' . $lang . '&family=' . $family . '&category=' . $category . '&sub=1&purge=1" title="categorycount">purge</a>.</p>';
                } else {
                    $renewtree = true;
                }
            }
            if ($renewtree || $purge) {
                $db->storeCategoryTree($category, $d, $db_name, $cluster);
                echo '<p>Generated new category tree.</p>';
            }

            // Build the main query to find category members
            $sql =
            'SELECT COUNT(DISTINCT page_id) AS count
             FROM ' . $db_name . '.page
             LEFT JOIN ' . $db_name . '.categorylinks
             ON page_id = cl_from
             LEFT JOIN s51362__erwin85.sc_' . $db_name . '
             ON cl_to = sc_category
             WHERE sc_depth < ' . $d . '
             AND sc_supercategory = \'' . $category . '\'' .
             ($namespacecond ? 'AND ' . $namespacecond : '');
        } else {
            $sql =
            'SELECT COUNT(DISTINCT page_id) AS count
             FROM ' . $db_name . '.page
             LEFT JOIN ' . $db_name . '.categorylinks
             ON page_id = cl_from
             WHERE cl_to = \'' . $category . '\''
             . ($namespacecond ? 'AND ' . $namespacecond : '');
        }
        #echo '<pre>' . $sql . '</pre>';
        $q = $db->performQuery($sql, $cluster);
        #echo mysql_error($db->link[$cluster]);
        if ($q) {
            $result = mysql_fetch_assoc($q);
            $count = $result['count'];
        }

        echo '<p>There are ' . $count . ' articles in <a href="http://' . $domain . '/wiki/' . $ns_name . ':' . $category. '">' . $ns_name . ':' . $category. '</a> at ' . $domain . '.</p>';
        $executiontime =  time() - $_SERVER['REQUEST_TIME'];
        echo '<p style="font-size:80%;">Execution time: ' . $executiontime . ' seconds.</p>';
    }
} else {
?>
<form method="get">
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
                Category (without namespace):
        </td>
        <td>
                <input type="text" name="category">
        </td>
</tr>
<tr>
    <td style = "text-align: right;">
        Include subcategories:
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
require_once 'inc/footer.inc.php';
?>
