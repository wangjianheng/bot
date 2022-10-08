<?php

namespace bot\common\traits;

trait Access
{
    protected $accessAble = [];

    public function __get($name)
    {
        if (in_array($name, $this->accessAble) && property_exists($this, $name)) {
            return $this->{$name};
        }
    }
}
