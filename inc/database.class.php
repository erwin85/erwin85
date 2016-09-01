<?php
class TSDatabase
{
    //Singleton implementation
    /*
    IRC 3 Dec 2009
    s1: enwiki
    s2: various medium-sized wikis
    s3: all wikis not on another cluster
    s4: commons
    s5: dewiki
    s6: fr/ja/ruwiki
    
    sql-toolserver: toolserver
    sql: user databases
    
    Distinct servers:
    s1, s2+s5, s3+s4+s6, sql, sql-toolserver
    
    Commons is available on all 3 servers.
    */
    private static $instance;
    
    public $link = array();
    public $status = array();
    private $_randServer = 's3.labsdb';
    private $_servers = array();//'sql', 'sql-s1', 'sql-s2', 'sql-s3', 'sql-s4', 'sql-s5', 'sql-s6');
    private $_replag = array();
    private $_dbconn = array();
    private $_mysqlconf;

    public function __clone() {}
      
    private function __construct()
    {
        $this->_setAllStatus();
        $this->_mysqlconf = parse_ini_file('/data/project/erwin85/replica.my.cnf');
        $this->_randServer = $this->_getRandServer();
        $this->_connectHost($this->_randServer); // Need a connection for mysql_real_escape_string
        
    }
    
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c();
        }

        return self::$instance;
    }
    
    function __destruct()
    {
        foreach($this->_servers as $s) {
            if ($this->_dbconn[$s] === True) {
                mysql_close($this->link[$s]);
            }
        }
    }
    
    /****************************************************
    Public functions
    ****************************************************/
    function performQuery($sql, $server = 'any')
    {
	// Add time limit to query.
//	$sql = '/* LIMIT:60 NM*/ ' . $sql;// Not used on Labs

        // Query can be performed on any server.
        if ($server == 'any') {
//            $server = 'toolsdb'; // Try 2015-07-31
		$server = 'enwiki.labsdb' ;
//            $server = $this->_randServer; // Not used on Labs
        }

	// Connect to host if necessary.
        if (!isset($this->_dbconn[$server])) {
            $this->_connectHost($server);
        }
        
	// Run query.
        if ($this->_dbconn[$server] === True) {
            $link = $this->link[$server];
            $q = mysql_query($sql, $link);
//            print "$sql<hr/>" . mysql_error() ;
            return $q;
        } else {
            return False;
        }
        
    }
    
    //Backwards compatibility
    function performUserQuery($sql)
    {
        return $this->performQuery($sql, $server = 'sql');
        
    }
    
    function getReplag($server, $machine = False)
    {
        if (!array_key_exists($server, $this->_replag)) {
            $this->_setReplag();
        }
        
        if ($machine) {
            return $this->_replag[$server][0];
        } else {
            return $this->_replag[$server][1];
        }
    }
     
    function getAllReplag($machine = False)
    {
        $replag = array();
        foreach($this->_servers as $s) {
            if ($s != 'sql') {
                $replag[$s] = $this->getReplag($s);
            }
        }
        return $replag;
    }
            
    function getWarning()
    {
        $warning = '';
/*        
        foreach ($this->_servers as $s) {
            if ($this->status[$s][0] == 'ERRO' || $this->status[$s][0] == 'DOWN') {
                $class = 'erro';
            }
            if ($this->status[$s][0] != 'OK' ) {
                $warning .= '<li ' . ($class ? ' class="' . $class . '"' : '') . '>Cluster ' . $s . ': ' . $this->status[$s][0] . ' - ' . $this->status[$s][1] . '</li>';
            }
        }
        
        if (!empty($warning)) {
            $warning = '<h3>Database status:</h3><ul>' . $warning . '</ul>';
        }
*/     
        if (file_exists('/var/www/sitenotice')) {
            $notice = file_get_contents('/var/www/sitenotice');
        }
        
        $notice = (!empty($notice) ? '<h3>Notification:</h3>' . $notice : '');
        
        if (!empty($warning) || !empty($notice)) {
            return '<div class="warning">' . $warning . $notice . '</div>';
        } else {
            return '';
        }
    }

