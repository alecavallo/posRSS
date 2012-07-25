<?php

class Connector {

	private $server;

	private $port;

	private $user;

	private $database;

	private $password;

	private $connection;


	function __construct($server="", $user="", $password="", $database="", $port=3306) {
		if (!empty($server) && !empty($user)) {
			$this->connect($server, $user, $password, $database, $port);
		}

	}

	function __destruct() {
		@mysql_close($this->connection);
	}

	public function connect($server="", $user="", $password="", $database="", $port=3306){
		if (!empty($server)) {
			$this->server=$server;
		}else{
			$server = $this->server;
		}

		if (!empty($user)) {
			$this->user=$user;
		}else{
			$user = $this->user;
		}

		if (!empty($password)) {
			$this->password = $password;
		}else{
			$password = $this->password;
		}

		if (!empty($port)) {
			$this->port=$port;
		}else{
			$port = $this->port;
		}

		if (!empty($this->connection) && ($this->server != $server || $this->user != $user || $this->port != $port)) {
			$this->disconnect();
		}
		$this->connection = mysql_connect($server.":".$port, $user, $password);
		mysql_set_charset('utf8',$this->connection);
		if (mysql_errno($this->connection)) {
			throw new Exception(mysql_error($this->connection), mysql_errno($this->connection));
		}

		if (!empty($database)) {
			$this->setDatabase($database);
		}
	}

	public function disconnect(){
		if (!empty($this->connection)) {
			mysql_close($this->connection);
			unset($this->connection);
		}
	}

	public function query($sql, $buffered=TRUE) {
		if ($buffered){
			$resultset = mysql_query($sql, $this->connection);
		}else {
			$resultset = mysql_unbuffered_query($sql, $this->connection);
		}

		if (mysql_errno($this->connection)) {
			$stacktrace = debug_print_backtrace();
			throw new Exception(mysql_error($this->connection)."\n {$stacktrace}\n\n"."QRY: {$sql}.", mysql_errno($this->connection));
		}

		return $resultset;
	}
	/**
	 * @return the $server
	 */
	public function getServer() {
		return $this->server;
	}

	/**
	 * @return the $port
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * @return the $user
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return the $database
	 */
	public function getDatabase() {
		return $this->database;
	}

	/**
	 * @return the $password
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @param $server the $server to set
	 */
	public function setServer($server) {
		$this->server = $server;
	}

	/**
	 * @param $port the $port to set
	 */
	public function setPort($port) {
		$this->port = $port;
	}

	/**
	 * @param $user the $user to set
	 */
	public function setUser($user) {
		$this->user = $user;
	}

	/**
	 * @param $database the $database to set
	 */
	public function setDatabase($database) {
		$this->database = $database;
		mysql_select_db($database);
		if (mysql_errno($this->connection)) {
			throw new Exception(mysql_error($this->connection), mysql_errno($this->connection));
		}

	}

	/**
	 * @param $password the $password to set
	 */
	public function setPassword($password) {
		$this->password = $password;
	}
	/**
	 * @return the $connection
	 */
	public function getConnection() {
		return $this->connection;
	}


}

?>