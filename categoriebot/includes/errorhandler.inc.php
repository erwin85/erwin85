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
			echo '<p><b>' .$type . ': </b>' . $error_msg_html . '</p>';
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
        require_once 'inc/template.inc.php';
        $t = new Template('inc/errortemplate.html');

        // Simple template variables
        $t->setVar('title', $GLOBALS['title']);
        $t->setVar('pagetitle', $GLOBALS['pagetitle']);
        $t->setVar('modified', $GLOBALS['modified']);

        $t->setVar('content', ob_get_clean());

        echo $t->toString();
	exit(0);
	}
}

set_error_handler('ErrorHandler');
date_default_timezone_set('UTC');
?> 
