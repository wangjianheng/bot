<?php

namespace bot\request;
use Illuminate\Support\Collection;

class Route
{
    public $path;

    protected $name;

    /**
     * @var Collection
     */
    protected $condition;

    protected static $pluck = null;

    public static function setPluck(callable $pluck)
    {
        static::$pluck = $pluck;
    }

    /**
     * Route constructor.
     * @param $name frame类型
     * @param $path 路径
     */
    public function __construct($name, $path)
    {
        $this->name = $name;

        $this->resovlePath($path);
    }

    /**
     * 是否存在过滤
     * @return boolean
     */
    public function hasCondition()
    {
        return $this->condition->isNotEmpty();
    }

    /**
     * 解析路径
     * @param $path 路径
     */
    protected function resovlePath($path)
    {
        $info = parse_url($path);

        /**
         * 根据frame数据做过滤
         * 目前只支持=，后面有需要再加
         */
        $this->condition = collect(explode('&', $info['query'] ?? ''))
            ->filter()
            ->mapWithKeys(function ($val) {
                list($key, $val) = explode('=', $val, 2);
                return [$key => ['=', $val]];
            });

        $this->path = $info['path'] ?? $path;
    }

    /**
     * 路由匹配
     * @param Request $request
     * @return boolean
     */
    public function match(Request $request)
    {
        /**
         * 以后扩展正则
         */
        if ($this->name !== $request->requestType()) {
            return false;
        }

        //匹配 以后扩展其他符号
        foreach ($this->condition as $key => list($oper, $val)) {
            $words = $request->get($key, '');
            if ($this->pluck($words) != $val) {
                return false;
            }
        }

        return true;
    }

    /**
     * 把命令捡出来
     */
    protected function pluck($words)
    {
        if (! is_null(static::$pluck)) {
            return call_user_func(static::$pluck, $words);
        }

        return current(explode(' ', $words));
    }
}