/*
function openDB ( $language , $project ) {
        global $mysql_user , $mysql_password , $o , $common_db_cache ;

        $db_key = "$language.$project" ;
        if ( isset ( $common_db_cache[$db_key] ) ) return $common_db_cache[$db_key] ;

        getDBpassword() ;
        $dbname = getDBname ( $language , $project ) ;

        $p = $project ;
        if ( $p == "wikipedia" ) $p = "wiki" ;

        $l = str_replace ( 'classic' , 'classical' , $language ) ;
        if ( $l == 'commons' ) $p = 'wiki' ;
        else if ( $l == 'wikidata' or $project == 'wikidata' ) $p = 'wiki' ;
        $server = "$l$p.labsdb" ;

        $db = new mysqli($server, $mysql_user, $mysql_password, $dbname);
        if($db->connect_errno > 0) {
                $o['msg'] = 'Unable to connect to database [' . $db->connect_error . ']';
                $o['status'] = 'ERROR' ;
                return false ;
        }
        $common_db_cache[$db_key] = $db ;
        return $db ;
}

function getDBname ( $language , $project ) {
        $ret = $language ;
        if ( $language == 'commons' ) $ret = 'commonswiki_p' ;
        else if ( $language == 'wikidata' || $project == 'wikidata' ) $ret = 'wikidatawiki_p' ;
        else if ( $project == 'wikipedia' ) $ret .= 'wiki_p' ;
        else if ( $project == 'wikisource' ) $ret .= 'wikisource_p' ;
        else if ( $project == 'wiktionary' ) $ret .= 'wiktionary_p' ;
        else if ( $project == 'wikibooks' ) $ret .= 'wikibooks_p' ;
        else if ( $project == 'wikinews' ) $ret .= 'wikinews_p' ;
        else if ( $project == 'wikiversity' ) $ret .= 'wikiversity_p' ;
        else if ( $project == 'wikivoyage' ) $ret .= 'wikivoyage_p' ;
        else die ( "Cannot construct database name for $language.$project - aborting." ) ;
        return $ret ;
}
*/

    function domain2dbname ( $domain ) {
	$ret = $domain ;
    	$ret = preg_replace ( '/^http:\/\//' , '' , $ret ) ;
    	$ret = preg_replace ( '/\.org$/' , '' , $ret ) ;
    	$ret = preg_replace ( '/wikipedia$/' , 'wiki' , $ret ) ;
    	$ret = preg_replace ( '/wikimedia$/' , 'wiki' , $ret ) ;
    	$ret = preg_replace ( '/\./' , '' , $ret ) ;
    	return $ret ;
    }
      
    function getCluster($domain)
    {
/*
    	$ret = $this->domain2dbname ( $domain ) ;
    	$ret .= '.labsdb' ;
        return $ret ;
*/
        $sql = "SELECT slice FROM meta_p.wiki WHERE url LIKE '%" . $domain . "'";
        $q = $this->performQuery($sql, $server = 'any');
        if ($q) {
            $result = mysql_fetch_assoc($q);
            return $result['slice'];
        }
    }

    function getClusterByDBName($dbname)
    {
        $db = $dbname;
        $db = preg_replace ( '/_p$/' , '' , $db ) ;
        $sql = "SELECT slice FROM meta_p.wiki WHERE dbname = '" . $dbname . "'";
        $q = $this->performQuery($sql, $server = 'any');
        if ($q) {
            $result = mysql_fetch_assoc($q);
            return $result['slice'];
        }
    }

    function getDababaseByFamilyAndLanguage($language, $family) {
    }
    
    function getDatabase($domain)
    {
/*
    	$ret = $this->domain2dbname ( $domain ) ;
    	$ret .= '_p' ;
    	return $ret ;
*/
        $sql = "SELECT dbname FROM meta_p.wiki WHERE url LIKE '%" . $domain . "'";
        $q = $this->performQuery($sql, $server = 'any');
        if ($q) {
            $result = mysql_fetch_assoc($q);
            return $result['dbname'].'_p';
        }
    }
    
    function getDomain($dbname)
    {
        $db = $dbname;
        $db = preg_replace ( '/_p$/' , '' , $db ) ;
        $sql = "SELECT url FROM meta_p.wiki WHERE dbname = '" . $dbname . "'";
        $q = $this->performQuery($sql, $server = 'any');
        if ($q) {
            $result = mysql_fetch_assoc($q);
            return preg_replace ('/^http:\/\/', '', $result['url']);
        }
    }
    
    
    function getNamespace($ns_id, $db_name)
    {
		$fh = popen ( "grep '$db_name' namespaces.tab | grep $ns_id" , 'r' ) ;
		while ( !feof($fh) ) {
			$s = fgets ( $fh ) ;
			$s = explode ( "\t" , $s ) ; // dbname , org, id , local_name
			if ( $s[0] != $db_name ) continue ;
			if ( $s[2] != $ns_id ) continue ;
			pclose ( $fh ) ;
			return trim($s[3]) ;
		}
		pclose ( $fh ) ;

/*
        $sql = "SELECT ns_name FROM toolserver.namespace WHERE dbname = '" . $db_name . "' AND ns_id = " . $ns_id;
        $q = $this->performQuery($sql, $server = 'any');
        if ($q) {
            $result = mysql_fetch_assoc($q);
            if ($result['ns_name'] == 'Article') {
                return '';
            } else {
                return $result['ns_name'];
            }
        }
*/
    }
    
    function getNamespaceID($ns_name, $db_name)
    {
		$fh = popen ( "grep '$db_name' namespaces.tab" , 'r' ) ;
		while ( !feof($fh) ) {
			$s = fgets ( $fh ) ;
			$s = explode ( "\t" , $s ) ; // dbname , org, id , local_name
			if ( $s[0] != $db_name ) continue ;
			if ( $s[3] != $ns_name ) continue ;
			pclose ( $fh ) ;
			return $s[2] ;
		}
		pclose ( $fh ) ;
/*
        $sql = "SELECT ns_id FROM toolserver.namespace WHERE dbname = '" . $db_name . "' AND ns_name = '" . $ns_name . "'";
        $q = $this->performQuery($sql, $server = 'any');
        if ($q) {
            $result = mysql_fetch_assoc($q);
            return $result['ns_id'];
        }
*/
    }
    
    function storeCategoryTree($category, $maxdepth, $db_name, $server)
    {
        $timestamp = date("YmdHis");
        $category = ucfirst($category);
        $category = str_replace(' ', '_', $category);

        $temptable = 'sc_' . $db_name . '_' . md5(uniqid());

        $sql = 'SHOW TABLES FROM s51362__erwin85 LIKE "sc_' . $db_name . '"';
        $q = $this->performQuery($sql, $server);

        if (mysql_num_rows($q) != 1) {
            //Create table
            $sql = 'CREATE TABLE s51362__erwin85.sc_' . $db_name . ' (
                   sc_id INT UNSIGNED NOT NULL auto_increment,
                   sc_page_id INT UNSIGNED NOT NULL,
                   sc_supercategory VARCHAR(255) binary NOT NULL,
                   sc_category VARCHAR(255) binary NOT NULL,
                   sc_depth INT UNSIGNED NOT NULL,
                   sc_timestamp BINARY(14) NOT NULL DEFAULT "19700101000000",
                   PRIMARY KEY sc_id (sc_id),
                   INDEX sc_supercategory (sc_supercategory),
                   INDEX sc_category (sc_category),
                   INDEX sc_supcatdepth (sc_supercategory, sc_category, sc_depth),
                   UNIQUE INDEX sc_supcattime (sc_supercategory, sc_category, sc_timestamp)
                   )';
            $q = $this->performQuery($sql, $server);
        } else {
            //Delete current tree for $category
            $sql = 'DELETE FROM s51362__erwin85.sc_' . $db_name . ' WHERE sc_supercategory = \'' . $category . '\'';
            $q = $this->performQuery($sql, $server);
        }    
        //Insert main category
        $sql = 'INSERT INTO s51362__erwin85.sc_' . $db_name . '
            SELECT \'\', page_id, page_title, page_title, 0, \'' . $timestamp . '\'
            FROM ' . $db_name . '.page
            WHERE page_title = "' . $category . '"
            AND page_namespace = 14';

        $q = $this->performQuery($sql, $server);
        //Keep adding categories as long as somethings inserted
        $inserted = mysql_affected_rows($this->link[$server]);
        //Create temporary table to get subcategories.
        $sql = 'CREATE TEMPORARY TABLE s51362__erwin85.' . $temptable . ' (
            sc_id INT UNSIGNED NOT NULL auto_increment,
            sc_page_id INT UNSIGNED NOT NULL,
            sc_supercategory VARCHAR(255) binary NOT NULL,
            sc_category VARCHAR(255) binary NOT NULL,
            sc_depth INT UNSIGNED NOT NULL,
            sc_timestamp BINARY(14) NOT NULL DEFAULT "19700101000000",
            PRIMARY KEY sc_id (sc_id)
            )';

        $q = $this->performQuery($sql, $server);

        //Get subcategories
        for ($level = 1 ; $inserted && $level <= $maxdepth; $level++) {
            $sql = 'INSERT INTO s51362__erwin85.' . $temptable . '
             SELECT \'\', page_id, \'' . $category . '\', page_title, ' . $level . ', \'' . $timestamp . '\'
             FROM ' . $db_name . '.page, ' . $db_name . '.categorylinks, s51362__erwin85.sc_' . $db_name . '
             WHERE page_id = cl_from
             AND cl_to = sc_category
             AND sc_depth = ' . ($level - 1) . '
             AND sc_supercategory = \'' . $category . '\'
             AND page_namespace = 14';
            echo mysql_error($this->link[$server]);
            $q = $this->performQuery($sql, $server);
            $inserted = mysql_affected_rows($this->link[$server]);

            if ($inserted) {
                $sql = 'INSERT IGNORE INTO s51362__erwin85.sc_' . $db_name . '
                    SELECT \'\', sc_page_id, sc_supercategory, sc_category, sc_depth, sc_timestamp FROM s51362__erwin85.' . $temptable;
                
                $q = $this->performQuery($sql, $server);
                $inserted = mysql_affected_rows($this->link[$server]);

                $sql = 'TRUNCATE TABLE s51362__erwin85.' . $temptable;
                #$q = $this->performQuery($sql, $server);
             }
        }
    }
    
    /****************************************************
    Private functions
    ****************************************************/
    private function _setStatus($text, $server)
    {
        // Don't use named groups, annoying php
        // $match = preg_match('/^(?P<status>[a-zA-Z]+?)\;(?P<msg>.*?)$/m', $txt, $m);
        $match = preg_match('/^([a-zA-Z]+?)\;(.*?)$/m', $text, $m);
        if ($match) {
            $this->status[$server] = array($m[1], $m[2]);
        } else {
            $this->status[$server] = array('UNKNOWN', '');
        }
        
        if ($this->status[$server][0] == 'ERRO' || $this->status[$server][0] == 'DOWN') {
            $this->_dbup[$server] = False;
        } else {
            $this->_dbup[$server] = True;
        }
    }
    
    private function _setAllStatus()
    {
        foreach ($this->_servers as $s) {
            if ($s != 'sql') {
                $f = '/var/www/status_' . substr($s, 4);
            } else {
                $f = '/var/www/status_sql';
            }
            
            if (file_exists($f)) {
                $status = $this->_determineStatus($f);
                $this->_setStatus($status, $s);
            } else {
                $this->_setStatus('Unknown', $s);
            }
        }
    }

    private function _determineStatus($file) {
        $content = file($file);
	// Find first line not starting with '#'
	foreach ($content as $line) {
	    if (substr($line, 0, 1) != '#') {
                return $line;
	    }
	}
	// Return blank.
	return "";
    }
    
    private function _getRandServer()
    {
    	return 'enwiki.labsdb' ;
/*    
        $servers = $this->_servers;
        while (count($servers) > 0) {
            $randKey = array_rand($servers);
            $s = $servers[$randKey];
            if ($this->_dbup[$s]) {
                return $s;
            } else {
                unset($server[$randKey]);
            }
        }*/
    }

    private function _connectHost($host)
    {
        $this->link[$host] = @mysql_connect($host, $this->_mysqlconf['user'], $this->_mysqlconf['password']);

        if ($this->link[$host]) {
            $this->_dbconn[$host] = True;
        } else {
            $this->_dbconn[$host] = False;
        }
            
    }
    private function _setReplag()
    {
	// No longer get replag.
	return;

        foreach ($this->_servers as $s) {
            if ($s != 'sql') {
                unset($r);
                switch($s) {
                    case 'sql-s1':
                        $dbname = 'enwiki_p';
                        break;
                    case 'sql-s2':
                        $dbname = 'nlwiki_p';
                        break;
                    case 'sql-s3':
                        $dbname = 'eswiki_p';
                        break;
                    case 'sql-s4':
                        $dbname = 'commonswiki_p';
                        break;
                    case 'sql-s5':
                        $dbname = 'dewiki_p';
                        break;
                    case 'sql-s6':
                        $dbname = 'frwiki_p';
                        break;
                }
                
                if (isset($dbname)) {
                    $sql = 'SELECT time_to_sec(timediff(now()+0,rev_timestamp)) FROM ' . $dbname . '.revision ORDER BY rev_timestamp DESC LIMIT 1';
                    $q = $this->performQuery($sql, $s);
                    if ($q) {
                        $result = mysql_fetch_array($q, MYSQL_NUM);
                        $r = array($result[0], $this->_timeDiff($result[0]));
                    }
                }
                
                $this->_replag[$s] = (isset($r) ? $r : array(-1, 'infinite'));
            }
        }
    }
    
    private function _timeDiff($time)
    {
        $days = ($time - ($time % 86400))/86400;
        $hours = (($time - $days*86400) - (($time - $days*86400) % 3600))/3600;
        $minutes = (($time - $days*86400 - $hours*3600) - (($time - $days*86400 - $hours*3600) % 60))/60;
        $seconds = $time - $days*86400 - $hours*3600 - $minutes*60;
        return $days . 'd ' . $hours . 'h ' . $minutes . 'm ' . $seconds . 's (' . $time .'s)';
    }
}
?>
