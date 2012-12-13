<?php
require("Inflector.class.php");
require("HTTP.class.php");

class HybridClusterAPI {

    private $endpoint;
    private $post_as_json = false;
    protected $path;

    public function __construct($control_panel, $username, $apikey, $post_as_json=false) {
        $this->endpoint = $control_panel . "/" . $this->path;
        $this->username = $username;
        $this->apikey = $apikey;
        $this->post_as_json = $post_as_json;
    }

    public function __call($name, $arguments) {
        if ($this->post_as_json)
            $parameters = Array(
                "api_format" => "structured_json",
                "json" => json_encode($arguments[0]),
            );
        else
            $parameters =& $arguments[0];
        
        $parameters['username'] = $this->username;
        $parameters['apikey'] = $this->apikey;

        $method = Inflector::underscore($name);
        $response_str = HTTP::post($url = "{$this->endpoint}/{$method}", $parameters);

        if (!strlen($response_str))
            throw new HybridClusterAPIException("Empty response received from {$url}");

        $data = json_decode($response_str);
        logModuleCall("hybridcluster", $url, $parameters, $response_str, $data, Array($this->apikey));
        
        if (is_null($data))
            throw new HybridClusterAPIJSONDecodeException($response_str);
        if (@$data->result == "string")
            return $data->string;
        if (@$data->result != "success")
            throw new HybridClusterAPIError($data->error);

        unset($data->result);

        return $data;
    }
}

class HybridClusterAPIException extends Exception {}
class HybridClusterAPIError extends HybridClusterAPIException {}

class HybridClusterAPIJSONDecodeException extends HybridClusterAPIException {}
