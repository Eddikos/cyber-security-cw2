<?php

	namespace Admin;

	class Category extends AdminController {

		public function index($f3) {
			$categories = $this->Model->Categories->fetchAll();
			$counts = array();
			foreach($categories as $category) {
				$counts[$category->id] = $this->Model->Post_Categories->fetchCount(array('category_id' => $category->id));
			}
			$f3->set('categories',$categories);
			$f3->set('counts',$counts);
		}

		public function add($f3) {
			if($this->request->is('post')) {
				$category = $this->Model->Categories;
				
				$categoryName = trim(strtolower($this->request->data['title']));

				if ($categoryName == ''){
					\StatusMessage::add('Empty Category name is not accepted','danger');
					return $f3->reroute('/admin/category');
				} else {
					// clean before passing
					$category->title = $this->request->data['title'];
					$category->save();

					\StatusMessage::add('Category added succesfully','success');
					return $f3->reroute('/admin/category');
				}
			}
		}

		public function delete($f3) {
			$categoryid = $f3->get('PARAMS.3');
			$category = $this->Model->Categories->fetchById($categoryid);
			$category->erase();

			//Delete links		
			$links = $this->Model->Post_Categories->fetchAll(array('category_id' => $categoryid));
			foreach($links as $link) { $link->erase(); } 
	
			\StatusMessage::add('Category deleted succesfully','success');
			return $f3->reroute('/admin/category');
		}

		public function edit($f3) {
			$categoryid = $f3->get('PARAMS.3');
			$category = $this->Model->Categories->fetchById($categoryid);

			// Check first whether the requested Category exists in Database
			if ($category){
				if($this->request->is('post')) {
					// clean before passing
					$category->title = $this->request->data['title'];
					$category->save();
					\StatusMessage::add('Category updated succesfully','success');
					return $f3->reroute('/admin/category');
				}
				$f3->set('category',$category);
			} else {
				\StatusMessage::add('Invalid Category ID being passed','danger');
				return $f3->reroute('/admin/category');
			}
		}


	}

?>
