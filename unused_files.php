<?PHP

error_reporting(E_ERROR|E_CORE_ERROR|E_ALL|E_COMPILE_ERROR);
ini_set('display_errors', 'On');

include_once ( '/data/project/magnustools/public_html/php/common.php' ) ;

$thumbsize = 200 ;
function get_image_row_div ( $file , $w , $h , $num ) {
	global $thumbsize ;
	$tw = $thumbsize ;
	if ( $w < $h ) {
		$tw = round ( $thumbsize * $w / $h ) ;
	}
	$thumb_url = get_thumbnail_url ( 'commons' , $file , $tw , 'wikimedia' ) ;

	$h = "<div style='margin-bottom:1px;padding:2px;background-color:" . ($num?'white':'#DDD') . "'>" ;
	$h .= "<div style='display:inline-block;width:" . $thumbsize . "px;padding-left:" . round(($thumbsize-$tw)/2) . "px;vertical-align:top'>" ;
	$h .= "<a href='//commons.wikimedia.org/wiki/File:" . myurlencode($file) . "' target='_blank'>" ;
	$h .= "<img src='$thumb_url' border=0 /></a>" ;
	$h .= "</div>" ;
	$h .= "<div style='margin-left:5px;display:inline-block;vertical-align:top'>" ;
	$h .= "<b>$file</b>" ;
	$fn = preg_replace('/\.[^.]+$/','',$file) ;
	$h .= "<br/><a href='https://www.google.com/?#q=" . urlencode($fn) . "+site%3Awikipedia.org+-site%3Acommons.wikimedia.org' target='_blank'>Search Wikipedias for this title</a> to find potential articles to insert it." ;
	$fn = implode ( ' OR ' , explode ( ' ' , $fn ) ) ;
	$h .= " (<a href='https://www.google.com/?#q=" . urlencode($fn) . "+site%3Awikipedia.org+-site%3Acommons.wikimedia.org' target='_blank'>OR version</a>)" ;
	$h .= "</div>" ;
	$h .= "</div>" ;
	return $h ;
}

$category = get_request ( 'category' , '' ) ;
$depth = get_request ( 'depth' , 12 ) ;
$projects = trim ( get_request ( 'projects' , '' ) ) ;

print get_common_header ( '' , 'Unused images' ) ;

if ( $category == '' ) {
	print "<h2>Find files in a Commons category tree <i>not</i> used on Wikipedia (TEMPORARY tool to test a <a href='https://bitbucket.org/magnusmanske/magnustools/issue/20/allowed-memory-exhausted'>bug</a>)</h2>" ;
	print "<form method='get' action='?'>" ;
	print "<input type='text' name='category' placeholder='Commons category' />, depth <input type='number' name='depth' value='$depth' /> " ;
	print "<br/><i>Optional:</i> Not used in projects <input type='text' name='projects' placeholder='enwiki,dewikisource,...' /> " ;
	print "<br/><input type='submit' class='btn btn-primary' value='Do it!' />" ;
	print "</form>" ;
	print "<div>Example: <a href='?category=Belgrade+Aviation+Museum&depth=12'>Belgrade Aviation Museum</a></div>" ;
	print get_common_footer() ;
	exit ( 0 ) ;
}


$db = openDB ( 'commons' , 'wikimedia' ) ;

$all_files = getPagesInCategory ( $db , $category , $depth , 6 ) ;
foreach ( $all_files AS $k => $i ) $all_files[$k] = $db->real_escape_string ( $i ) ;

$total_num = count ( $all_files ) ;
$sql = "SELECT img_name, img_width, img_height FROM image WHERE img_name IN ('" . implode("','",$all_files) . "') AND NOT EXISTS (select * from globalimagelinks WHERE gil_page_namespace_id=0 AND gil_to=img_name " ;
if ( $projects != '' ) {
	$projects = explode ( ',' , $projects ) ;
	foreach ( $projects AS $k => $v ) $projects[$k] = $db->real_escape_string ( $v ) ;
	$sql .= " AND gil_wiki IN ('" . implode("','",$projects) . "')" ;
}
$sql .= " LIMIT 1) ORDER BY img_name" ; ;
unset ( $all_files ) ; // Save RAM
if(!$result = $db->query($sql)) die('There was an error running the query [' . $db->error . ']');
$unused_files = array() ;
while($o = $result->fetch_object()){
	$unused_files[str_replace ( '_' , ' ' , $o->img_name )] = $o ;
}


print "<h1>Unused images for \"$category\"</h1>" ;
print "<div>" . count($unused_files) . " unused files (out of $total_num).</div>" ;
//print "<pre>" ; print_r ( $unused_files ) ; print "</pre>" ;

$num = 0 ;
foreach ( $unused_files AS $file => $meta ) {
	print get_image_row_div ( $file , $meta->img_width , $meta->img_height , $num ) ;
	$num = ($num+1)%2 ;
}

print get_common_footer() ;

?>
