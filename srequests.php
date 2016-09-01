<?php
function getPage($pagename, $forcelive=False)
{
    if(!$forcelive) {
        $ch = curl_init('http://toolserver.org/~daniel/WikiSense/WikiProxy.php?wiki=meta.wikimedia.org&title=' . urlencode($pagename));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
        $output = curl_exec($ch);
        curl_close($ch);
    } else {
        $ch = curl_init('http://meta.wikimedia.org/w/index.php?title=' . urlencode($pagename) . '&action=raw&ctype=text');
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Wikipedia Bot - http://toolserver.org/~erwin85');
	    $output = curl_exec($ch);
	    curl_close($ch);
    }    
    return $output;
}

function isSteward($user) {
    global $cluster;
    global $db;
    $user = mysql_real_escape_string($user);
    $sql = 'SELECT 1
            FROM metawiki_p.user
            JOIN metawiki_p.user_groups
            ON ug_user = user_id
            WHERE user_name = \'' . $user . '\'
            AND ug_group = \'steward\' LIMIT 1;';
    $q = $db->performQuery($sql, $cluster);
    if ($q) {
        if (mysql_num_rows($q) == 1) {
            return True;
        }
    }

    return False;
}


function anchorencode($text) {
    $a = trim($text);
    $a = preg_replace('/\[\[(?:[^\]]*?)\|([^\]]*?)\]\]/', '${1}', $a);
    $a = preg_replace('/\[\[([^\]]*?)\]\]/', '${1}', $a);

    // Rest of function from CoreParserFunctions.php
    // phase3/includes/parser/CoreParserFunctions.php (r54220)
    $a = urlencode( $a );
    $a = strtr( $a, array( '%' => '.', '+' => '_' ) );
    # leave colons alone, however
    $a = str_replace( '.3A', ':', $a );
    return $a;
}
        
//Set page variables needed for errorhandler
$title = 'steward requests';
$pagetitle = 'steward requests';
$modified = '24 November 2009';
date_default_timezone_set('UTC');

// Include files
require_once 'inc/webStart.inc.php';

