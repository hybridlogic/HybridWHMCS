<?php

class HTTP {

	static $headers;
	static $ch;
	static $code;

	static function get($url, $throw=false) {
		self::$ch = curl_init($url);
		return self::perform(self::$ch, $throw);
	}

	static function post($url, $vars) {
		self::$ch = curl_init($url);
		curl_setopt(self::$ch, CURLOPT_POST, true);
		curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $vars);
        if (strpos($url,'https:')) {
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, false);
        }
		return self::perform(self::$ch);
	}

	static function put($url, $file) {
		self::$ch = curl_init($url);
		$fp = fopen($file, "r");
		curl_setopt(self::$ch, CURLOPT_PUT, true);
		curl_setopt(self::$ch, CURLOPT_INFILE, $fp);
		curl_setopt(self::$ch, CURLOPT_INFILESIZE, filesize($file));
		curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
        if (strpos($url,'https:')) {
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, false);
        }
		return curl_exec(self::$ch);
	}

	static function postFile($url, $file) {
		self::$ch = curl_init($url);
		$fp = fopen($file, "r");
		curl_setopt(self::$ch, CURLOPT_POST, true);
		curl_setopt(self::$ch, CURLOPT_INFILE, $fp);
		curl_setopt(self::$ch, CURLOPT_INFILESIZE, filesize($file));
		curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
        if (strpos($url,'https:')) {
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, false);
        }
		return curl_exec(self::$ch);
	}

	static function retryGet($url) {
		for ($n = 0; $n < 5; $n++) {
			try {
				$data = self::get($url, true);
				return $data;
			} catch (Exception $e) {};
		}
		throw $e;
	}

	static function perform($ch, $throw=false) {
		self::$headers = Array();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Required for TPJ importer
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, Array("self", "writeHeaders"));
		$data = curl_exec($ch);
		self::$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($throw and self::$code >= 400 and self::$code < 600)
			throw new HTTPFailed(self::$code);
		// Arg this copies loads of memory
		if (self::$code == 301 or self::$code == 302 and strlen(@self::$headers['location'])) {
			curl_setopt($ch, CURLOPT_URL, self::$headers['location']);
			return self::perform($ch, $throw);
		}
		return $data;
	}

	static function writeHeaders($ch, $header) {
		if (strpos($header, ": ") !== false) {
			@list($k, $v) = explode(": ", trim($header));
			self::$headers[strtolower($k)] = $v;
		}
		return strlen($header);
	}
}

class HTTPFailed extends Exception {

	private $status_code;

	function __construct($status_code) {
		$this->status_code = $status_code;
	}

	function __toString() {
		return "HTTP Error {$this->status_code}";
	}
}
