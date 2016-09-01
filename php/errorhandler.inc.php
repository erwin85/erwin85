<?php
function ErrorHandler($errno, $errstr, $errfile, $errline)
{
	$error_msg = $errstr . ' in ' . $errfile . ' on ' . $errline . ' at ' . date("r") . '.';
	$error_msg_html = $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b> at ' . date("r") . '.';
	$email_addr = 'erwin85@hemlock.ts.wikimedia.org';
	$remote_dbg = "localhost";
	$log_file = '';
  
	$email = False;
	$stdlog = False;
	$remote = False;
	$display = True;
   
	$notify = True;
	$halt_script = True;


	switch($errno)
	{
		case E_NOTICE:
			$halt_script = False;
			$type = "Notice";
			$display = False;
			break;

		case E_USER_NOTICE:
			$halt_script = False;
			$type = "Notice";
			$error_msg_html = $errstr;
			break;

		case E_COMPILE_WARNING:
		case E_CORE_WARNING:
		case E_WARNING:
			$halt_script = False;
			$type = "Warning";
			break;
		    
		case E_USER_WARNING:
			$halt_script = False;
			$type = "Warning";
			$error_msg_html = $errstr;
			break;

		case E_USER_ERROR:
			$type = "Fatal Error";
			$error_msg_html = $errstr;
			break;

	    case E_COMPILE_ERROR:
		case E_CORE_ERROR:
		case E_ERROR:    
			$type = "Fatal Error";
			break;

		case E_PARSE:
			$type = "Parse Error";
			break;

		default:    
			$type = "Unknown Error";
			break;
	}

	if($notify)
	{
		$error_msg = $type . $error_msg;

		if($email)
		{
			error_log($error_msg, 1, $email_addr);
		}

		if($remote)
		{
			error_log($error_msg, 2, $remote_dbg);
		}

		if($display)
		{
			echo '<br /><b>' .$type . ': </b>' . $error_msg_html;
		}

		if($stdlog)
		{
			if($log_file == '')
			{
				error_log($error_msg, 0);
			}
			else
			{
				error_log($error_msg, 3, $log_file);
			}
		}
	}

	if($halt_script)
	{
?>
</div>
<div id="column-one">
<div class="portlet" id="p-navigation">
<h5>erwin85's tools</h5>
<div class="pBody">
<ul>
<li><a href="categorycount.php">category count</a></li>
<li><a href="blockmsg.php">blockmsg</a></li>
</ul>
</div>
</div>

<div class='portlet' id='p-navigation2'>
<h5>navigation</h5>
<div class='pBody'>
<ul>
<li><a href="http://tools.wikimedia.de/~interiot/cgi-bin/tstoc" title="Toolserver TOC">Toolserver TOC</a></li>
</ul>
</div>
</div>

</div>
<div id="footer">
<div id="f-poweredbyico"><a href="/"><img style = "border:0; float:left; padding: 5px;" src="http://tools.wikimedia.de/images/wikimedia-toolserver-button.png" alt="Powered by Wikimedia Toolserver" title="Powered by Wikimedia Toolserver" height="31" width="88"></a></div>
<ul id="f-list">
<li id="lastmod">This page was last modified 29 June 2007.</li>
<li id="about">This tool is written by <a href="http://nl.wikipedia.org/wiki/Gebruiker:Erwin">Erwin85</a>.</li>
</ul>
</div>

</div>
</body>
</html>
<?php
	exit(0);
	}
}

set_error_handler('ErrorHandler');
date_default_timezone_set('UTC');
?> 
