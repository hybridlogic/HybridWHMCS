<?php
require_once("HybridClusterAPI.class.php");

class WHMCSAPI extends HybridClusterAPI {

    private $post_as_json = true;
    protected $path = "whmcs";

}
