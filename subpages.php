<?php
$title = 'subpages - erwin85';
$pagetitle = 'subpages';
$modified = '20 May 2008';

// Include files
require_once 'inc/webStart.inc.php';

$scripts =  '<script type="text/javascript" language="javascript" src="getnamespaces.js"></script>';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>Subpages</b> selects all subpages of a given page. You can use this to e.g. get a list of subpages of a book on wikibooks and add them to your raw watchlist.
</p>
<?php
if (!empty($_SERVER['QUERY_STRING'])) {
    $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $title = mysql_real_escape_string($_GET['title']);
    $namespace = mysql_real_escape_string($_GET['namespaces']);

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

        // Check category
        if (!$title) {
            trigger_error("Please enter a title.", E_USER_ERROR);
        }


        $cluster = $db->getCluster($domain);

        if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
            trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
        } else {
        $db_name = $db->getDatabase($domain);
        $ns_name = ($namespace != -1 ? $db->getNamespace(intval($namespace), $db_name) : '');
        if(!$ns_name == '') $ns_name .= ':';
            $title = ucfirst(str_replace(' ', '_', $title));
            $namespacecond = ($namespace != -1 ? 'page_namespace = ' . intval($namespace) : 'page_namespace = 0');

        $sql = 'SELECT page_namespace, page_title FROM ' . $db_name . '.page WHERE page_title LIKE \''. $title . '/%\''
                 . ($namespacecond ? ' AND ' . $namespacecond : '');

        #echo '<pre>' . $sql . '</pre>';
        $q = $db->performQuery($sql, $cluster);

            if (!$q) {
                    trigger_error('Database query failed.', E_USER_ERROR);
            }

        echo '<pre>';
        echo $ns_name . $title . "\n";
        while ($row = mysql_fetch_assoc($q)) {
                $namespace =  $db->getNamespace($row['page_namespace'], $db_name);
                if(!$namespace == '') $namespace .= ':';
            echo $namespace . $row['page_title'] . "\n";
        }
        echo '</pre>';
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
    </td>
</tr>
<tr>
        <td style = "text-align: right;">
                Title:
        </td>
        <td>
                <input type="text" name="title">
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
