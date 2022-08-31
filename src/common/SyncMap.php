<?php

namespace bot\common;

use Illuminate\Support\Arr;
use \Swoole\Coroutine;
use yii\base\Application;

class SyncMap
{
    protected static $data = [];

    public function __construct()
    {
        \Yii::$app->on(Application::EVENT_AFTER_REQUEST, [static::class, 'del']);
    }

    /**
     * 设置|获取数据
     * @param $key sting 键值
     * @param mixed $val 值
     * @return mixed
     */
    public static function map($key, $val = null)
    {
        $key = Coroutine::getPcid() . '.' . $key;
        if (is_null($val)) {
            return Arr::get(static::$data, $key);
        }

        Arr::set(static::$data, $key, $val);
    }

    /**
     * 删除数据
     */
    public static function del()
    {
        unset(static::$data[Coroutine::getPcid()]);
    }


}
