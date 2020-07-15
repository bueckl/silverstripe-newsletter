<?php
namespace Newsletter\Traits;

trait Helper {
    public function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class);
    }
}
