<?php
include_once('/home/erwin85/libs/php/errorhandler.inc.php');
$mysqlconf = parse_ini_file('../.my.cnf');
$link = mysql_connect($mysqlconf['host'], $mysqlconf['user'], $mysqlconf['password']);

$s1 = 0;
$s2 = 0;
$s3 = 0;

if (!$link) {
        trigger_error('Could not retrieve replag. Failure to connect to mysql: ' . mysql_error() . '.', E_USER_NOTICE);
}else{
        $query = mysql_query("SELECT time_to_sec(timediff(now()+0,rev_timestamp)) FROM dewiki_p.revision ORDER BY rev_timestamp DESC LIMIT 1", $link);
        $result = mysql_fetch_array($query, MYSQL_NUM);
        $s2 = $result[0];
        /*
        $query = mysql_query("SELECT up_timestamp FROM dewiki_p.updates ORDER BY up_timestamp DESC LIMIT 1");
        $result = mysql_fetch_array($query, MYSQL_NUM);
        $s2ts = $result[0];
        */
}
mysql_close($link);

//Reconnect to server for enwiki_p.
$link = mysql_connect('sql-s1', $mysqlconf['user'], $mysqlconf['password']);
if (!$link) {
        trigger_error('Could not retrieve replag. Failure to connect to mysql: ' . mysql_error() . '.', E_USER_NOTICE);
}
else
{
        $query = mysql_query("SELECT time_to_sec(timediff(now()+0,rev_timestamp)) FROM enwiki_p.revision ORDER BY rev_timestamp DESC LIMIT 1", $link);
        $result = mysql_fetch_array($query, MYSQL_NUM);
        $s1 = $result[0];
        /*
        $query = mysql_query("SELECT up_timestamp FROM enwiki_p.updates ORDER BY up_timestamp DESC LIMIT 1");
        $result = mysql_fetch_array($query, MYSQL_NUM);
        $s1ts = $result[0];
        */
}

$link = mysql_connect('sql-s3', $mysqlconf['user'], $mysqlconf['password']);
if (!$link) {
        trigger_error('Could not retrieve replag. Failure to connect to mysql: ' . mysql_error() . '.', E_USER_NOTICE);
}
else
{
        $query = mysql_query("SELECT time_to_sec(timediff(now()+0,rev_timestamp)) FROM frwiki_p.revision ORDER BY rev_timestamp DESC LIMIT 1", $link);
        $result = mysql_fetch_array($query, MYSQL_NUM);
        $s3 = $result[0];
        /*
        $query = mysql_query("SELECT up_timestamp FROM frwiki_p.updates ORDER BY up_timestamp DESC LIMIT 1");
        $result = mysql_fetch_array($query, MYSQL_NUM);
        $s3ts = $result[0];
        */
}

function timediff($time)
{
        $days = ($time - ($time % 86400))/86400;
        $hours = (($time - $days*86400) - (($time - $days*86400) % 3600))/3600;
        $minutes = (($time - $days*86400 - $hours*3600) - (($time - $days*86400 - $hours*3600) % 60))/60;
        $seconds = $time - $days*86400 - $hours*3600 - $minutes*60;
        return $days . 'd ' . $hours . 'h ' . $minutes . 'm ' . $seconds . 's (' . $time .'s)';
}
?>
