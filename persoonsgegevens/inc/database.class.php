<?php
class TSDatabase
{
    //Singleton implementation
    private static $instance;
    
    public $link = array();
    public $status = array();
    private $_dbup = array();
    private $_replag = array();
    private $_dbconn = array();
    private $_mysqlconf;

    public function __clone() {}
      
    private function __construct($sql_s1 = true, $sql_s2 = true, $sql_s3 = true, $sql = false)
    {
        $this->_setAllStatus();
        $this->_mysqlconf = parse_ini_file('/home/erwin85/.my.cnf');
        
        if ($this->_dbup['sql-s1'] && $sql_s1) {
            $this->_connectHost('sql-s1');
        }
        
        if ($this->_dbup['sql-s2'] && $sql_s2) {
            $this->_connectHost('sql-s2');        }
        
        if ($this->_dbup['sql-s3'] && $sql_s3) {        
            $this->_connectHost('sql-s3');        }
        
        if ($this->_dbup['sql'] && $sql) {
            $this->_connectHost('sql');
        }
     }
    
    public static function singleton($sql_s1 = true, $sql_s2 = true, $sql_s3 = true, $sql = false)
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($sql_s1, $sql_s2, $sql_s3, $sql);
        }

        return self::$instance;
    }
    
    function __destruct()
    {
        if ($this->_dbup['sql-s1'] && $this->_dbconn['sql-s1']) {
            mysql_close($this->link['sql-s1']);
        }
        
        if ($this->_dbup['sql-s2'] && $this->_dbconn['sql-s2']) {
            mysql_close($this->link['sql-s2']);
        }
        
        if ($this->_dbup['sql-s3'] && $this->_dbconn['sql-s3']) {        
            mysql_close($this->link['sql-s3']);
        }
        
        if ($this->_dbup['sql'] && $this->_dbconn['sql']) {        
            mysql_close($this->link['sql']);
        }
    }
    
    /****************************************************
    Public functions
    ****************************************************/
    function performQuery($sql, $cluster)
    {
        if ($this->_dbup[$cluster]) {
            if (!$this->_dbconn[$cluster]) {
                $this->_connectHost($cluster);
            }
            
            $link = $this->link[$cluster];
            $q = mysql_query($sql, $link);
            return $q;
        } else {
            return false;
        }
        
    }
    
    //Backwards compatibility
    function performUserQuery($sql)
    {
        return $this->performQuery($sql, 'sql');
        
    }
    
    function getReplag($cluster)
    {
        if (array_key_exists($cluster, $this->_replag)) {
            return $this->_replag[$cluster][1];
        } else {
            $this->_setReplag();
            return $this->_replag[$cluster][1];
        }
    }
    
    function getWarning()
    {
        $warning = '';
        
        foreach (array('sql-s1', 'sql-s2', 'sql-s3', 'sql') as $cluster) {
            if ($this->status[$cluster][0] == 'ERRO' || $this->status[$cluster][0] == 'DOWN') {
                $class = 'erro';
            }
            if ($this->status[$cluster][0] != 'OK' ) {
                $warning .= '<li ' . ($class ? ' class="' . $class . '"' : '') . '>Cluster ' . $cluster . ': ' . $this->status[$cluster][0] . ' - ' . $this->status[$cluster][1] . '</li>';
            }
        }

        if (!empty($warning)) {
            $warning = '<div class="warning"><h3>Database status</h3><ul>' . $warning . '</ul></div>';
        }
        
        return $warning;
    }
    
    function getCluster($domain)
    {
        foreach (array('sql-s1', 'sql-s2', 'sql-s3') as $cluster) {
            if ($this->_dbup[$cluster]) {
                $sql = "SELECT server FROM toolserver.wiki WHERE domain = '" . $domain . "'";
                $q = $this->performQuery($sql, $cluster);
                if ($q) {
                    $result = mysql_fetch_assoc($q);
                    return 'sql-s' . $result['server'];
                }
            }
        }
    }
    
    function getDatabase($domain)
    {
        foreach (array('sql-s1', 'sql-s2', 'sql-s3') as $cluster) {
            if ($this->_dbup[$cluster]) {
                $sql = "SELECT dbname FROM toolserver.wiki WHERE domain = '" . $domain . "'";
                $q = $this->performQuery($sql, $cluster);
                if ($q) {
                    $result = mysql_fetch_assoc($q);
                    return $result['dbname'];
                }
            }
        }
    }
    
    function getNamespace($ns_id, $db_name)
    {
        foreach (array('sql-s1', 'sql-s2', 'sql-s3') as $cluster) {
            if ($this->_dbup[$cluster]) {
                $sql = "SELECT ns_name FROM toolserver.namespace WHERE dbname = '" . $db_name . "' AND ns_id = " . $ns_id;
                $q = $this->performQuery($sql, $cluster);
                if ($q) {
                    $result = mysql_fetch_assoc($q);
                    if ($result['ns_name'] == 'Article') {
                        return '';
                    } else {
                        return $result['ns_name'];
                    }
                }
            }
        }
    }
    
    function getNamespaceID($ns_name, $db_name)
    {
        foreach (array('sql-s1', 'sql-s2', 'sql-s3') as $cluster) {
            if ($this->_dbup[$cluster]) {
                $sql = "SELECT ns_id FROM toolserver.namespace WHERE dbname = '" . $db_name . "' AND ns_name = '" . $ns_name . "'";
                $q = $this->performQuery($sql, $cluster);
                if ($q) {
                    $result = mysql_fetch_assoc($q);
                    return $result['ns_id'];
                }
            }
        }
    }
    
    function storeCategoryTree($category, $maxdepth, $db_name, $cluster)
    {
        $timestamp = date("YmdHis");
        $category = ucfirst($category);
        $category = str_replace(' ', '_', $category);

        $temptable = 'sc_' . $db_name . '_' . md5(uniqid());

        $sql = 'SHOW TABLES FROM u_erwin85 LIKE "sc_' . $db_name . '"';
        $q = $this->performQuery($sql, $cluster);

        if (mysql_num_rows($q) != 1) {
            //Create table
            $sql = 'CREATE TABLE u_erwin85.sc_' . $db_name . ' (
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
                   ) TYPE=MyISAM';
            $q = $this->performQuery($sql, $cluster);
        } else {
            //Delete current tree for $category
            $sql = 'DELETE FROM u_erwin85.sc_' . $db_name . ' WHERE sc_supercategory = \'' . $category . '\'';
            $q = $this->performQuery($sql, $cluster);
        }    
        //Insert main category
        $sql = 'INSERT INTO u_erwin85.sc_' . $db_name . '
            SELECT \'\', page_id, page_title, page_title, 0, \'' . $timestamp . '\'
            FROM ' . $db_name . '.page
            WHERE page_title = "' . $category . '"
            AND page_namespace = 14';

        $q = $this->performQuery($sql, $cluster);
        //Keep adding categories as long as somethings inserted
        $inserted = mysql_affected_rows($this->link[$cluster]);
        //Create temporary table to get subcategories.
        $sql = 'CREATE TEMPORARY TABLE u_erwin85.' . $temptable . ' (
            sc_id INT UNSIGNED NOT NULL auto_increment,
            sc_page_id INT UNSIGNED NOT NULL,
            sc_supercategory VARCHAR(255) binary NOT NULL,
            sc_category VARCHAR(255) binary NOT NULL,
            sc_depth INT UNSIGNED NOT NULL,
            sc_timestamp BINARY(14) NOT NULL DEFAULT "19700101000000",
            PRIMARY KEY sc_id (sc_id)
            ) TYPE=MyISAM';

        $q = $this->performQuery($sql, $cluster);

        //Get subcategories
        for ($level = 1 ; $inserted && $level <= $maxdepth; $level++) {
            $sql = 'INSERT INTO u_erwin85.' . $temptable . '
             SELECT \'\', page_id, \'' . $category . '\', page_title, ' . $level . ', \'' . $timestamp . '\'
             FROM ' . $db_name . '.page, ' . $db_name . '.categorylinks, u_erwin85.sc_' . $db_name . '
             WHERE page_id = cl_from
             AND cl_to = sc_category
             AND sc_depth = ' . ($level - 1) . '
             AND sc_supercategory = \'' . $category . '\'
             AND page_namespace = 14';
            echo mysql_error($this->link[$cluster]);
            $q = $this->performQuery($sql, $cluster);
            $inserted = mysql_affected_rows($this->link[$cluster]);

            if ($inserted) {
                $sql = 'INSERT IGNORE INTO u_erwin85.sc_' . $db_name . '
                    SELECT \'\', sc_page_id, sc_supercategory, sc_category, sc_depth, sc_timestamp FROM u_erwin85.' . $temptable;
                
                $q = $this->performQuery($sql, $cluster);
                $inserted = mysql_affected_rows($this->link[$cluster]);

                $sql = 'TRUNCATE TABLE u_erwin85.' . $temptable;
                #$q = $this->performQuery($sql, $cluster);
             }
        }
    }
    
    /****************************************************
    Private functions
    ****************************************************/
    private function _setStatus($text, $cluster)
    {
        // Don't use named groups, annoying php
        // $match = preg_match('/^(?P<status>[a-zA-Z]+?)\;(?P<msg>.*?)$/m', $txt, $m);
        $match = preg_match('/^([a-zA-Z]+?)\;(.*?)$/m', $text, $m);
        if ($match) {
            $this->status[$cluster] = array($m[1], $m[2]);
        } else {
            $this->status[$cluster] = array('UNKNOWN', '');
        }
        
        if ($this->status[$cluster][0] == 'ERRO' || $this->status[$cluster][0] == 'DOWN') {
            $this->_dbup[$cluster] = false;
        } else {
            $this->_dbup[$cluster] = true;
        }
    }
 
    private function _setAllStatus()
    {
        $s1text = file_get_contents('/var/www/status_s1');
        $this->_setStatus($s1text, 'sql-s1');

        $s2text = file_get_contents('/var/www/status_s2');
        $this->_setStatus($s2text, 'sql-s2');

        $s3text = file_get_contents('/var/www/status_s3');
        $this->_setStatus($s3text, 'sql-s3');
        
        $sqltext = file_get_contents('/var/www/status_sql');
        $this->_setStatus($sqltext, 'sql');
    }
    
    private function _connectHost($host)
    {
        $this->link[$host] = @mysql_connect($host, $this->_mysqlconf['user'], $this->_mysqlconf['password']);
        if ($this->link[$host]) {
            $this->_dbconn[$host] = true;
        } else {
            $this->_dbup[$host] = false;
        }
            
    }
    private function _setReplag()
    {
        if ($this->_dbup['sql-s1']) {
            $sql = 'SELECT time_to_sec(timediff(now()+0,rev_timestamp)) FROM enwiki_p.revision ORDER BY rev_timestamp DESC LIMIT 1';
            $q = $this->performQuery($sql, 'sql-s1');
            if ($q) {
                $result = mysql_fetch_array($q, MYSQL_NUM);
                $this->_replag['sql-s1'] = array($result[0], $this->_timeDiff($result[0]));
            }
        } else {
            $this->_replag['sql-s1'] = array(-1, 'infinite');
        }
        
        if ($this->_dbup['sql-s2']) {
            $sql = 'SELECT time_to_sec(timediff(now()+0,rev_timestamp)) FROM dewiki_p.revision ORDER BY rev_timestamp DESC LIMIT 1';
            $q = $this->performQuery($sql, 'sql-s2');
            if ($q) {
                $result = mysql_fetch_array($q, MYSQL_NUM);
                $this->_replag['sql-s2'] = array($result[0], $this->_timeDiff($result[0]));
            }
        } else {
            $this->_replag['sql-s2'] = array(-1, 'infinite');
        }
        
        if ($this->_dbup['sql-s3']) {        
            $sql = 'SELECT time_to_sec(timediff(now()+0,rev_timestamp)) FROM frwiki_p.revision ORDER BY rev_timestamp DESC LIMIT 1';
            $q = $this->performQuery($sql, 'sql-s3');
            if ($q) {
                $result = mysql_fetch_array($q, MYSQL_NUM);
                $this->_replag['sql-s3'] = array($result[0], $this->_timeDiff($result[0]));
            }
        } else {
            $this->_replag['sql-s3'] = array(-1, 'infinite');
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
