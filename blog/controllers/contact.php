<?php

class Contact extends Controller {

	public function index($f3) {
		if($this->request->is('post')) {
			list($from, $subject, $message, $captcha) = array(trim($this->request->data['from']), 
									trim($this->request->data['subject']),
									trim($this->request->data['message']), 
									trim($this->request->data['captcha']));
			
			if ($from == '' || $subject == '' || $message == '' || $captcha == ''){
				StatusMessage::add('All fields needs to be filled in!','danger');
			} elseif(!filter_var($from, FILTER_VALIDATE_EMAIL)){
				StatusMessage::add('Email is not valid','danger');
			} elseif(strlen($message) <= 5 || strlen($subject) <= 5){
				StatusMessage::add('Subject and Message has to be at least 5 characters long','danger');
			} elseif ($captcha !== $_SESSION['captcha_code']){
				StatusMessage::add('Invalid captcha','danger');
			} else {
				$from = "From: $from";
				$site = $f3->get('site');
				$to = $site['email'];
				mail($to,$subject,$message,$from);

				StatusMessage::add('Thank you for contacting us');
				return $f3->reroute('/');
			}
		}	
	}

}

?>
