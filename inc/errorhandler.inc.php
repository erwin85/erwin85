<?php
function ErrorHandler($errno, $errstr, $errfile, $errline,  $errcontext)
{
        if (preg_match('/save/i', $errstr) && !isset($_GET['mdbg'])) {
                $error_msg = 'Obtaining the results for your query took too much time. The execution has automatically been stopped.';
                $error_msg_html = $error_msg;
        } else if (preg_match('/mysql/i', $errstr) && !isset($_GET['mdbg'])) {
        $error_msg = 'There were MySQL errors at ' . date("r") . $errno . $errstr . $errfile . $errline . $errcontext . '. It is possible that this tool won\'t work right now. If this error message persists please file a bug.';
        $error_msg_html = $error_msg;
    } else {
            $error_msg = $errstr . ' in ' . $errfile . ' on ' . $errline . ' at ' . date("r") . '.';
            $error_msg_html = $errstr . ' in <span style="font-weight:bold">' . $errfile . '</span> on line <span style="font-weight:bold">' . $errline . '</span> at ' . date("r") . '.';
        }
        $email_addr = 'erwin85@toolserver.org';
        $remote_dbg = "localhost";
        $log_file = '';

        $email = False;
        $stdlog = False;
        $remote = False;
        $display = True;

        $notify = True;
        $halt_script = True;


        switch($errno) {
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

        if($notify and isset($error_msg)) {
                $error_msg = $type . $error_msg;

                if($email) {
                        error_log($error_msg, 1, $email_addr);
                }

                if($remote) {
                        error_log($error_msg, 2, $remote_dbg);
                }

                if($display) {
                        echo '<p><span style="font-weight:bold">' . $type . ':</span> ' . $error_msg_html . '</p>';
                }

                if($stdlog) {
                        if($log_file == '') {
                                error_log($error_msg, 0);
                        } else {
                                error_log($error_msg, 3, $log_file);
                        }
                }
        }

        if($halt_script) {
        require_once dirname(__FILE__).'/footer.inc.php';
        exit(0);
        }
}

if (error_reporting() != 0) {
    # Added ", error_reporting() & ~E_DEPRECATED" as a temporary
    # workaround for T140421.                      -- scfc, 2017-02-09
    set_error_handler('ErrorHandler', error_reporting() & ~E_DEPRECATED);
}
?>
