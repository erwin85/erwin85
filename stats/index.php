<?php
require_once '/home/erwin85/public_html/inc/database.class.php';
$db = TSDatabase::singleton(true, true, true);

$ch = curl_init('http://meta.wikimedia.org/w/api.php?action=query&prop=categoryinfo&titles=Category:Open_XWiki_reports|Category:Closed_XWiki_reports|Category:Added_XWiki_reports|Category:Ignore_XWiki_reports|Category:Stale_XWiki_reports&format=json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
curl_setopt($ch, CURLOPT_USERAGENT, 'Wikipedia Bot - xwiki stats - http://toolserver.org/~erwin85/stats/');
$output = curl_exec($ch);
curl_close($ch);
$results = json_decode($output, true);
$reports = array();
foreach ($results['query']['pages'] as $page) {
    switch($page['title']) {
        case 'Category:Open XWiki reports':
            $reports['open'] = $page['categoryinfo']['pages'];
            break;
        case 'Category:Closed XWiki reports':
            $reports['closed'] = $page['categoryinfo']['pages'];
            break;
        case 'Category:Added XWiki reports':
            $reports['added'] = $page['categoryinfo']['pages'];
            break;
        case 'Category:Ignore XWiki reports':
            $reports['ignore'] = $page['categoryinfo']['pages'];
            break;
        case 'Category:Stale XWiki reports':
            $reports['stale'] = $page['categoryinfo']['pages'];
            break;
    }
}
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel='stylesheet' type='text/css' href='/startsite/main.css' />
<title>Statistics</title>
</head>
<body>
<h1>Statistics</h1>
<h2>Number of reports</h2>
<p><ul>
<li><a href="http://meta.wikimedia.org/wiki/Category:Open_XWiki_reports" title="Category:Open XWiki reports">Open</a>: <?=$reports['open'];?></li>
<li><a href="http://meta.wikimedia.org/wiki/Category:Closed_XWiki_reports" title="Category:Closed XWiki reports">Closed</a>: <?=$reports['closed'];?></li>
<li><a href="http://meta.wikimedia.org/wiki/Category:Added_XWiki_reports" title="Category:Added XWiki reports">Added</a>: <?=$reports['added'];?></li>
<li><a href="http://meta.wikimedia.org/wiki/Category:Ignore_XWiki_reports" title="Category:Ignore XWiki reports">Ignore</a>: <?=$reports['ignore'];?></li>
<li><a href="http://meta.wikimedia.org/wiki/Category:Stale_XWiki_reports" title="Category:Stale XWiki reports">Stale</a>: <?=$reports['stale'];?></li>
</ul></p>
<h3>Last hour</h3>
<img src="hourly.png" alt="Number of open reports in the last hour" />
<h3>Last day</h3>
<img src="daily.png" alt="Number of open reports in the last day" />
<h3>Last week</h3>
<img src="weekly.png" alt="Number of open reports in the last week" />
<h3>Last month</h3>
<img src="monthly.png" alt="Number of open reports in the last month" />
<h3>Last year</h3>
<img src="yearly.png" alt="Number of open reports in the last year" />
<h2>Users</h2>
<table>
<tr><th>User</th><th>Total</th><th>Last month</th><th>Last week</th><th>Last day</th></tr>
<?php
$sql = 'select rev_user_text as user,
        count(1) as total,
        sum(if(rev_timestamp > (SELECT date_sub(now(), interval 1 month)),1,0)) as month,
        sum(if(rev_timestamp > (SELECT date_sub(now(), interval 1 week)),1,0)) as week,
        sum(if(rev_timestamp > (SELECT date_sub(now(), interval 1 day)),1,0)) as day
        from metawiki_p.revision
        left join metawiki_p.page
        on page_id = rev_page
        where page_namespace = 2
        and page_title like \'COIBot/XWiki/%\'
        and not exists (select * from metawiki_p.user_groups
                        where ug_user = rev_user
                        and ug_group = \'bot\')
        group by rev_user_text
        order by month desc limit 10;';

$q = $db->performQuery($sql, 'sql-s3');

if (!$q) {
    trigger_error('Database query failed.', E_USER_NOTICE);
}

while ($row = mysql_fetch_assoc($q))
{
    echo '<tr><td>' . $row['user'] . '</td><td>' . $row['total'] . '</td><td>' . $row['month'] . '</td><td>' . $row['week'] . '</td><td>' . $row['day'] . '</td></tr>';
}

?>
</table>
<div class='footer'>
        <p><a href="http://toolserver.org"><img class='footer' src='/wikimedia-toolserver-button.png' alt='Wikimedia Toolserver' /></a></p>
</div>
</body>
</html>
