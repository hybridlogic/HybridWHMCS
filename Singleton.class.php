<?php
abstract class Singleton {

    final public static function getInstance() {
        static $instances = array();

        $class = get_called_class();

        if (!isset($instances[$class]))
            $instances[$class] = new $class();

        return $instances[$class];
    }

    final private function __clone() {
    }
}
