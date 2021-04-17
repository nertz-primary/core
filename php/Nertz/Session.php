<?php
class Nertz_Session
{
	var $sess_save_path;
	var $sess_session_name;
	var $SID;
	var $db;
	var $IP;
	var $max_session_idle_time;
	function Nertz_Session()
	{
		global $tp, $max_session_idle_time, $db, $log;
		$this->db  = & $db;
		$this->log = & $log;
		$this->IP  = getenv("REMOTE_ADDR");
		ini_set("session.name","SID");
		$this->max_session_idle_time = 60*30; 
		ini_set( "session.gc_maxlifetime", $this->max_session_idle_time );
		ini_set( "session.use_cookies", 1 );
		ini_set( "session.use_trans_sid", Nertz::is_bot() ? 0 : 1 );
		/*session_set_save_handler(
		array( &$this, "_ses_open_handler" ),
		array( &$this, "_ses_close_handler" ),
		array( &$this, "_ses_read_handler" ),
		array( &$this, "_ses_write_handler" ),
		array( &$this, "_ses_destroy_handler" ),
		array( &$this, "_ses_gc_handler" )
		);*/
		//session_cache_limiter('must-revalidate');
		session_start();
	}
	function set_value( $name, $value )
	{
		global $_SESSION;
		$_SESSION[$name] = $value;
	}
	function get_value( $name, $default = null )
	{
		global $_SESSION;
		return  isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
	}
	function unset_value($name)
	{
		global $_SESSION;
		unset($_SESSION[$name]);
	}
	function has_value( $name)
	{
		return isset($_SESSION[$name]);
	}
	function write()
	{
		session_write_close();
	}
	///////////////////////////////////////////////////////////
	// ���������� ������� - �� ������������ !!!				 //
	///////////////////////////////////////////////////////////
	// ���������� ��� �������� ������
	function _ses_open_handler ($save_path, $session_name)
	{
		$this->sess_save_path    = $save_path;
		$this->sess_session_name = $session_name;
		return true;
	}
	// ���������� ��� �������� ������
	function _ses_close_handler()
	{
		return true;
	}
	// ���������� ������ ������ ����� �� ��
	function _ses_read_handler ($id)
	{
		$this->SID = $id;
		$_SESSION['user_ip'] = $this->IP;
		$sql="SELECT session_vars FROM ?_session WHERE session_id = ? AND user_ip= ? AND last_time > ?";
		$session_vars = $this->db->getOne($sql, array($id, $this->IP, (time()-$this->max_session_idle_time)));
		return $session_vars;
	}
	// ���������� ������ ������ ����� � ��
	function _ses_write_handler ($id, $sess_data)
	{
		global $_SESSION;

		$ind = $this->db->getOne("SELECT ind FROM ?_session WHERE session_id = ?", array($id));
		if($ind)
		{
			$sql = "UPDATE ?_session SET last_time  = ?, counter = counter+1, session_vars = ? WHERE ind = ?";
			return $this->db->sql_query($sql, array(time(), $sess_data, $ind));
		}
		else
		{
			$sql="INSERT INTO ?_session SET session_id = ?, start_time = ?, last_time = ?, user_ip= ?, counter=0, session_vars= ? ;";
			return $this->db->sql_query($sql, array($id, time(), time(), $_SESSION['user_ip'], $sess_data ));
		}
	}
	// ���������� �������� ������
	function _ses_destroy_handler ($id)
	{
		return $this->db->sql_query("DELETE FROM ?_session WHERE session_id = ?", array($id));
	}
	// ���������� ����������� ������ ������
	function _ses_gc_handler ($maxlifetime)
	{
		return $this->db->sql_query("DELETE FROM ?_session WHERE last_time < ".(time()-$this->max_session_idle_time)."");
	}

}