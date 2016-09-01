<?php
/*
Categorycode based on catgraph:
(c) 2007 Peter Schloemer
Released under the GNU Public License (GPL), version 2
*/

$title = 'catanalyzer';
$pagetitle = 'catanalyzer';
$modified = '4 September 2007';

// Include files
require_once 'inc/webStart.inc.php';

// Start page
require_once 'inc/header.inc.php';
// Page content
?>
<p>
<b>catanalyzer</b> shows a category tree and can count the articles in those categories.
</p>
<?php
function dot_savefile($format, $input, $output)
{
    exec('dot -T ' . $format . ' ' . $input . ' -o ' . $output);
}

function dot_passthru ($format, $filename)
{
    passthru ('dot -T ' . $format . ' ' . $filename);
}

function removechars ($text)
{
	return preg_replace('/[^A-Za-z0-9 _]/', '_', $text);
}

function catlink ($cat)
{
	global $domain;
	global $ns_name;
	return 'http://' . $domain . '/wiki/' . $ns_name . ':' . str_replace(' ','_',$cat);
}

function write_node($file, $c) {
	global $count;
	global $cat_count;
	fwrite ($file, str_replace(' ','_', removechars($c)) . ' [URL="' . addcslashes(catlink($c),'"\\') . '", label = "' . $c . ($count ? '\nArticles: ' . $cat_count[$c] : '') . '"];' . "\n");
}

function write_edge($file, $sc, $c, $sub = False) {
	global $cat_count;
	if (!$sub) {
		fwrite ($file, '"' . str_replace(' ','_',removechars($sc)) . '" -> "' . str_replace(' ','_',removechars($c)) . "\";\n");
	}
	else
	{
		fwrite ($file, '"' . str_replace(' ','_',removechars($c)) . '" -> "' . str_replace(' ','_',removechars($sc)) . "\";\n");
	}
}


