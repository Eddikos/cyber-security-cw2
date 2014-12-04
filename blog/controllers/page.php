<?php

class Page extends Controller {

	function display($f3) {
		$pagename = $f3->get('PARAMS.3');
		$page = $this->Model->Pages->fetch($pagename);

		// checks first if the page does even exists, otherwise it would create the requested page on URL enter
		if ($page === false){
	        \StatusMessage::add('Invalid page name','danger');
			return $f3->reroute('/');
		}

		$pagetitle = ucfirst(str_replace("_"," ",str_replace(".html","",$pagename)));
		$f3->set('pagetitle',$pagetitle);
		$f3->set('page',$page);
	}

}

?>
