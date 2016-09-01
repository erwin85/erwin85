<?php
class Session
{
    public $logged_in;
    public $user_id;
    public $user = array();
    public $challange;
    public $dbchallenge;
    public $registered;

    private $_login;
    private $_register;
    private $_db;
    private $_debug;
    private $_session_id;

    function __construct($login = true, $register = false, $logout = false, $confirm = false)
    {
        $this->logged_in = false;
        $this->registered = false;
        $this->_db = TSDatabase::singleton(false, false, false, true);
        $this->_debug = false;
        $this->_login = $login;
        $this->_register = $register;
        $this->_session_id = $_COOKIE['session_id'];

        if ($logout) {
            $this->_debug('User logout.');
            $this->_destroySession();
            return false;
        } elseif ($confirm) {
            $this->_confirmUser();
            return false;
        }
                
        $checksession = $this->_checkSession();
        if ($checksession && !$this->logged_in) {
            if ($this->_login) {
                $this->_checkLoginForm();
            } elseif ($this->_register) {
                $this->_checkRegisterForm();
            }
        }
        
        if (!$this->logged_in) {
            $this->_startSession();
        } else {
            $this->_setUser();
        }
        
    }
    
    /* Nothing to destruct
    function __destruct()
    {
        //Do nothing;
    }
    */
    
    /****************************************************
    Public functions
    ****************************************************/

    /****************************************************
    Private functions
    ****************************************************/
    private function _debug($msg)
    {
        if ($this->_debug) {
            echo '<p>' . $msg . '</p>';
        }
    }
        
    private function _checkSession()
    {
        if (!isset($this->_session_id) || empty($this->_session_id)) {
            $this->_debug('session_id is empty.');
            return false;
        }
    
        $sql = "SELECT user_id, challenge, INET_NTOA(ip_address) AS ip_address, fix_to_ip FROM u_erwin85.sessions WHERE session_id = '" . $this->_session_id . "' AND TTL > " . time();
        $q = $this->_db->performQuery($sql, 'sql');
        
        //Check if we have a match  
        if(mysql_num_rows($q) == 0) {
            //Debug
            $this->_debug('The session was not found in the database.');
            return false;
        }
        $result = mysql_fetch_assoc($q);
        $this->user_id = $result['user_id'];
        $ip_address = $result['ip_address'];
        $fix_to_ip = $result['fix_to_ip'];
        $this->dbchallenge = $result['challenge'];
        
        if ($fix_to_ip) {
            if ($ip_address == $_SERVER['REMOTE_ADDR']) {
                if ($this->user_id != 0) {
                    $this->logged_in = true;
                    $this->_debug('The user is logged in on a ip locked session.');
                    return true;
                }
            } else {
                $this->_debug('The session has been hijacked.');
                return false;
            }
        } else {
            if ($this->user_id != 0) {
                $this->logged_in = True;
                $this->_debug('The user is logged in.');
                return true;
            } else {
                $this->_debug('The user is not logged in, however check if the user just logged in.');
                return true;
            }
        }
    }

