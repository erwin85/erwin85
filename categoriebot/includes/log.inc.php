<?php
if (!empty($_SERVER['QUERY_STRING']))
{
    if (!get_magic_quotes_gpc())
    {
    	$type = mysql_real_escape_string($_GET['type']);
    	$user = mysql_real_escape_string($_GET['user']);
    }
    else
    {
        $type = $_GET['type'];
    	$user = $_GET['user'];
    }
    
    $id = intval($_GET['id']);
    $limit = intval($_GET['limit']);
	
	if (!empty($limit))
	{
		if ($limit>500) $limit=500;
		if ($limit<1) $limit=1;
	}
	else
	{
		$limit = 100;
	}
	
	//A limit like LIMIT 10, 100
	$offset = intval($_GET['offset']);
	if (!empty($offset))
	{
		if ($offset<1) $offset=0;
	}
	else
	{
		$offset = 0;
	}
	
    
    if(!empty($id))
    {
        $sql = "SELECT log_type, log_timestamp, log_user, log_title, log_comment, log_params, user_name FROM u_erwin85.logging";
        $sql .= " LEFT JOIN u_erwin85.user_accounts ON log_user = user_id WHERE log_id = " . $id;
    }
    elseif(!empty($type))
    {
        if($type == 'move')
        {
            $sql = "SELECT log_type, log_timestamp, log_user, log_title, log_comment, log_params, user_name FROM u_erwin85.logging";
            $sql .= " LEFT JOIN u_erwin85.user_accounts ON log_user = user_id WHERE log_type = 'move'";
        }
        else
        {
            $sql = "SELECT log_type, log_timestamp, log_user, log_title, log_comment, log_params, user_name FROM u_erwin85.logging";
            $sql .= " LEFT JOIN u_erwin85.user_accounts ON log_user = user_id WHERE log_type = 'move'";
        }
        
        if(!empty($user))
        {
            $sql .= " AND user_name = '" . $user . "'";
        }
    }
    elseif(!empty($user))
    {
        $sql = "SELECT log_type, log_timestamp, log_user, log_title, log_comment, log_params, user_name FROM u_erwin85.logging";
        $sql .= " LEFT JOIN u_erwin85.user_accounts ON log_user = user_id WHERE user_name = '" . $user . "'";
    }
    else
    {
        $sql = "SELECT log_type, log_timestamp, log_user, log_title, log_comment, log_params, user_name FROM u_erwin85.logging";
        $sql .= " LEFT JOIN u_erwin85.user_accounts ON log_user = user_id";
    }
}
else
{
    $offset = 0;
    $limit = 100;
    $sql = "SELECT log_type, log_timestamp, log_user, log_title, log_comment, log_params, user_name FROM u_erwin85.logging";
    $sql .= " LEFT JOIN u_erwin85.user_accounts ON log_user = user_id";
}

$sql .= " ORDER BY log_timestamp DESC LIMIT " . $offset . ", " . $limit;
$q = $db->performUserQuery($sql);
?>
