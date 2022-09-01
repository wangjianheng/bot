<?php

namespace bot\common;

use Illuminate\Support\Arr;
use \Swoole\Coroutine;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;
use \Yii;

class Sync implements BootstrapInterface
{
    const EVENT_BEFORE_DELETE = 'before_sync_del';

    protected static $data = [];

    public function bootstrap($app)
    {
        $app->on(Application::EVENT_AFTER_REQUEST, [static::class, 'del']);
    }

    /**
     * 按pcid分开
     * @param $key sting 键值
     * @param mixed $val 值
     * @return mixed
     */
    public static function map(string $key, $val = null)
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
        $event = [
            'class'  => Event::class,
            'name'   => self::EVENT_BEFORE_DELETE,
            'sender' => Sync::class,
        ];

        Yii::$app->trigger(self::EVENT_BEFORE_DELETE, Yii::createObject($event));

        unset(static::$data[Coroutine::getPcid()]);
    }


}
