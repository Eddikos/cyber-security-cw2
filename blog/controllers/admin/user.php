<?php

namespace Admin;

class User extends AdminController {

	public function index($f3) {
		$users = $this->Model->Users->fetchAll();
		$f3->set('users',$users);
	}

	public function edit($f3) {	
		//Start the instance of the F3 Encryption
		$bEncrypt = \Bcrypt::instance();

		$id = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetchById($id);

		// First check whether the requested User exists at all
		if ($u){
			$oldPassword = $u->password;
		
			if($this->request->is('post')) {
				$u->copyfrom('POST');

				// Check whether entered new password is the same as the old one, or wasn't changed at all, 
				// It is done to avoid Double Hashing
				if ($this->request->data['password'] !== $oldPassword && $bEncrypt->hash($this->request->data['password'],null, 10) !== $oldPassword) {
				    $u->password = $bEncrypt->hash($u->password, null, 10);
				} else { 
				    $u->password = $oldPassword;
				}

				$u->save();
				\StatusMessage::add('User updated succesfully','success');
				return $f3->reroute('/admin/user');
			}			
			$_POST = $u->cast();
			$f3->set('u',$u);
		} else {
			\StatusMessage::add('Invalid user ID being requested','danger');
			return $f3->reroute('/admin/user');
		}

		// Store previous password to be used for checking later on
		
	}

	public function delete($f3) {
		$id = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($id);

		if($id == $this->Auth->user('id')) {
			\StatusMessage::add('You cannot remove yourself','danger');
			return $f3->reroute('/admin/user');
		}

		//Remove all posts and comments
		$posts = $this->Model->Posts->fetchAll(array('user_id' => $id));
		foreach($posts as $post) {
			$post_categories = $this->Model->Post_Categories->fetchAll(array('post_id' => $post->id));
			foreach($post_categories as $cat) {
				$cat->erase();
			}
			$post->erase();
		}
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $id));
		foreach($comments as $comment) {
			$comment->erase();
		}
		$u->erase();

		\StatusMessage::add('User has been removed','success');
		return $f3->reroute('/admin/user');
	}


}

?>
