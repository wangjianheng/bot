<?php

namespace bot\common\traits;

use Illuminate\Support\Arr;

trait AsArray
{
    protected $access;

    public function offsetExists($offset)
    {
        return Arr::exists($this->val(), $offset);
    }

    public function offsetGet($offset)
    {
        return Arr::get($this->val(), $offset);
    }

    public function offsetSet($offset, $value)
    {
        Arr::set($this->{$this->access}, $offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->{$this->access}, $offset);
    }

    private function val()
    {
        return $this->{$this->access};
    }
}
