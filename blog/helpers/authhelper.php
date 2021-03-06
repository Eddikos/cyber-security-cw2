<?php

	class AuthHelper {

		/** Construct a new Auth helper */
		public function __construct($controller) {
			$this->controller = $controller;
		}

		/** Attempt to resume a previously logged in session if one exists */
		public function resume() {
			$f3=Base::instance();				

			//Ignore if already running session	
			if($f3->exists('SESSION.user.id')){
				$userSession = $f3->get('SESSION.user.id');
				$user = $this->controller->db->query("SELECT * FROM users WHERE session = '$userSession'");
				if(!empty($user[0])){
					return;
				} else {
					$f3->clear('SESSION');
				}
				
			} 

			//Log user back in from cookie
			if($f3->exists('COOKIE.RobPress_User')) {
				//$user = unserialize(base64_decode($f3->get('COOKIE.RobPress_User')));
				$userCookie = $f3->get('COOKIE.RobPress_User');
				//$user = $this->controller->Model->users->fetch(array('cookie' => $userCookie));
				$user = $this->controller->db->query("SELECT * FROM users WHERE cookie = '$userCookie'");
				//var_dump($user);
				if(!empty($user[0])){
					$this->forceLogin($user[0]);
				}
			}
		}		

		/** Look up user by username and password and log them in */
		public function login($username,$password) {
			$f3=Base::instance();
			//Start the instance of the F3 Encryption
			$bEncrypt = \Bcrypt::instance();

			$db = $this->controller->db;
			
			// Previous code
			//$results = $db->query("SELECT * FROM `users` WHERE `username`='$username' AND `password`='$password'");
			
			// Using FatFree syntax, separate the variables from the actual query
			$f3->set('username', $username);
			$f3->set('password', $password);
			$result = $this->controller->Model->Users->fetch(array('username' => $username));

			// Another way to prevent SQL injection, but it still uses Query to database
			// $results = $db->connection->exec("SELECT * FROM `users` WHERE `username`= :username ",
			//									array(':username'=>$f3->get('username')));		
			if ($result){
				$result = $result->cast();	
			}
			// Verify the encryption first, and only then allow to login
			if (!empty($result)) {	
					if($bEncrypt->verify($f3->get('password'), $result['password'])===true) {
					    $user = $result; 
					    $this->setupSession($user);
					    return $this->forceLogin($user);
					} else {
						
					}
				
			} 
			return false;
		}

		/** Log user out of system */
		public function logout() {
			$f3=Base::instance();							

			//Kill the session
			//session_destroy();
			$f3->clear('SESSION');

			//Kill the cookie
			setcookie('RobPress_User','',time()-3600,'/');
		}

		/** Set up the session for the current user */
		public function setupSession($user) {
			$f3=Base::instance();
			//Remove previous session
			//session_destroy();
			$f3->clear('SESSION');
			$user_id = $user['id'];

			//Setup new session
			// Using a unique ID and hashing it 
			$session = uniqid(rand(),true);
			session_id(md5($session));
			$update = $this->controller->db->query("UPDATE users SET session = '$session' WHERE id = $user_id");   

			//Setup cookie for storing user details and for relogging in
			//setcookie('RobPress_User',base64_encode($user),time()+3600*24*30,'/');
			//Setup cookie for storing user details and for relogging in
			//setcookie('RobPress_User',base64_encode(serialize($user)),time()+3600*24*30,'/');
			
		   	$cookie = uniqid(rand(),true);
		   	//store the cookie in the database
		   	$update = $this->controller->db->query("UPDATE users SET cookie = '$cookie' WHERE id = $user_id");   
		   	setcookie('RobPress_User',$cookie,time()+3600*24*30,'/');
			//And begin!
			new Session();
		}

		/** Not used anywhere in the code, for debugging only */
		public function specialLogin($username) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3 = Base::instance();
			$user = $this->controller->Model->Users->fetch(array('username' => $username));
			$array = $user->cast();
			return $this->forceLogin($array);
		}

		/** Force a user to log in and set up their details */
		public function forceLogin($user) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3=Base::instance();						
			$f3->set('SESSION.user',$user);
			return $user;
		}

		/** Get information about the current user */
		public function user($element=null) {
			$f3=Base::instance();
			if(!$f3->exists('SESSION.user')) { return false; }
			if(empty($element)) { return $f3->get('SESSION.user'); }
			else { return $f3->get('SESSION.user.'.$element); }
		}

	}

?>
