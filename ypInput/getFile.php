<?php
	error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

	unset($file);
	if(isset($_GET['file'])) {
		$file = $_GET['file'];
	}

	header('Content-type: application/txt');
	if (isset($file) && file_exists($file)) {
		echo file_get_contents($file);
	}
?>