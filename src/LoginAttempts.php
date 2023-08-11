<?php
namespace True;

/**
 * Login Attempts class
 *
 * @package TrueAdmin 5
 * @author Daniel Baldwin
 */
class LoginAttempts
{
	private $DB = null;
	private $ip = null;
	var $fields = ["id", "lockout_time", "ip", "count"];
	
	public function __construct($DB=null)
	{
		$this->DB = $DB;
		$this->ip = $_SERVER['REMOTE_ADDR'];
	}
	
	public function __get($key)
	{
		return $this->DB->get("SELECT $key FROM admin_login_attempts WHERE ip=?", array($this->ip), 'value');
	}
	
	function set(array $args)
	{
		if($this->DB->get("SELECT ip FROM admin_login_attempts WHERE ip=?", [$this->ip], 'value') != false)
			$this->DB->execute("UPDATE admin_login_attempts SET lockout_time=?, count=count+1 WHERE ip=?", [$args['lockout_time'], $this->ip]);
		else
			$this->DB->set('admin_login_attempts', ['lockout_time'=>$args['lockout_time'], 'count'=>1, 'ip'=>$this->ip]);
	}
	
	function setIp($ip)
	{
		$this->ip = $ip;
	}
	
	public function create()
	{
		$this->DB->query('CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
		`id` int(11) NOT NULL auto_increment,
		`lockout_time` varchar(50) default NULL,
		`ip` varchar(30) default NULL,
		`count` int(11) default NULL,
		PRIMARY KEY  (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8');
	}
}
	
?>