// Start page
require_once 'inc/header.inc.php';
// Page content
$cacheFile = './cache/srequests.php';
?>
<p>
<b>steward requests</b> is an overview of the steward request pages.
</p>
<?php
// Used cached version
if (file_exists($cacheFile) && !$_GET['action'] == 'purge') {
    echo '<span style="font-style:italic">Using cached results from ' .  strftime('%H:%M, %e %B %Y', filemtime($cacheFile)) . ' (UTC), <a href="' . $_SERVER['php_self'] . '?action=purge">purge</a></span>.';
    include_once $cacheFile;
} else {
    // Start output buffering to regenerate chache
    ob_start();
    
    $forcelive = mysql_real_escape_string($_GET['forcelive']);    
    $forcelive = ($forcelive ? True : False);

    $domain = 'meta.wikimedia.org';
    $cluster = $db->getCluster($domain);

    $requestPages = array(
                        'Checkuser' => array('page_title' => 'Steward_requests/Checkuser', 'level' => 3),
                        'Global' => array('page_title' => 'Steward_requests/Global', 'level' => 3),
                        'Bot status' => array('page_title' => 'Steward_requests/Bot_status', 'level' => 3),
                        'Permissions' => array('page_title' => 'Steward_requests/Permissions', 'level' => 4),
                        'Username changes' => array('page_title' => 'Steward_requests/Username_changes', 'level' => 3),
                        'SUL requests' => array('page_title' => 'Steward_requests/SUL_requests', 'level' => 3),
                    );

    // Loop over steward request pages.
    foreach($requestPages as $title => $page) {
        echo '<h2><a href="http://' . $domain . '/wiki/' . $page['page_title'] . '">' . $title . '</a></h2>';

        // Get contents.
        $content = getPage($page['page_title'], $forcelive);

        $iOpen = 0;
        $iUnhandled = 0;
        $aOldest = array(time(), '');
        $aUsers = array();
        $aRequests = array();
        
        if (!$content) {
            trigger_error('Could not get [[' . $page . ']].', E_USER_WARNING);
        }
        
        $offset = (strpos($content, '<!-- bof -->') ? strpos($content, '<!-- bof -->') : 0);

        $requests = preg_split('/\={' . $page['level'] . '}(.*?)\={' . $page['level'] . '}/', substr($content, $offset), 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        // Loop requests
        for($i = 1; $i < count($requests); $i = $i + 2) {
            $title = $requests[$i];
            $text = $requests[$i + 1];
            $handled = False;
            $info = array();

            // Ignore closed requests;
            if (preg_match('/\{\{[Cc]losed(?:\||\}\})/', $text) 
                || preg_match('/\s*?\|status\s*?=\s*?[Dd]one/', $text)
                || preg_match('/\s*?\|status\s*?=\s*?[Nn]ot done/', $text)
                || preg_match('/\{\{[Ss]tatus\|(?:[Dd]one|[Nn]ot done)(?:\||\}\})/', $text)) {
                $info['status'] = 'Closed';
            }
                   
            // Find timestamps
            $timestamps = array();
            preg_match_all('/\d{2}:\d{2}, \d{1,2} .*? \d{4} \(UTC\)/', $text, $matches);
            foreach($matches[0] as $m) {
                $timestamps[] = strtotime($m);
            }

            sort($timestamps);
            
            // If no timestamp is found it's probably an example.
            if ($timestamps[0] == 0) {
                $info['status'] = 'Ignored';
            }
            
            if ($timestamps[0] < $aOldest[0] && !isset($info['status'])) {
                $aOldest = array($timestamps[0], $title);
            }
            
            $info['t_old'] = strftime('%H:%M, %e %B %Y', $timestamps[0]);
            $info['t_new'] = strftime('%H:%M, %e %B %Y', $timestamps[count($timestamps)-1]);
            
            // Find users
            $users = array();
            preg_match_all('/\[\[([Uu]ser:|Special:Contributions\/)(?P<user>[^\|\]]*?)\|[^\]]*?\]\]/', $text, $matches);

            foreach($matches['user'] as $u) {
                if(isSteward($u)) {
                    $handled = True;
                    $item = array('name' => $u, 'steward' => True);
                } else {
                    $item = array('name' => $u, 'steward' => False);
                }
                if (!in_array($item, $users)) {
                    $users[] = $item;
                }
            }
            $info['users'] = $users;
            
            // Update number of unhandled requests.
            if (!isset($info['status'])) {
                $iOpen++;
                if (!$handled) {
                    $iUnhandled++;
                    $info['status'] = 'Unhandled';
                } else {
                    $info['status'] = 'Handled';
                }
            }
            
            // Add array to main array
            $aRequests[$title] = $info;
        }
        
        // Show statistics
        
        $sql = 'SELECT user_name
                FROM metawiki_p.revision
                JOIN metawiki_p.page
                ON page_id = rev_page
                JOIN metawiki_p.user
                ON user_id = rev_user
                JOIN metawiki_p.user_groups
                ON ug_user = user_id
                WHERE page_namespace = 0
                AND page_title = \'' . $page['page_title'] . '\'
                AND ug_group = \'steward\'
                AND rev_timestamp > DATE_SUB(NOW(), INTERVAL 1 MONTH) + 0
                GROUP BY user_id
                ORDER BY user_name ASC';
        $q = $db->performQuery($sql, $cluster);

        if (!$q) {
            $stewards = 'Unknown';
        } else {
            $stewards = array();
            while($row = mysql_fetch_assoc($q)) {
                $stewards[] = $row['user_name'];
            }
            $stewards = join(', ', $stewards);
        }
           
        echo '<p>';
        echo 'Open requests: ' . $iOpen . ' (<b>' . $iUnhandled . ' unhandled</b>).<br />';
        echo 'Oldest open request: <i>' . $aOldest[1] . '</i> opened at <b>' . strftime('%H:%M, %e %B %Y', $aOldest[0]) . '</b>. <br />';
        echo 'Recent stewards: <i>' . $stewards . '</i>.';
        echo '</p>';
        
        // List open requests
        echo '<table class="prettytable sortable" style="width: 100%">';
        echo '<tr><th style="width: 30%">Title</th><th style="width: 20%">First comment</th><th style="width: 20%">Last comment</th><th style="width: 30%">Users</th>';
        foreach($aRequests as $title => $info) {
            if($info['status'] == 'Handled' || $info['status'] == 'Unhandled'){
                $sUsers = '';
                foreach ($info['users'] as $u) {
                    if($u['steward']) {
                        $sUsers .= ', <b>' . $u['name'] . '</b>';
                    } else {
                        $sUsers .= ', ' . $u['name'];
                    }
                }
                $sUsers = (strlen($sUsers) > 2 ? substr($sUsers, 2) : $sUsers);
                $link = '<a href="http://' . $domain . '/wiki/' . $page['page_title'] . '#' . anchorencode($title) . '">' . $title . '</a>';
                echo '<tr>';
                echo '<td>' . $link . '</td><td>' . $info['t_old'] . '</td><td>' . $info['t_new'] . '</td><td>' . $sUsers . '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
    }

    // Save results to cache    
    $f = fopen($cacheFile, 'w');
    fwrite($f, ob_get_contents());
    fclose($f);

    // Send the output to the browser
    ob_end_flush();
        
}
require_once 'inc/footer.inc.php';
?>
