<?php
function getPage($pagename, $forcelive=False)
{
    if(!$forcelive) {
        $ch = curl_init('http://toolserver.org/~daniel/WikiSense/WikiProxy.php?wiki=nl.wikipedia.org&title=' . urlencode($pagename));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
        $output = curl_exec($ch);
        curl_close($ch);
    } else {
        $ch = curl_init('http://nl.wikipedia.org/w/index.php?title=' . urlencode($pagename) . '&action=raw&ctype=text');
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Wikipedia Bot - http://toolserver.org/~erwin85');
	    $output = curl_exec($ch);
	    curl_close($ch);
    }    
    return $output;
}

$page = mysql_real_escape_string($_GET['page']);
$content = getPage($page);
preg_match_all('/\[\[[Oo]verleg[_ ]gebruiker\:.*?\]\]/', $content, $matches);
$aUsers = array();
foreach($matches[0] as $link)
{
	$user = substr($link, 20, strpos(']]', $link)-2);
	if (!in_array($user, $aUsers))
	{
		$aUsers[] = $user;
	}
}
preg_match_all('/\{\{linkgebruiker\|.*?\}\}/', $content, $matches);
foreach($matches[0] as $link)
{
	$user = $aUsers[] = substr($link, 16, strpos('}}', $link)-2);
	if (!in_array($user, $aUsers))
	{
		$aUsers[] = $user;
	}
}

$users = implode('|', $aUsers);

header('location: contribs.php?users=' . $users . '&lang=nl&family=wikipedia');
?>
