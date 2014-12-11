<?php
class User extends Controller {
	
	public function view($f3) {
		$userid = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($userid);

		if($u){
			$articles = $this->Model->Posts->fetchAll(array('user_id' => $userid));
			$comments = $this->Model->Comments->fetchAll(array('user_id' => $userid));

			$f3->set('u',$u);
			$f3->set('articles',$articles);
			$f3->set('comments',$comments);
		} else {
			\StatusMessage::add('Invalid User ID being passed', 'danger');
			return $f3->reroute('/');
		}

		
	}

	public function add($f3) {
		//Start the instance of the F3 Encryption
		$bEncrypt = \Bcrypt::instance();
		if($this->request->is('post')) {
			extract($this->request->data);
			list($captcha, $username, $displayname, $email, $password, $password2) = array(trim($this->request->data['captcha']), 
									trim($this->request->data['username']), 
									trim($this->request->data['displayname']), 
									trim($this->request->data['email']), 
									trim($this->request->data['password']), 
									trim($this->request->data['password2']));

			if ($captcha == '' || $username == '' || $displayname == '' || $email == '' || $password == '' || $password2 == ''){
				StatusMessage::add('All fields needs to be filled in!','danger');
			} elseif(strlen($username) <= 3 || strlen($displayname) <= 3){
				StatusMessage::add('Names have to be more than 3 characters long','danger');
			} elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
				StatusMessage::add('Email is not valid','danger');
			} elseif(strlen($password) <= 3){
				StatusMessage::add('Password has to be at least 3 characters long','danger');
			} elseif($password !== $password2){
				StatusMessage::add('Passwords must match','danger');
			} elseif ($captcha == $_SESSION['captcha_code']){
				$check = $this->Model->Users->fetch(array('username' => $username));
				if (!empty($check)) {
					StatusMessage::add('User already exists','danger');
				} else {
					$user = $this->Model->Users;
					$user->copyfrom('POST');
					$user->created = mydate();
					$user->bio = '';
					$user->level = 1;
					if(empty($displayname)) {
						$user->displayname = $user->username;
					}

					// Encrypt the password before passing it to the database
					$salt = uniqid(rand(), true);
					$user->password = $bEncrypt->hash($user->password,$salt, 10);
					$user->save();	
					StatusMessage::add('Registration complete','success');
					return $f3->reroute('/user/login');
				}
			} else {
				StatusMessage::add('Invalid captcha','danger');
			}
			
		}
	}

	public function login($f3) {
		// Instanciate Audit Class for URl checking
		$audit = \Audit::instance();

		if ($this->request->is('post')) {
			list($username, $password, $captcha) = array($this->request->data['username'], $this->request->data['password'], $this->request->data['captcha']);
			if (trim($captcha) == ''){
					StatusMessage::add('You need to provide captcha as well','danger');
			} elseif ($captcha == $_SESSION['captcha_code']){
				if ($this->Auth->login($username,$password)) {
					StatusMessage::add('Logged in succesfully','success');
				

					// Trying to prevent Open Redirect 
					if(isset($_GET['from'])) {
						if ($audit->url($_GET['from'])){
							\StatusMessage::add('URL within a URl? Bad Idea!!','danger');
							return $f3->reroute('/');

							\StatusMessage::add('Profile updated succesfully','success');
							return $f3->reroute('/user/profile');
						} else {
							$f3->reroute($_GET['from']);
						}
					} else {
						$f3->reroute('/');	
					}


				} else {
					StatusMessage::add('Invalid username or password','danger');
				}
			} else {
				StatusMessage::add('Invalid captcha','danger');
			}
		}		
	}

	public function logout($f3) {
		$this->Auth->logout();
		StatusMessage::add('Logged out succesfully','success');
		$f3->reroute('/');	
	}


	public function profile($f3) {	
		//Start the instance of the F3 Encryption
		$bEncrypt = \Bcrypt::instance();

		$id = $this->Auth->user('id');
		extract($this->request->data);
		$u = $this->Model->Users->fetch($id);

		// Store previous password to be used for checking later on
		$oldPassword = $u->password;  //  was within the patch as well

		if($this->request->is('post')) {
			$u->copyfrom('POST');

			// Was within the latest patch 
			if(trim($u->password) == '') { $u->password = $oldPassword; }
			
			$u->displayname = $this->request->data['displayname'];


			//Handle avatar upload
			// List of allowed extensions to be uploaded
			$validExtensions = array(
				'png' => 'image/png',
				'jpeg' =>'image/jpeg',
			    'jpg' => 'image/jpeg',
			    'bmp' => 'image/bmp',
			    'gif' => 'image/gif'
			);


			if(isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] == false) {
			    $filename = basename($_FILES['avatar']['name']);
			    // Get the last extension of the file
			    $lastExt = (new SplFileInfo($filename))->getExtension();
			    
			    // Check whether last extension of the file is 
			    if(array_key_exists($lastExt, $validExtensions) === true && ($_FILES['avatar']['type']) === $validExtensions[$lastExt]){ 
			     	$url = File::Upload($_FILES['avatar']);
			     	$u->avatar = $url;
			    }else{
			     	\StatusMessage::add('Invalid file extension','danger');
			    }
			}



			// Check whether entered new password is the same as the old one, or wasn't changed at all, 
			// It is done to avoid Double Hashing
			 if (strlen($_POST['password']) !== 0) {
			    $salt = uniqid(rand(), true);
			    $u->password = $bEncrypt->hash($u->password, $salt, 10);
			   }else{
			    $u->password = $oldPassword;
			   }



			$u->save();
			\StatusMessage::add('Profile updated succesfully','success');
			return $f3->reroute('/user/profile');
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}
}
?>
