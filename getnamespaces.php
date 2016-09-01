<?php
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
error_reporting(0);
//require_once 'inc/init.inc.php';
if (!empty($_SERVER['QUERY_STRING'])) {
	// Get variables
//	$lang = mysql_real_escape_string($_GET['lang']);
//    $family = mysql_real_escape_string($_GET['family']);
	$lang = $_GET['lang'];
    $family = $_GET['family'];

    if ($family == 'commons' || $family == 'meta') {
        echo '<namespaces family="' . $family .'">' . "\n";
    } else {
        echo '<namespaces lang="' . $lang . '" family="' . $family .'">' . "\n";
    }
    
    $org = '' ;
	if ($family == 'commons' || $family == 'meta') {
        $org = $family . '.wikimedia.org';
    } else {
        $org = $lang . '.' . $family . '.org';
    }

	$namespaces = array() ;
    $fh = popen ( "grep '$org' namespaces.tab" , 'r' ) ;
    while ( !feof($fh) ) {
    	$s = fgets ( $fh ) ;
    	$s = explode ( "\t" , $s ) ; // dbname , org, id , local_name
    	if ( $s[1] != $org ) continue ;
    	$namespaces[$s[2]] = trim ( $s[3] ) ;
    }
    pclose ( $fh ) ;
    
    ksort ( $namespaces ) ;
    foreach ( $namespaces AS $k => $v ) {
	    echo '<namespace ns_id="' . $k . '" ns_name="' . $v . '"></namespace>' . "\n";
	}
    
/*
    $sql = 'SELECT ns_id, ns_name FROM toolserver.namespace';

	if ($family == 'commons' || $family == 'meta') {
        $sql .= ' WHERE domain = \'' . $family . '.wikimedia.org\'';
    } else {
        $sql .= ' WHERE domain = \'' . $lang . '.' . $family . '.org\'';
    }
    $sql .= ' ORDER BY ns_id ASC';
    $q = $db->performQuery($sql, $server = 'any');
    if (!$q) {
        echo '<namespace ns_id="-1" ns_name="error"></namespace>' . "\n";
    } else {
        if (mysql_num_rows($q) == 0 ) {
           echo '<namespace ns_id="-1" ns_name="error"></namespace>' . "\n";
        } else { 
            while ($row = mysql_fetch_assoc($q)) {
                if ($row['ns_id']>0) {
                   echo '<namespace ns_id="' . $row['ns_id'] . '" ns_name="' . $row['ns_name'] . '"></namespace>' . "\n";
                }
            }
        }
    }
*/
    echo '</namespaces>';
} else {
    echo '<namespaces lang="0" family="0">' . "\n";
    echo '<namespace ns_id="-1" ns_name="error"></namespace>' . "\n";
    echo '</namespaces>';
}
?>
