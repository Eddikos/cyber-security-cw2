<?php

/** Prepare timestamp for MySQL insertion */
function mydate($timestamp=0) {
	if(empty($timestamp)) { $timestamp = time(); }
	if(!is_numeric($timestamp)) { $timestamp = strtotime($timestamp); }
	return date("Y-m-d H:i:s",$timestamp);
}

/** Prepare timestamp for nice display */
function nicedate($timestamp=0) {
	if(empty($timestamp)) { $timestamp = time(); }
	if(!is_numeric($timestamp)) { $timestamp = strtotime($timestamp); }
	return date("l jS \of F Y H:i:s",$timestamp);
}

/** HTML escape content */
function h($text) {
	return htmlspecialchars($text);
}

// a function to sanitize the input Name/file/
function sanitize_input($string, $force_lowercase = true, $anal = false) {
    $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
                   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                   "â€”", "â€“", ",", "<", ".", ">", "/", "?");
    $clean = trim(str_replace($strip, "", strip_tags($string)));
    $clean = preg_replace('/\s+/', "-", $clean);
    $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
    return ($force_lowercase) ?
        (function_exists('mb_strtolower')) ?
            mb_strtolower($clean, 'UTF-8') :
            strtolower($clean) :
        $clean;
}

?>
