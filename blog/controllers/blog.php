<?php
class Blog extends Controller {
	
	public function index($f3) {	
		if ($f3->exists('PARAMS.3')) {
			$categoryid = $f3->get('PARAMS.3');
			$category = $this->Model->Categories->fetch($categoryid);
			$postlist = array_values($this->Model->Post_Categories->fetchList(array('id','post_id'),array('category_id' => $categoryid)));
			$posts = $this->Model->Posts->fetchAll(array('id' => $postlist, 'published' => 'IS NOT NULL'),array('order' => 'published DESC'));
			$f3->set('category',$category);
		} else {
			$posts = $this->Model->Posts->fetchPublished();
		}

		$blogs = $this->Model->map($posts,'user_id','Users');
		$blogs = $this->Model->map($posts,array('post_id','Post_Categories','category_id'),'Categories',false,$blogs);
		$f3->set('blogs',$blogs);
	}

	public function view($f3) {
		$id = $f3->get('PARAMS.3');
		if(empty($id)) {
			return $f3->reroute('/');
		}
		$post = $this->Model->Posts->fetchById($id);

		// Check whehter the Page requested exists or not
		if($post) {
			$blog = $this->Model->map($post,'user_id','Users');
			$blog = $this->Model->map($post,array('post_id','Post_Categories','category_id'),'Categories',false,$blog);

			$comments = $this->Model->Comments->fetchAll(array('blog_id' => $id));
			$allcomments = $this->Model->map($comments,'user_id','Users');

			$f3->set('comments',$allcomments);
			$f3->set('blog',$blog);	
		} else {
			\StatusMessage::add('Invalid View ID being passed','danger');
			return $f3->reroute('/');
		}			
	}

	// Comment out the unnecessary command which User might access to EMPTY the whole website
	
	// public function reset($f3) {
	// 	$allposts = $this->Model->Posts->fetchAll();
	// 	$allcategories = $this->Model->Categories->fetchAll();
	// 	$allcomments = $this->Model->Comments->fetchAll();
	// 	$allmaps = $this->Model->Post_Categories->fetchAll();
	// 	foreach($allposts as $post) $post->erase();
	// 	foreach($allcategories as $cat) $cat->erase();
	// 	foreach($allcomments as $com) $com->erase();
	// 	foreach($allmaps as $map) $map->erase();
	// 	StatusMessage::add('Blog has been reset');
	// 	return $f3->reroute('/');
	// }

	public function comment($f3) {
		$id = $f3->get('PARAMS.3');
		$post = $this->Model->Posts->fetchById($id);
		if($this->request->is('post')) {
			if (trim($this->request->data['message']) == ''){
				StatusMessage::add('You are trying to submit an empty comment','danger');
				return $f3->reroute('/blog/view/' . $id);
			} else {
				$comment = $this->Model->Comments;
				$comment->copyfrom('POST');
				// Not to use HIDDEN Field anymore
				$comment->user_id = $this->Auth->user("id");
				$comment->blog_id = $id;
				$comment->created = mydate();
				$comment->message = $this->request->data['message'];

				//Moderation of comments
				if (!empty($this->Settings['moderate']) && $this->Auth->user('level') < 2) {
					$comment->moderated = 0;
				} else {
					$comment->moderated = 1;
				}

				//Default subject
				if(empty($this->request->data['subject'])) {
					$comment->subject = 'RE: ' . $post->title;
				} else {
					$comment->subject = $this->request->data['subject'];
				}

				$comment->save();

				//Redirect
				if($comment->moderated == 0) {
					StatusMessage::add('Your comment has been submitted for moderation and will appear once it has been approved','success');
				} else {
					StatusMessage::add('Your comment has been posted','success');
				}
				return $f3->reroute('/blog/view/' . $id);
			}
		}
	}

	public function moderate($f3) {
		list($id,$option) = explode("/",$f3->get('PARAMS.3'));
		$comments = $this->Model->Comments;
		$comment = $comments->fetch($id);

		$post_id = $comment->blog_id;
		//Approve
		if ($option == 1) {
			$comment->moderated = 1;
			$comment->save();
		} else {
		//Deny
			$comment->erase();
		}
		StatusMessage::add('The comment has been moderated');
		$f3->reroute('/blog/view/' . $comment->blog_id);
	}

	public function search($f3) {
		if($this->request->is('post')) {
			extract($this->request->data);
			$f3->set('search',$search);
			
			if (trim($search) == ''){
					StatusMessage::add('Empty field is being submitted/searched for','danger');
			} else {
				$search = str_replace("*","%",$search); //Allow * as wildcard
				// Prepare the Taken from http://fatfreeframework.com/sql-mapper
				$searchQuery = '%'.$search.'%';
				// Make a query using predefined method from database
				$posts = $this->Model->Posts->find(array('title LIKE ? OR content LIKE ?', $searchQuery, $searchQuery));
				
				if(empty($posts)) {
					StatusMessage::add('No search results found for ' . $search); 
					return $f3->reroute('/blog/search');
				}

				//Load associated data
				$blogs = $this->Model->map($posts,'user_id','Users');
				$blogs = $this->Model->map($posts,array('post_id','Post_Categories','category_id'),'Categories',false,$blogs);

				$f3->set('blogs',$blogs);
				$this->action = 'results';
			}	
		}
	}
}
?>
