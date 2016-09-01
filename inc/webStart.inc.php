<?php
error_reporting(E_ERROR|E_CORE_ERROR|E_ALL|E_COMPILE_ERROR);
ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/errorhandler.inc.php';
require_once dirname(__FILE__).'/database.class.php';
require_once dirname(__FILE__).'/../php/various.inc.php';

// Set database connection
$db = TSDatabase::singleton();

// Set database variables
$replag = $db->getAllReplag();
$warning = $db->getWarning();
?>
