<?php
function getPage($pagename, $forcelive=False)
{
    if(!$forcelive) {
        $ch = curl_init('http://toolserver.org/~daniel/WikiSense/WikiProxy.php?wiki=nl.wikipedia.org&title=' . urlencode($pagename));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
        $output = curl_exec($ch);
        curl_close($ch);
    } else {
        $ch = curl_init('https://nl.wikipedia.org/w/index.php?title=' . urlencode($pagename) . '&action=raw&ctype=text');
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Wikipedia Bot - https://tools.wmflabs.org/erwin85');
	    $output = curl_exec($ch);
	    curl_close($ch);
    }    
    return $output;
}

//Set page variables needed for errorhandler
$title = 'blockmsg';
$pagetitle = 'blockmsg';
$modified = '18 May 2007';
date_default_timezone_set('UTC');

// Include files
//require_once 'inc/webStart.inc.php';

$scripts =  '<script type="text/javascript" language="javascript" src="blockmsg.js"></script>';
$replag = array('sql-s1' => 'n.v.t.', 'sql-s2' => 'n.v.t.', 'sql-s3' => 'n.v.t.');

// Start page
require_once 'inc/header.inc.php';

if(!isset($_GET['page']) && !isset($_POST['wpBlock']) && !isset($_POST['wpUnBlock'])) {
	echo '<p><b>blockmsg</b> is een tool om meerdere gebruikers of adressen snel te blokkeren of te deblokkeren. Vanuit de dossiers kan er met het sjabloon <a href="https://nl.wikipedia.org/wiki/Sjabloon:blockmsg">blockmsg</a> gelinkt worden naar deze pagina.</p> <p>blockmsg maakt het mogelijk om één keer de blokkadeduur, reden etc. op te geven en met die gegevens alle gebruikers te blokkeren in plaats van voor elke gebruiker de blokkeeropties in te moeten vullen. Op dezelfde manier kan door één keer de deblokkeerreden op te geven een groep gebruikers worden gedeblokkeerd.</p>';
} elseif(isset($_POST['wpUnBlock'])) {
	$abort = False;
	$aUsers = explode('|', $_POST['wpBlockUsers']);
	$page = $_POST['wpBlockMsg'];
	$wpUnblockReason = $_POST['wpUnblockReason'];
	if ($wpUnblockReason == '')
	{
		echo 'Geef een reden voor de deblokkade op!<br />';
	}
	else
	{
		echo '<h3>Deblokkeer de gebruikers genoemd op <a href="https://nl.wikipedia.org/wiki/' . $page . '">[[' . $page .']]</a></h3>';
		echo '<h4>Gebruikers</h4>';
		foreach($aUsers as $user)
		{
			echo '<a href="https://nl.wikipedia.org/wiki/Gebruiker:' . $user . '">' . $user . '</a> (<a href="https://nl.wikipedia.org/w/index.php?title=Speciaal:Log&type=block&page=Gebruiker:' . $user .'">Blokkeerlogboek</a>) - ';
		}
		
		echo '<h4>Deblokkeerlinks</h4>';
		#echo '<b><a href="https://nl.wikipedia.org/w/index.php?title=Gebruiker:Erwin85/blockmsg&users=' . $_POST['wpBlockUsers'] . '&wpUnblockReason=' . $wpUnblockReason . '&unblock=1">Deblokkeer alle gebruikers</a></b><br /><br />';
		foreach($aUsers as $user)
		{	#https://nl.wikipedia.org/w/index.php?title=Speciaal:IpBlokkeerlijst&action=unblock&ip=Blokvandaal
			echo '<a href="https://nl.wikipedia.org/w/index.php?title=Speciaal:IpBlokkeerlijst&action=unblock&ip=' . $user . '&wpUnblockReason=' . $wpUnblockReason. '&confirmblock">Deblokkeer ' . $user . '</a><br />';
		}
	}
} elseif(isset($_POST['wpBlock'])) {
	$abort = False;
	$aUsers = explode('|', $_POST['wpBlockUsers']);
	$page = $_POST['wpBlockMsg'];
	$wpBlockExpiry = $_POST['wpBlockExpiry'];
	$wpBlockOther = $_POST['wpBlockOther'];
	$wpBlockReasonList = $_POST['wpBlockReasonList'];
	$wpBlockReason = $_POST['wpBlockReason'];
	$wpAnonOnly = $_POST['wpAnonOnly'];
	$wpCreateAccount = $_POST['wpCreateAccount'];
	$wpEnableAutoblock = $_POST['wpEnableAutoblock'];
	#wpBlockExpiry: otherwpBlockOther: wpBlockReasonList: otherwpBlockReason: Herhaald vandalisme
	if ($wpBlockExpiry == 'other')
	{
		if($wpBlockOther == '')
		{
			echo 'Geef een duur voor de blokkade op!<br />';
			$abort = True;
		}
		else
		{
			$BlockOther = $wpBlockOther;
		}
	}
	else
	{
		$BlockOther = $wpBlockExpiry;
	}

	if ($wpBlockReasonList == 'other')
	{
		if($wpBlockReason == '')
		{
			echo 'Geef een reden voor de blokkade op!<br />';
			$abort = True;
		}
		else
		{
			$BlockReason = $wpBlockReason;
		}
	}
	else
	{
		$BlockReason = $wpBlockReasonList;
	}
	if ($abort == False)
	{
		/*echo 'wpBlockExpiry: ' . $wpBlockExpiry;
		echo 'wpBlockOther: ' . $wpBlockOther;
		echo 'wpBlockReasonList: '. $wpBlockReasonList;
		echo 'wpBlockReason: ' . $wpBlockReason;*/
		echo '<h3>Blokkeer de gebruikers genoemd op <a href="https://nl.wikipedia.org/wiki/' . $page . '">[[' . $page .']]</a></h3>';
		echo '<h4>Gebruikers</h4>';
		foreach($aUsers as $user)
		{
			echo '<a href="https://nl.wikipedia.org/wiki/Gebruiker:' . $user . '">' . $user . '</a> (<a href="https://nl.wikipedia.org/w/index.php?title=Speciaal:Log&type=block&page=Gebruiker:' . $user .'">Blokkeerlogboek</a>) - ';
		}
		
		echo '<h4>Blokkeerlinks</h4>';
		#echo '<b><a href="https://nl.wikipedia.org/w/index.php?title=Gebruiker:Erwin85/blockmsg&users=' . $_POST['wpBlockUsers'] . '&wpBlockReason=' . $BlockReason . '&wpBlockOther=' . $BlockOther . '&wpAnonOnly=' . $wpAnonOnly . '&wpCreateAccount=' . $wpCreateAccount . '&wpEnableAutoblock=' . $wpEnableAutoblock. '">Blokkeer alle gebruikers</a></b><br /><br />';
		foreach($aUsers as $user)
		{
			echo '<a href="https://nl.wikipedia.org/w/index.php?title=Speciaal:BlokkeerIp&ip=' . $user . '&wpBlockReason=' . $BlockReason. '&wpBlockOther=' . $BlockOther . '&wpAnonOnly=' . $wpAnonOnly . '&wpCreateAccount=' . $wpCreateAccount . '&wpEnableAutoblock=' . $wpEnableAutoblock . '&confirmblock">Blokkeer ' . $user . '</a><br />';
		}
		//https://nl.wikipedia.org/w/index.php?title=Gebruiker:Erwin85/blockmsg&users=Blokvandaal|Blokpop&wpBlockReason=blockmsg.js&wpBlockOther=12%20hours&wpAnonOnly=1&wpCreateAccount=1&wpEnableAutoblock=1
	}
}
else
{
	$page = $_GET['page'];
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
	#$aUsers = array('Blokpop', 'Blokvandaal');
	$unblock = $_GET['unblock'];
	if ($unblock == 1)
	{
		echo '<h3>Deblokkeer de gebruikers genoemd op <a href="https://nl.wikipedia.org/wiki/' . $page . '">[[' . $page .']]</a></h3>';
		echo '<h4>Gebruikers</h4>';
		foreach($aUsers as $user)
		{
			echo '<a href="https://nl.wikipedia.org/wiki/Gebruiker:' . $user . '">' . $user . '</a> (<a href="https://nl.wikipedia.org/w/index.php?title=Speciaal:Log&type=block&page=Gebruiker:' . $user .'">Blokkeerlogboek</a>) - ';
		}
?>
<h4>Deblokkeeropties</h4>
<form method="post" action="blockmsg.php" id="unblockip"><table border="0"><tbody><tr>
			<tr>

				<td align="right">
					Reden
				</td>
				<td><input name="wpUnblockReason" size="40" value="" tabindex="2" type="text"></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input value="Creëer deblokkeerlinks" name="wpUnBlock" tabindex="3" type="submit"></td>
			</tr></tbody></table><input name="wpBlockMsg" value="<?=$_GET['page'];?>" type="hidden"><input name="wpBlockUsers" value="<?=implode("|", $aUsers);?>" type="hidden"></form>
<?php
	}
	else
	{
		echo '<h3>Blokkeer de gebruikers genoemd op <a href="https://nl.wikipedia.org/wiki/' . $page . '">[[' . $page .']]</a></h3>';
		echo '<h4>Gebruikers</h4>';
		foreach($aUsers as $user)
		{
			echo '<a href="https://nl.wikipedia.org/wiki/Gebruiker:' . $user . '">' . $user . '</a> (<a href="https://nl.wikipedia.org/w/index.php?title=Speciaal:Log&type=block&page=Gebruiker:' . $user .'">Blokkeerlogboek</a>) - ';
		}
?>
<h4>Blokkeeropties</h4>
<form id="blockip" method="post" action="blockmsg.php">
	<table border='0'>
		<tr>
			<td align="right"><label for="wpBlockExpiry">Verloop (maak een keuze)</label></td>
			<td>
				<select tabindex='2' id='wpBlockExpiry' name="wpBlockExpiry" onchange="considerChangingExpiryFocus()">
					<option value="other">ander verloop</option><option value="15 min">15 minuten</option><option value="1 hour">1 uur</option><option value="2 hours">2 uur</option><option value="6 hours">6 uur</option><option value="12 hours">12 uur</osubmitption><option value="1 day">1 dag</option><option value="3 days">3 dagen</option><option value="1 week">1 week</option><option value="2 weeks">2 weken</option><option value="1 month">1 maand</option><option value="3 months">3 maanden</option><option value="6 months">6 maanden</option><option value="1 year">1 jaar</option><option value="indefinite">onbepaald</option>

				</select>
			</td>
			
		</tr>
		<tr id='wpBlockOther'>
			<td align="right"><label for="mw-bi-other">Ander verloop</label></td>
			<td>
				<input name="wpBlockOther" size="45" value="" tabindex="3" id="mw-bi-other" />
			</td>

		</tr>
			<tr>
				<td align="right"><label for="wpBlockReasonList">Reden</label></td>
				<td>
					<select tabindex='4' id="wpBlockReasonList" name="wpBlockReasonList">
						<option value="other">Andere reden</option><optgroup label="Veelvoorkomende reden"><option value="Herhaald vandalisme">Herhaald vandalisme</option><option value="Toevoegen van onjuiste informatie">Toevoegen van onjuiste informatie</option><option value="Linkspam">Linkspam</option><option value="Persoonlijke aanval">Persoonlijke aanval</option><option value="Misbruik van sokpoppen">Misbruik van sokpoppen</option><option value="Ongewenste gebruikersnaam">Ongewenste gebruikersnaam</option></optgroup>

						</select>
				</td>
			</tr>
		<tr id="wpBlockReason">
			<td align="right"><label for="mw-bi-reason">Andere/extra reden:</label></td>
			<td>
				<input name="wpBlockReason" size="45" value="Herhaald vandalisme" tabindex="5" id="mw-bi-reason" />
			</td>

		</tr>
		<tr id='wpAnonOnlyRow'>
			<td>&nbsp;</td>
			<td>
				<input name="wpAnonOnly" type="checkbox" value="1" checked="checked" id="wpAnonOnly" tabindex="6" />&nbsp;<label for="wpAnonOnly">Blokkeer alleen anonieme gebruikers</label>
			</td>
		</tr>
		<tr id='wpCreateAccountRow'>

			<td>&nbsp;</td>
			<td>
				<input name="wpCreateAccount" type="checkbox" value="1" checked="checked" id="wpCreateAccount" tabindex="7" />&nbsp;<label for="wpCreateAccount">Voorkom aanmaken accounts</label>
			</td>
		</tr>
		<tr id='wpEnableAutoblockRow'>
			<td>&nbsp;</td>
			<td>

				<input name="wpEnableAutoblock" type="checkbox" value="1" checked="checked" id="wpEnableAutoblock" tabindex="8" />&nbsp;<label for="wpEnableAutoblock">Automatisch de IP-adressen van deze gebruiker blokkeren</label>
			</td>
		</tr>
		
		<tr>
			<td style='padding-top: 1em'>&nbsp;</td>
			<td style='padding-top: 1em'>
				<input type="submit" value="Creëer Blokkeerlinks" name="wpBlock" tabindex="10" />
			</td>

		</tr>
	</table><input name="wpBlockMsg" value="<?=$_GET['page'];?>" type="hidden"><input name="wpBlockUsers" value="<?=implode("|", $aUsers);?>" type="hidden"></form>
<?php
	}
}
require_once 'inc/footer.inc.php';
?>
