<?php

class File {

	public static function Upload($array) {
		$f3 = Base::instance();
		extract($array);
		$directory = getcwd() . '/uploads';
		$destination = $directory . '/' . $name;
		$webdest = '/uploads/' . $name;
		if (move_uploaded_file($tmp_name,$destination)) {
			// Change permissions of the file so uploaded file can be seen straight away
			chmod($destination, 0644);
			return $webdest;
		} else {
			return false;
		}
	}

}

?>
