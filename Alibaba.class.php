<?php
// Alibaba
// PHP authentication library
// by Ben Crowder

include_once "alibaba_config.php";

class Alibaba {
	private static $app_name = '';
	private static $database_name = '';
	private static $database_host = '';
	private static $database_username = '';
	private static $database_password = '';
	private static $user_table_name = '';
	private static $username_field = '';
	private static $password_field = '';
	private static $cookie_expiration = '';
	private static $hash_function = '';
	private static $login_page_url = '';

	// Initialization with configuration and session start (if a previous session exist, recall it
	public static function AlibabaInit($params) {
		self::$app_name = $params['app_name'];
		self::$database_name = $params['database_name'];
		self::$database_host = $params['database_host'];
		self::$database_username = $params['database_username'];
		self::$database_password = $params['database_password'];
		self::$user_table_name = $params['user_table_name'];
		self::$username_field = $params['username_field'];
		self::$password_field = $params['password_field'];
		self::$cookie_expiration = $params['cookie_expiration'];
		self::$hash_function = $params['hash_function'];
		self::$login_page_url = $params['login_page_url'];
		
		if(session_id() == '' || !isset($_SESSION)) {
			if (isset($_COOKIE["alibaba_" . self::$app_name . "_session"]))
				session_id ($_COOKIE["alibaba_" . self::$app_name . "_session"]);
			session_start();
		}	
	}

	//if the user is not autheticated, force him to login page
	public static function forceAuthentication() {
		if (!self::authenticated()) {		
		self::redirectToLogin();
		}
	}

	//is the user authenticated?
	public static function authenticated() {
		if (isset($_COOKIE["alibaba_" . self::$app_name . "_username"]) &&  self::sessionCheck()) {
			return true;
		} 
		else {
			return false;
		}
	}

	// Perform check on the database
	public static function loginAction($username, $password) {
		// Connect to the database
		$db = self::db_connect();

		// Hash the password with the correct function
		$password = self::hashpass($password);

		// Check the database
		$query = "SELECT * FROM " . mysql_real_escape_string(self::$user_table_name) . " WHERE " . mysql_real_escape_string(self::$username_field) . "='" . mysql_real_escape_string($username) . "' AND " . mysql_real_escape_string(self::$password_field) . "='" . mysql_real_escape_string($password) . "'";

		$result = mysql_query($query) or die("Couldn't run: $query");

		if (mysql_numrows($result)) { 
			// We're logged in
			$logged_in = true;
		} else {
			// Login failed
			$logged_in = false;
		}
		self::db_close($db);
		return $logged_in;
	}	

	public static function login($username, $password) {
		$logged_in=self::loginAction($username, $password);
		if ($logged_in) { 
			$_SESSION['address']=$_SERVER['REMOTE_ADDR'];
			$_SESSION['username']=$username;
			//set the cookie, 86400=24*24*60
			setcookie("alibaba_" . self::$app_name . "_username", $username, time() + 86400 * self::$cookie_expiration, "/");
			setcookie("alibaba_" . self::$app_name . "_session", session_id(), time() + 86400 * self::$cookie_expiration, "/");
		} else {
			// unset session and cookie
			$_SESSION = array();
			session_destroy();
			setcookie("alibaba_" . self::$app_name . "_username", "", time() - 3600, "/");
			setcookie("alibaba_" . self::$app_name . "_session", "", time() - 3600, "/");
		}
		return $logged_in;
	}

	private static function pswCheck($password){
		return self::loginAction(self::getUsername(), $password);
	}
	
	public static function redirectToLogin($login = '') {
		if ($login == '') { $login = self::$login_page_url; }
		$locstr = "Location: $login";
		header($locstr);
	}

	public static function getUsername() {
		if (self::authenticated())
		return $_COOKIE["alibaba_" . self::$app_name . "_username"];
		return false;
	}

	public static function logout($url = '') {
			setcookie("alibaba_" . self::$app_name . "_username", "", time() - 3600, "/");			
			$_SESSION = array();
			session_destroy();
			self::redirectToLogin($url);
	}

	// Service functions

	private static function sessionCheck(){
		if(session_id() == '' || !isset($_SESSION) || !isset($_SESSION['address'])) return false;
		return ($_SESSION['address']==$_SERVER['REMOTE_ADDR'] &&$_SESSION['username']==$_COOKIE["alibaba_" . self::$app_name . "_username"] );
	}

	private static function hashpass($password) {
		switch(self::$hash_function) {
			case "md5": $password = md5($password); break;
			case "sha1": $password = sha1($password); break;
			case "md5sha1" : $password = md5(sha1($password)); break;
			case "sha1md5" : $password = sha1(md5($password)); break;
		}
		return $password;
	}
	
	private static function db_connect() {
		$conn = mysql_connect(self::$database_host, self::$database_username, self::$database_password);
		if (!$conn) { echo "Error connecting to database.\n"; }

		@mysql_select_db(self::$database_name, $conn) or die("Unable to select database.");
		
		return $conn;
	}

	private static function db_close($conn) {
		mysql_close($conn);
	}
}
?>
