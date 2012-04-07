<?php
class MysqlClass
{
	protected $hostname;
	protected $username;
	protected $password;
	protected $port;
	protected $database;
	protected $connId = FALSE;
	protected static $dbId = 1;
	protected $config;
	
	public function __construct() {
		// 获取mysql的配置
		$this->config = & getConfig ( 'mysql' );
		
		$this->connect ();
	}
	
	protected function connect() {
		do {
			$this->hostname = $this->config ['hostname' . self::$dbId];
			$this->port = $this->config ['port' . self::$dbId];
			$this->username = $this->config ['username' . self::$dbId];
			$this->password = $this->config ['password' . self::$dbId];
			$this->database = $this->config ['database' . self::$dbId];
			
			$this->connId = mysql_connect ( $this->hostname . ':' . $this->port, $this->username, $this->password, TRUE ) or die ( mysql_error () );
		
		} while ( $this->connId === FALSE && ++ self::$dbId <= $this->config ['maxServers'] );
		
		// 当数据库连接不成功，可以考虑第二台服务器
		if ($this->connId === FALSE) {
			// 触发错误
			echo 'database errors,that\'s not your fault';
		}
	}
	
	public function reconnect() {
		if (mysql_ping ( $this->connId ) === FALSE) {
			$this->connId = FALSE;
		}
	}
	/*
	 * mysql_select_db
	 * 
	 */
	public function dbSelect() {
		return @mysql_select_db ( $this->database, $this->connId );
	}
	
	/*
	 * 
	 * 
	 */
	public function select($table){
		
	}
	
	/*
	 * 
	 */
	public function update(){
		
	}
	
	/*
	 * 
	 */
	public function insert(){
		
	}
	
	/*
	 * 
	 */
	private function query($result){
		
	}
	
	/*
	 * execute
	 * 
	 */
	public function execute($sql){
		
	}
	
	// test
	public function getConnId() {
		return $this->connId;
	}
	
	// just for testing
	public function getSort() {
		$sql = 'select * from elog_sort';
		$result = mysql_query ( $sql, $this->connId );
		$tmp = array ();
		$data = array ();
		while ( $tmp = mysql_fetch_assoc ( $result ) ) {
			$data [] = $tmp;
		}
		return $data;
	}
}