<?php
require("StaticSingleton.class.php");

class HTTP extends StaticSingleton {

	public $headers;
	public $ch;
	public $code;

    public function __construct() {
        $this->ch = curl_init();
    }

    public function static_get($url, $parameters=Array()) {
        if (is_array($parameters))
            $parameters = http_build_query($parameters, "", "&");

        if (strlen($parameters))
            $url .= "?" . $parameters;

        curl_setopt($this->ch, CURLOPT_URL, $url);
		return $this->perform();
	}

	public function static_post($url, $vars) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $vars);
		return $this->perform();
	}

	public function static_put($url, $file) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
		$fp = fopen($file, "r");
		curl_setopt($this->ch, CURLOPT_PUT, true);
		curl_setopt($this->ch, CURLOPT_INFILE, $fp);
		curl_setopt($this->ch, CURLOPT_INFILESIZE, filesize($file));
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($this->ch);
	}

	public function static_postFile($url, $file) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
		$fp = fopen($file, "r");
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_INFILE, $fp);
		curl_setopt($this->ch, CURLOPT_INFILESIZE, filesize($file));
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($this->ch);
	}

    public function setTimeout($timeout) {
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    }

	public function static_retryGet($url) {
		for ($n = 0; $n < 5; $n++) {
			try {
				$data = $this->get($url, true);
				return $data;
			} catch (Exception $e) {};
		}
		throw $e;
	}

	private function perform() {
		$this->headers = Array();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); // Required for TPJ importer
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, Array("self", "writeHeaders"));
		$data = curl_exec($this->ch);
		$this->code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        $this->errno = curl_errno($this->ch);
        switch (true) {
            case ($this->errno == 28):
                throw new HTTPTimeout;
            case ($this->code >= 400 and $this->code < 600):
                throw new HTTPFailed($this->code, $data);
            // Arg this copies loads of memory, why doesn't followlocation work properly?!
            case ($this->code == 301 or $this->code == 302 and strlen(@$this->headers['location'])):
                curl_setopt($this->ch, CURLOPT_URL, $this->headers['location']);
                return $this->perform();
        }
		return $data;
	}

	public function static_writeHeaders($ch, $header) {
		if (strpos($header, ": ") !== false) {
			@list($k, $v) = explode(": ", trim($header));
			$this->headers[strtolower($k)] = $v;
		}
		return strlen($header);
	}
}

class HTTPException extends Exception {}
class HTTPTimeout extends HTTPException {}
class HTTPFailed extends HTTPException {

	private $status_code;

	public function __construct($status_code, $output=null) {
		$this->status_code = $status_code;
		$this->output = $output;
	}

	public function __toString() {
		return "HTTP Error {$this->status_code}" . (strlen($this->output) ? ', output was: ' . $this->output : '');
	}
}
