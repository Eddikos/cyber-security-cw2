<?php

namespace Admin;

class Page extends AdminController {

	public function index($f3) {
		$pages = $this->Model->Pages->fetchAll();
		//$dude = 4;
		$f3->set('pages',$pages);
		//$f3->set('dude', $dude );
		//var_dump($f3->get('dude'));
		//var_dump($f3->dbtype());
		//var_dump($this->get('csrf'));
	}

	public function add($f3) {
		if($this->request->is('post')) {
			// clean before passing
			$pagename = trim($this->request->data['title']);

			// Sanitize the input in prder to have clean URLs
			$pagename = sanitize_input($pagename);			

			if ($pagename == ''){
				\StatusMessage::add('Empty Page name is not accepted','danger');
				return $f3->reroute('/admin/page/');
			} else {
				$this->Model->Pages->create($pagename);
				\StatusMessage::add('Page created succesfully','success');
				return $f3->reroute('/admin/page/edit/' . $pagename);
			}
		}
	}

	public function edit($f3) {
		$pagename = $f3->get('PARAMS.3');
		if ($this->request->is('post')) {
			$pages = $this->Model->Pages;
			$pages->title = $pagename;
			$pages->content = $this->request->data['content'];
			$pages->save();

			\StatusMessage::add('Page updated succesfully','success');
			return $f3->reroute('/admin/page');
		}
	
		$pagetitle = ucfirst(str_replace("_"," ",str_ireplace(".html","",$pagename)));	
		$page = $this->Model->Pages->fetch($pagename);
		$f3->set('pagetitle',$pagetitle);
		$f3->set('page',$page);
	}

	public function delete($f3) {
		$pagename = $f3->get('PARAMS.3');
		$this->Model->Pages->delete($pagename);	
		\StatusMessage::add('Page deleted succesfully','success');
		return $f3->reroute('/admin/page');	
	}
}

?>
