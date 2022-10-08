<?php

namespace bot\common;


class Tool
{
    /**
     * 访问对象
     * @param $ins object 对象
     * @param $attr string|\Closure 访问
     * @return mixed
     */
    public static function access($ins, $attr)
    {
        return (function() use ($attr) {
            if ($attr instanceof \Closure) {
                return $attr->call($this);
            }

            if (is_string($attr)) {
                return $this->{$attr};
            }
        })->call($ins);
    }

    /**
     * 是否为map
     * @return boolean
     */
    public static function isMap($array)
    {
        $keys = array_keys($array);
        return ! empty(
            array_filter($keys, 'is_string')
        );
    }

    /**
     * 是否为数组
     * @return boolean
     */
    public static function isArr($array)
    {
        $keys = array_keys($array);
        return ! empty(
        array_filter($keys, 'is_int')
        );
    }
}
