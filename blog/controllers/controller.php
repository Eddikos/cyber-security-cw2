<?php

class Controller {

	protected $layout = 'default';

	public function __construct() {
		$f3=Base::instance();
		$this->f3 = $f3;

		// Connect to the database
		$this->db = new Database();
		$this->Model = new Model($this);

		//Load helpers
		$helpers = array('Auth');
		foreach($helpers as $helper) {
			$helperclass = $helper . "Helper";
			$this->$helper = new $helperclass($this);
		}
	}

	public function beforeRoute($f3) {
		$this->request = new Request();

		//Check user
		$this->Auth->resume();

		//Load settings
		$settings = $this->Model->Settings->fetchList(array('key','value'));
		$settings['base'] = $f3->get('BASE');

		$settings['path'] = $f3->get('PATH');
		$this->Settings = $settings;
		$f3->set('site',$settings);

		// Fetch all available parameters from URL and Clean them up
		// And then return back to the array of PARAMS
		$parameters = $f3->clean($f3->get('PARAMS'));
		$f3->set('PARAMS', $parameters);
		
		//Extract request data
		extract($this->request->data);

		//Process before route code
		if(isset($beforeCode)) {
			$f3->process($beforeCode);
		}

		// First check whether there are any GET or POST methods being send to the page, it will mean that there is a form being submitted
		if ($this->request->is('post')){
			// If there is, check if it there is Token Input being set up and it is equal to the SESSION one
			if (isset($this->request->data['formToken']) && $_SESSION['formToken'] == $this->request->data['formToken']){
				// Nullify the SESSION, UNSET function from the F3 could be used
				//$_SESSION['formToken'] = null;

				// Because we are within form it means there are some parameters being passed
				// Clean them before actual use
				$this->request->data = $f3->clean($this->request->data,'ul,b,i,u,s,sub,sup,body,strong,em,a,ol,li,div,p,blockqoute,div,span,img,table,tr,td,tbody,thead,iframe');

			} else {
				//Reroute to the Main page if CSRF is detected
				\StatusMessage::add("You trying to access the site from other place, bad idea", 'danger');
				return $f3->reroute('/');
			}
		}
		
	}

	public function afterRoute($f3) {	
		//Set page options
		$f3->set('title',isset($this->title) ? $this->title : get_class($this));

		//Prepare default menu	
		$f3->set('menu',$this->defaultMenu());

		//Setup user
		$f3->set('user',$this->Auth->user());

		//Check for admin
		$admin = false;
		if(stripos($f3->get('PARAMS.0'),'admin') !== false) { $admin = true; }

		//Identify action
		$controller = get_class($this);
		if($f3->exists('PARAMS.action')) {
			$action = $f3->get('PARAMS.action');	
		} else {
			$action = 'index';
		}

		//Handle admin actions
		if ($admin) {
			$controller = str_ireplace("Admin\\","",$controller);
			$action = "admin_$action";
		}

		//Handle errors
		if ($controller == 'Error') {
			$action = $f3->get('ERROR.code');
		}

		//Handle custom view
		if(isset($this->action)) {
			$action = $this->action;
		}

		//Extract request data
		extract($this->request->data);

		//Generate content		
		$content = View::instance()->render("$controller/$action.htm");
		$f3->set('content',$content);

		//Process before route code
		if(isset($afterCode)) {
			$f3->process($afterCode);
		}

		//Render template
		echo View::instance()->render($this->layout . '.htm');
	}

	public function defaultMenu() {
		$menu = array(
			array('label' => 'Search', 'link' => 'blog/search'),
			array('label' => 'Contact', 'link' => 'contact'),
		);

		//Load pages
		$pages = $this->Model->Pages->fetchAll();
		foreach($pages as $pagetitle=>$page) {
			$pagename = str_ireplace(".html","",$page);
			$menu[] = array('label' => $pagetitle, 'link' => 'page/display/' . $pagename);
		}

		//Add admin menu items
		if ($this->Auth->user('level') > 1) {
			$menu[] = array('label' => 'Admin', 'link' => 'admin');
		}

		return $menu;
	}

}

?>
