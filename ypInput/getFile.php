<?php
	error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

	// Use curl to retrieve remote files
	$imsUseCurl = true;

	unset($file);
	if(isset($_GET['file'])) {
		$file = $_GET['file'];
	}

	if (empty($file)) exit();

	$wholeURL    = wholeURLforTheExecutedFile();
//	$phpBaseName = strrright($wholeURL, '/');
//	$prefixURL   = strrleft($wholeURL, $phpBaseName);

//	echo $wholeURL . "\n";
//	echo $phpBaseName . "\n";
//	echo $prefixURL . "\n";

	if (strpos($file, '.rss') !== false) {
		$rss = true;
		header('Content-type: application/xml');
	}
	else {
		$rss = false;
		header('Content-type: application/txt');
	}

	if ($rss) {
		// Cannot be a remote file
		$rssContent = local_file_get_contents($file);

		// Substitute the strings

		// 1. Default settings
		$rssContent = str_replace('defOnline = 0;', 'defOnline = 1;', $rssContent);
		$rssContent = preg_replace('/defPrefix = ".+";/', 'defPrefix = "' . $wholeURL . '?file=";', $rssContent);
		$rssLeft = strleft($rssContent, '/* P4 ypStart */');
		$rssRight = strrright($rssContent, '/* P4 ypEnd */');

		$rssContent =
			$rssLeft .
				'topOnline = pushBackStringArray(topOnline, defOnline);' . "\r\n" .
			"\t" . 'topPrefix = pushBackStringArray(topPrefix, defPrefix);' . "\r\n" .
			"\t" . 'imPrefix  = pushBackStringArray(imPrefix, "5-CJFastCHS");' .
			$rssRight;

		// 2. User-defined input methods
		for ($i = 1 ; $i <= 4 ; $i ++) {
			$argUserModule = 'p' . $i;

			unset($userModule);
			if(empty($_GET[$argUserModule]))
				continue;
			$userModule = $_GET[$argUserModule];

			$userModuleSettings = explode(',', $userModule);
			if (count($userModuleSettings) != 3)
				continue;

			$rssLeft = strleft($rssContent, '/* P' . $i . ' Start */');
			$rssRight = strrright($rssContent, '/* P' . $i . ' End */');

			$rssContent =
				$rssLeft .
					'topOnline = pushBackStringArray(topOnline, ' . $userModuleSettings[0] . ');' . "\r\n" .
				"\t" . 'topPrefix = pushBackStringArray(topPrefix, "' . $userModuleSettings[1] . '");' . "\r\n" .
				"\t" . 'imPrefix = pushBackStringArray(imPrefix, "' . $userModuleSettings[2] . '");' .
				$rssRight;
		}

		echo $rssContent;
	}
	else {
		// Check if the requested file is a local or remote file
		$argFileRemote = 'remote';

		if(empty($_GET[$argFileRemote])) {
			// It's a local file (local as on the same server)
			if (file_exists($file))
				echo local_file_get_contents($file);
			else {
				echo "\n\n\n";
				echo "Local file: \"$file\"\n";
			}
		}
		else {
			// It's a remote file on the given server
			$txtConent = yp_file_get_contents($file);
			// Assume that the input method data/text files contain no "404",
			// which the not found error code for HTTP
			if (strpos($txtCouent, '404') === false)
				echo $txtConent;
			else {
				echo "\n\n\n";
				echo "Remote file: \"$file\"\n";
			}
		}
	}
	echo "\n\n\n";

	function wholeURLforTheExecutedFile() {
		$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
		$protocol = strleft(strtolower($_SERVER['SERVER_PROTOCOL']), '/') . $s;
		$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':' . $_SERVER['SERVER_PORT']);
		return $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['SCRIPT_NAME'];
	}

	function strleft($s1, $s2) {
	    return substr($s1, 0, strpos($s1, $s2));
	}

	function strrright($s1, $s2) {
	    return substr($s1, strrpos($s1, $s2) + strlen($s2));
	}

	function strrleft($s1, $s2) {
	    return substr($s1, 0, strrpos($s1, $s2));
	}

	function local_file_get_contents($file) {
		return file_get_contents($file);
	}

	function yp_file_get_contents($url, $timeout = 30, $referer = '', $user_agent = ''){
		global $imsUseCurl;

		if (!empty($imsUseCurl)) {
			$curl = curl_init();
			if(strstr($referer, '://')) {
				curl_setopt ($curl, CURLOPT_REFERER, $referer);
			}
			curl_setopt ($curl, CURLOPT_URL, $url);
			curl_setopt ($curl, CURLOPT_TIMEOUT, $timeout);
			if (strlen($user_agent) == 0) {
				$user_agent = ini_get('user_agent');
			}
			curl_setopt ($curl, CURLOPT_USERAGENT, $user_agent);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
			$html = curl_exec ($curl);
			curl_close ($curl);
			return $html;
		}
		else {
			return file_get_contents($url);
		}
	}
?>