    private function _checkLoginForm()
    {
        $this->_debug('Check if a form was submitted');
        if (isset($_POST['submit'])) {
            //Debug
            $this->_debug('The user did supply credentials.');

            if (!get_magic_quotes_gpc()) {
                $response = mysql_real_escape_string($_POST['response']);
                $user_name = mysql_real_escape_string($_POST['user_name']);
                $password = mysql_real_escape_string($_POST['password']);
            } else {
                $response = $_POST['response'];
                $user_name = $_POST['user_name'];
                $password = $_POST['password'];
            }
            
            if ($_POST['fix_to_ip'] == '1') {
                $fix_to_ip = 1;
            } else {
                $fix_to_ip = 0;
            }
            
            if(isset($response) && !empty($response) && (!ctype_alnum($user_name) || !ctype_alnum($response))) {
                die('Wrong input');
            }

            /*
                Execute a query to select User data based on the submitted username
                Normally we would use some escaping here - its omitted for clarity (is magic_quotes dependent)
            */
            $sql = "SELECT user_id, user_name, password FROM u_erwin85.user_accounts WHERE user_name = '" . $user_name . "'";
            $q = $this->_db->performQuery($sql, 'sql');

            /*
                Ensure we got a result
                No result would indicate the User does not exist and must register an account
                (code for registering is not included in this tutorial)
            */
            if(mysql_num_rows($q) == 0) {
                die('Wrong input');
            }

            /*
                Fetch the User data into an associative array
            */
            $user = mysql_fetch_assoc($q);

            //Generate expected response
            $response_string = strtolower($user['user_name']) . ':' . $user['password'] . ':' . $this->dbchallenge;
            $expected_response = sha1($response_string);

            //Compare response
            if($response == $expected_response) {
                $this->logged_in = true;
                $sql = "DELETE FROM u_erwin85.sessions WHERE session_id = '" . $this->_session_id . "' OR TTL < " . time();
                $q = $this->_db->performQuery($sql, 'sql');
                
                $this->_debug('The user just logged in.');
                $session_id = sha1(uniqid(mt_rand(), true));
                $challenge = sha1(uniqid(mt_rand(), true));
                $user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 150);
                $this->user_id = $user['user_id'];
                
                $sql = "INSERT INTO u_erwin85.sessions (session_id, challenge, user_id, ip_address, fix_to_ip, user_agent, TTL)";
                $sql .= " VALUES ('" . $session_id . "', '" . $challenge . "', " . $user['user_id'] . ", INET_ATON('" . $_SERVER['REMOTE_ADDR'] . "'), " . $fix_to_ip . ", '" . $user_agent . "', " . (time() + 360) . ")";
                
                $q = $this->_db->performQuery($sql, 'sql');
                setcookie('session_id', $session_id, time() + 30000);
            } else {
                $this->_debug('Response wasn\'t expected.');
            }
        }
    }

    private function _checkRegisterForm()
    {
        $this->_debug('Check if a register form was submitted');
        if (isset($_POST['submit'])) {
            //Debug
            $this->_debug('The user did supply credentials.');

    		if (!get_magic_quotes_gpc()) {
    			$response = mysql_real_escape_string($_POST['response']);
    			$user_name = mysql_real_escape_string($_POST['user_name']);
    			$password = mysql_real_escape_string($_POST['password']);
    			$passwordcheck = mysql_real_escape_string($_POST['passwordcheck']);
    		} else {
    		    $response = $_POST['response'];
    			$user_name = $_POST['user_name'];
    			$password = $_POST['password'];
    			$passwordcheck = $_POST['passwordcheck'];
    		}
    		
    		$sql = "SELECT user_id, ug_group FROM nlwiki_p.user LEFT JOIN nlwiki_p.user_groups ON user_id = ug_user WHERE user_name = '" . $user_name . "'";
    		$q = $this->_db->performQuery($sql, 'sql-s2');

    		/*
    		    Ensure we got a result
    		    No result would indicate the user does not exist
    		*/
    		if(mysql_num_rows($q) == 0) {
    		    die('Gebruik je Wikipedianaam.');
    		}
    		
    		$is_mod = false;
    		while ($row = mysql_fetch_assoc($q))
    		{
    			if (!isset($mw_user_id)) {
    				$mw_user_id = $row['user_id'];
    			}
    			if ($row['ug_group'] == 'sysop') {
    				//User is a sysop
    				$is_mod = True;
    			}
    		}
    	
    		if(isset($response) && !empty($response) && (!ctype_alnum($user_name) || !ctype_alnum($response))) {
    		    die('Wrong input.');
    		}

    		//Generate expected response
    		$response_string = strtolower($user_name) . ':' . $this->dbchallenge;
    		$expected_response = sha1($response_string);

    		//Compare response
    		if($response == $expected_response) {
    			if($password != $passwordcheck) {
    				die('Wrong input.');
    			}
    			
    			$sql = "INSERT INTO u_erwin85.user_accounts (mw_user_id, user_name, password, enabled)";
    			$sql .= " VALUES (" . $mw_user_id . ", '" . $user_name . "', '" . $password . "', 0)";
    			$q = $this->_db->performQuery($sql, 'sql');
    			
    			$sql = "SELECT LAST_INSERT_ID() AS user_id";
    			$q = $this->_db->performQuery($sql, 'sql');
    			$result = mysql_fetch_assoc($q);
    			$user_id = $result['user_id'];
    		
    			$authe_code = sha1(uniqid(mt_rand(), true));
    			$autho_code = sha1(uniqid(mt_rand(), true));
    			$authorised = $is_mod ? 1 : 0;
    			
    			$sql = "INSERT INTO u_erwin85.activate_user (user_id, authenticated, authentication_code, authorised, authorisation_code)";
    			$sql .= " VALUES (" . $user_id . ", 0, '" . $authe_code . "', " . $authorised . ", '" . $autho_code . "')";
    			$q = $this->_db->performQuery($sql, 'sql');
    			
    		    $this->_destroySession();
     			
    		    $this->_debug('The user just registered.');
    		    $this->registered = true;
    		    require_once '/home/erwin85/libs/phpbot/BasicBot.php';
    			$MailBot = new BasicBot();
    			$subject = 'Aanmelding opdrachtenbot';
$mailtext = <<<END
Beste $user_name,

Je kunt je aanmelding bij Erwins opdrachtenbot voltooien door onderstaand adres te bezoeken:

http://toolserver.org/~erwin85/categoriebot/confirm.php?user=$user_id&code=$authe_code

Als je je niet hebt aangemeld voor de opdrachtenbot, heeft iemand anders geprobeerd om onder jouw naam aan te melden. Laat me dit s.v.p. weten.

Groet,
Erwin
END;
    			if ($MailBot->emailUser($user_name, $mailtext, $subject)) {
    				$this->_debug('The user was sent an activation mail.');
    			} else {
    				$this->_debug('Failed to send activation mail.');
    				mail('erwin@kroekenstoel.nl', 'phpBot e-mail voor ' . $user_name, $mailtext);
    			}

    		} else {
    			$this->_debug('Response wasn\'t expected.');
    			$this->_debug('Response: ' . $response);
    			$this->_debug('Bla: '. $expected_response);
    		}
	    }
    }
    // End _checkRegisterForm
    private function _startSession()
    {
        //Destroy cookie
        $this->_destroySession();
        
        //Insert information in session table
        $session_id = sha1(uniqid(mt_rand(), true));
        $challenge = sha1(uniqid(mt_rand(), true));
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 150);
        
        $sql = "INSERT INTO u_erwin85.sessions (session_id, challenge, user_id, ip_address, fix_to_ip, user_agent, TTL)";
        $sql .= " VALUES ('" . $session_id . "', '" . $challenge . "', 0, INET_ATON('" . $_SERVER['REMOTE_ADDR'] . "'), 0, '" . $user_agent . "', " . (time() + 360) . ")";
        
        $q = $this->_db->performQuery($sql, 'sql');
        if($q) {
            setcookie('session_id', $session_id);
            $this->_debug('We just started a new session.');
            $this->_session_id = $session_id;
            $this->challenge = $challenge;
         } else {
            $this->_debug('Failed to start session.');
         }
    }
    
    private function _destroySession()
    {
        setcookie('session_id', '', time());
        $sql = "DELETE FROM u_erwin85.sessions WHERE session_id = '" . $this->_session_id . "' OR TTL < " . time();
        $q = $this->_db->performQuery($sql, 'sql');
    }

    private function _setUser()
    {
        if (isset($this->user_id)) {
            $this->_debug('Getting user information for user_id: ' . $this->user_id);
            $sql = "SELECT user_id, user_name, mw_user_id, enabled FROM u_erwin85.user_accounts WHERE user_id = " . $this->user_id;
            $q = $this->_db->performQuery($sql, 'sql');
            if(mysql_num_rows($q) == 0) {
                $this->_debug('No user information found.');
                $this->_destroySession($this->_session_id);
                $this->_startSession();
            }

            $user = mysql_fetch_assoc($q);
            $this->user = $user;
        }
    }
    
    private function _confirmUser()
    {
        $authenticated = false;
        if(isset($_GET['user'])) {
            $this->_debug('Checking confirmation code.');
        	if (!get_magic_quotes_gpc()) {
        		$user_id = mysql_real_escape_string($_GET['user']);
        		$code = mysql_real_escape_string($_GET['code']);
        	} else {
        		$user_id = $_GET['user'];
        		$code = $_GET['code'];
        	}
        	
        	//If 1 the admin wants to authorize the user, if !isset the user wants to authenticate
        	$auth = (isset($_GET['auth'])) ? True : False;	
        	if(!$auth) {
        		//User wants to authenticate himself
        		$this->_debug('User wants to authenticate.');
        		$sql = "SELECT authorised, authorisation_code, user_name FROM u_erwin85.activate_user";
        		$sql .= " LEFT JOIN u_erwin85.user_accounts ON activate_user.user_id = user_accounts.user_id";
        		$sql .= " WHERE activate_user.user_id = " . $user_id . " AND authentication_code = '" . $code . "'";
        		$q = $this->_db->performQuery($sql, 'sql');
        		if(mysql_num_rows($q) == 0) {
        		    echo 'Wrong input';
        		    return false;
        		}

        		/*
        		    Fetch the User data into an associative array
        		*/
        		$result = mysql_fetch_assoc($q);
        		$auth_code = $result['authorisation_code'];
        		$authorised = $result['authorised'] ? true : false;
        		$user_name = $result['user_name'];
        		
        		$sql = "UPDATE u_erwin85.activate_user SET authenticated = 1 WHERE user_id = " . $user_id . " AND authentication_code = '" . $code . "'";
        		$q = $this->_db->performQuery($sql, 'sql');
        		$authenticated = true;
        		if($authorised) {
        			//User is all set to use the bot
        			$this->_debug('User is welcome.');
        			$sql = "UPDATE u_erwin85.user_accounts SET enabled = 1 WHERE user_id = " . $user_id;
        			$q = $this->_db->performQuery($sql, 'sql');
        			echo 'Je kunt nu inloggen en opdrachten geven.';
        		} else {
$mailtext = <<<END
Beste Erwin,

Gebruiker $user_name heeft zich net geidentifeerd bij de opdrachtenbot. Als je hem wilt toelaten bezoek:

http://toolserver.org/~erwin85/categoriebot/confirm.php?user=$user_id&code=$auth_code&auth=1

Groet,
Erwins mailslaaf
END;
        			mail('erwin@kroekenstoel.nl', 'Authoriseer gebruiker opdrachtenbot', $mailtext);
        			echo 'Erwin moet je nog toelaten. Daarna kun je opdrachten geven.';
        		}
        	} else {
        		//Admins wants to authenticate user
        		$sql = "SELECT authenticated FROM u_erwin85.activate_user WHERE user_id = " . $user_id . " AND authorisation_code = '" . $code . "'";
        		$q = $this->_db->performQuery($sql, 'sql');
        		if(mysql_num_rows($q) == 0)
        		{
        		    echo 'Wrong input';
        		    return false;
        		}

        		/*
        		    Fetch the User data into an associative array
        		*/
        		$result = mysql_fetch_assoc($q);
        		$authenticated = $result['authenticated'] ? true : false;
        		
        		$sql = "UPDATE u_erwin85.activate_user SET authorised = 1 WHERE user_id = " . $user_id . " AND authorisation_code = '" . $code . "'";
        		$q = $this->_db->performQuery($sql, 'sql');
        		$authorised = true;
        		if($authenticated) {
        			//User is all set to use the bot
        			$sql = "UPDATE u_erwin85.user_accounts SET enabled = 1 WHERE user_id = " . $user_id;
        			$q = $this->_db->performQuery($sql, 'sql');
        		}
        	}
        }
    } //end _confirmUser
}
?>
