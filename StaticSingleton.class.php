<?php
require("Singleton.class.php");

class StaticSingleton extends Singleton {

    public static function __callStatic($name, $arguments) {
        $class = get_called_class();
        $self = $class::getInstance();
        return call_user_func_array(Array($self, "static_{$name}"), $arguments);
    }

    public function __call($name, $arguments) {
        if (!method_exists($this, "static_{$name}"))
            throw new Exception("Unknown method {$class}::{$name}");
        return call_user_func_array(Array($this, "static_{$name}"), $arguments);
    }
}