// Get and check parameters
if (!empty($_SERVER['QUERY_STRING']))
{
    // Get variables
    $lang = mysql_real_escape_string($_GET['lang']);
    $family = mysql_real_escape_string($_GET['family']);
    $cat = mysql_real_escape_string($_GET['cat']);

    $cat = str_replace("_"," ",$cat);
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
    	
	// Check category
	if (!$cat) {
	    trigger_error("Please enter a category.", E_USER_ERROR);
	} 

	$d = $_GET['d'];
	if ($d) {
		$d = intval($d);
		if ($d<0) $d=0;
	}
	else
	{
		$d = 0;
	}

	$n = $_GET['n'];
	if ($n) {
		$n = intval($n);
		if ($n>100) $n=100;
		if ($n<1) $n=1;
	}
	else
	{
		$n = 100;
	}

	switch ($_GET['format'])
	{
		case 'gif':
			$mime = 'image/gif';
			$format = 'gif';
			break;
		case 'svg':
			$mime = 'image/svg+xml';
			$format = 'svg';
			break;
		default:
			$mime = 'image/png';
			$format = 'png';
	}

	$sub = $_GET['sub'];
	if ($sub)
	{
		$sub = True;
	}
	else
	{
		$sub = False;
	}

	$count = $_GET['count'];
	if ($count)
	{
		$count = True;
	}
	else
	{
		$count = False;
	}
	
	$cluster = $db->getCluster($domain);
	
	if ($db->status[$cluster][0] == 'ERRO' || $db->status[$cluster][0] == 'DOWN') {
	    trigger_error('Sorry, this database is not available at the moment.', E_USER_ERROR);
	} else {
    	$db_name = $db->getDatabase($domain);
    	$ns_name = $db->getNamespace(14, $db_name);	
	
    	// $newcats: Indexed array containing the newly found categories.
    	$newcats = array();
    	// $cat_*: Hashed arrays containing each categories' supercategories, depth and number of articles
    	$cat_super = array();
    	$cat_depth = array();
    	$cat_count = array();

    	// Initialization
    	$newcats[] = $cat;
    	$depth = 0;

    	// Repeat all this as long as new categories have been found and neither a depth limit (if applied) or a number-of-categories limit have been reached
    	while (count($newcats)>0 && (count($cat_depth)+count($newcats))<=$n && ($d===0 || $depth<=$d)) {
    	    // Create empty entries for the newly found categories from the last iteration
    	    foreach ($newcats as $c) {
    	        $cat_super[$c] = array();
    	        $cat_depth[$c] = $depth;
    	    }

    	    // Build "IN" list of new categories to be queried
    	    $sql_in = '\'' . str_replace(" ", "_", mysql_real_escape_string($newcats[0])) . '\'';
    	    for ($i=1; $i<count($newcats); $i++)
    		{
    	        $sql_in .= ',\'' . str_replace(" ", "_", mysql_real_escape_string($newcats[$i])) . '\'';
    	    }

    	    // Clear "new categories"
    	    $newcats = array();

    	    // Query supercategories of newly found cats
    	    if (!$sub)
    		{
    	        $sql = 'SELECT page_title,cl_to FROM ' . $db_name . '.page, ' . $db_name . '.categorylinks WHERE cl_from=page_id AND page_namespace=14 AND page_title IN (' . $sql_in . ')';
    	    }
    	    // ...or subcategories, if parameter was given. Yes, this messes up the naming of
    	    // many variables, but it's not the end of the world.
    	    else
    		{
    	        $sql = 'SELECT cl_to,page_title FROM ' . $db_name . '.page, ' . $db_name . '.categorylinks WHERE cl_from=page_id AND page_namespace=14 AND cl_to IN (' . $sql_in . ')';
    	    }

    	    $q = $db->performQuery($sql, $cluster);
    	    if (!$q)
    		{
    	        mysql_close;
    	        trigger_error('Database query failed.', E_USER_ERROR);
    	    }
    	    while ($row = mysql_fetch_row($q))
    		{
    	        // Get "normal" format rather than MediaWiki
    	        $page_title = str_replace("_"," ",$row[0]);
    	        $cl_to = str_replace("_"," ",$row[1]);
    	        // Add supercategory
    	        $cat_super[$page_title][] = $cl_to;
    	        // If the supercategory is not known yet, add to new categories
    	        if (!isset($cat_depth[$cl_to]))
    			{
    	            $newcats[] = $cl_to;
    	        }
    	    }
    	    mysql_free_result($q);

    	    // Increase depth
    	    $depth++;
    	}
    	
    	$depth--;
    	
    	$cattime =  time() - $_SERVER['REQUEST_TIME'];
    	echo '<p>Retrieved categories.</p>';
    	// Nice string as title

    	// If the last iteration found nothing, don't show depth
    	if ($count)
    	{
    		$allcategories .= '"' . implode('", "', array_keys($cat_depth)) . '"';
    		$query = "
    			SELECT COUNT(DISTINCT page_id) AS count FROM " . $db_name . ".categorylinks
    			JOIN " . $db_name . ".page ON cl_from = page_id
    			AND page_namespace = 0
    			AND cl_to IN (" . $allcategories . ")";
    		$q = $db->performQuery($query, $cluster);
    	    if (!$q)
    		{
    	        trigger_error('Database query failed.', E_USER_WARNING);
    		}
    		$result = mysql_fetch_array($q, MYSQL_ASSOC);
    		$totalcount = $result['count'];
    		$i = 0;
    		foreach(array_keys($cat_depth) as $c)
    		{
    			$i++;
    			if ($i > $n)
    			{
    				echo 'Stopped counting categories.';
    				break;
    			}
    			$query = "
    			SELECT COUNT(1) AS count FROM " . $db_name . ".categorylinks
    			JOIN " . $db_name . ".page ON cl_from = page_id
    			AND page_namespace = 0
    			AND cl_to = '" . mysql_real_escape_string(str_replace(' ', '_', $c)) . "'";
    			$q = $db->performQuery($query, $cluster);

    		    if (!$q)
    			{
    		        trigger_error('Database query failed.', E_USER_WARNING);
    		        //echo 'Query was: ' . $query;
    			}

    			$result = mysql_fetch_array($q, MYSQL_ASSOC);
    			$cat_count[$c] = $result['count'];
    		}
    	}

    	if (count($newcats)===0)
    	{
    	    $depth=-1;
    	    $title = $cat . '(' . $wiki_name . ', ';
    		if ($count) $title .= 'unique articles: ' . $totalcount . ', ';
    	}
    	else 
    	{
    	    $title = $cat . '(' . $wiki_name . ', ';
    		if ($count) $title .= 'unique articles: ' . $totalcount . ', ';
    		$title .= 'depth: ' . $depth . ', ';
    	}

    	if ($sub)
    	{
    	    $title .= 'sub)';
    	}
    	else {
    	    $title .= 'super)';
    	}

    	$filename = tempnam('tmp/', 'dotfile');
    	$file = fopen($filename,'w');

    	fwrite ($file, "digraph cluster_CategoryTree {\n");
    	fwrite ($file, "node [fontsize=12, shape=rect]\n");
    	fwrite ($file, "splines=true\n");
    	fwrite ($file, 'label="' . addcslashes($title,'"\\') . "\"\n");

    	if ($depth===0)
    	{
    	    fwrite ($file, '"' . removechars($c) . "\"\n");
    	}
    	else
    	{
    	
    		foreach (array_keys($cat_depth) as $c)
    		{
    			write_node($file, $c);
    		}
    		foreach ($cat_super as $c => $scats)
    		{
    			if ($depth===-1 || $cat_depth[$c] < $depth)
    			{
    				foreach ($scats as $sc)
    				{
    					//fwrite ($file, '"' . addcslashes($sc,'"\\') . '" -> "' . addcslashes($c,'"\\') . "\"\n");
    					write_edge ($file, $sc, $c, $sub);
    				}
    			}
    			/*
    			// Don't do this because all nodes are already in the file
    			if ($depth===-1 || $cat_depth[$c] <= $depth)
    			{
    				fwrite ($file, '"' . addcslashes($c,'"\\') . '" [URL="' . addcslashes(catlink($c),'"\\') . "\"]\n");
    			}
    			*/
    		}
    	}

    	fwrite ($file,  "}\n");

    	// Close file
    	fclose($file);
    	
    	#echo '<pre>' . file_get_contents($filename) . '</pre>';
    	$outputfile = 'tmp/'. $db_name . '_' . str_replace(' ','_',removechars($cat)) . '_' . ($sub ? 'sub.' : 'super.') . $format;
    	//echo $outputfile;
        dot_savefile($format, $filename, $outputfile);
    	chmod($outputfile, 0755);
    	echo '<p>The output has been saved to <b><a href = "./tmp/'. $db_name . '_' . str_replace(' ','_',removechars($cat)) . '_' . ($sub ? 'sub.' : 'super.') . $format . '">./tmp/'. $db_name . '_' . str_replace(' ','_',removechars($cat)) . '_' . ($sub ? 'sub.' : 'super.') . $format . '</a></b>. It will be deleted after 24 hours.</p>';
    	$totalcats = count($cat_depth) - 1;
    	echo '<p>Total number of ' . ($sub ? 'sub' : 'super') . 'categories: ' . $totalcats . '.' . ($count ? ' These contain ' . $totalcount . ' unique articles.' : '') . '</p>';
    	if (($format==='gif') || ($format==='png'))
    	{
    	    echo '<map id="map" name="map">';
    	    dot_passthru('cmap', $filename);
    	    echo '</map>';
    	    echo '<img src="./tmp/'. $db_name . '_' . str_replace(' ','_',removechars($cat)) . '_' . ($sub ? 'sub.' : 'super.') . $format . '" usemap="map">';
    	}

    	// Remove output file, close database
    	//unlink($filename);
    	@mysql_close();
    	$executiontime =  time() - $_SERVER['REQUEST_TIME'];
    	$counttime = $executiontime - $cattime;
    	echo '<p style="font-size:80%;">(Fetched categories in ' . $cattime . ' seconds. Counted articles in ' . $counttime . ' seconds. Total execution time: ' . $executiontime . ' seconds.)</p>';
    }
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
		Category (without namespace):
	</td>
	<td>
		<input type="text" name="cat">
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Maximum search depth (0 is unlimited)
	</td>
	<td>
		<input type="text" name="d" value="0" />
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Maximum number of categories returned (0 is unlimited, maximum is 100)
	</td>
	<td>
		<input type="text" name="n" value="0" />
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Count articles:
	</td>
	<td>
		<input type="checkbox" name="count" value="1" Checked>
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Return super- or subcategories:
	</td>
	<td>
		<select name="sub">
		<option selected="selected" value="0">supercategories</option>
		<option value="1">subcategories</option>
		</select>
	</td>
</tr>
<tr>
	<td style = "text-align: right;">
		Output:
	</td>
	<td>
		<select name="format">
		<option>gif</option>
		<option>png</option>
		<option selected="selected">svg</option>
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
