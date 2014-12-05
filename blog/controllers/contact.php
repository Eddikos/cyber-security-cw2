<?php

class Contact extends Controller {

	public function index($f3) {
		if($this->request->is('post')) {
			list($from, $subject, $message) = array(trim($this->request->data['from']), 
									trim($this->request->data['subject']), 
									trim($this->request->data['message']));
			
			if ($from == '' || $subject == '' || $message == ''){
				StatusMessage::add('All fields needs to be filled in!','danger');
			} elseif(!filter_var($from, FILTER_VALIDATE_EMAIL)){
				StatusMessage::add('Email is not valid','danger');
			} elseif(strlen($message) <= 5 || strlen($subject) <= 5){
				StatusMessage::add('Subject and Message has to be at least 5 characters long','danger');
			} else {
				$from = "From: $from";
				//$to = "root@localhost";
				mail($to,$subject,$message,$from);

				StatusMessage::add('Thank you for contacting us');
				return $f3->reroute('/');
			}
		}	
	}

}

?>
