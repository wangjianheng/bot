<?php

namespace bot\common;

use Illuminate\Support\Arr;
use \Swoole\Coroutine;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Event;
use \Swoole\Timer;
use \Yii;

class Sync implements BootstrapInterface
{
    const EVENT_BEFORE_DELETE = 'before_sync_del';

    const CRON_TIME = 10000;

    /**
     * 最多存1000个回调
     */
    const MAX_ITEMS = 1000;

    protected static $data = [];

    /**
     * 存储服务 实现get set就行
     * 默认就存到变量里了 用起来最方便 什么都可以存 只是受局限MAX_ITEMS
     * 如果放到reids或则db 回调方法只能是静态函数了
     * @var Sync $store
     */
    protected static $store;

    protected static $storeData = [];

    public function bootstrap($app)
    {
        $app->on(Application::EVENT_AFTER_REQUEST, [static::class, 'del']);

        Timer::tick(self::CRON_TIME, [self::class, 'cron']);

        static::setStore($this);
    }

    public static function setStore($store)
    {
        static::$store = $store;
    }

    /**
     * 按pcid分开
     * @param $key sting 键值
     * @param mixed $val 值
     * @return mixed
     */
    public static function map(string $key, $val = null)
    {
        $key = Coroutine::getCid() . '.' . $key;
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

        unset(static::$data[Coroutine::getCid()]);
    }

    /**
     * 注册调用 key => callback
     * @param string $key 键值
     * @param callable $callable 调用
     * @param int $time 存活时间
     */
    public static function registerCall($key, callable $callable, $time = 0)
    {
        static::$store->set($key, $callable, $time);
    }

    /**
     * 调用
     * @param string $key 键值
     * @param array $params 入参
     * @return mixed
     */
    public static function call($key, $params)
    {
        if ($callable = static::$store->get($key)) {
            return call_user_func_array($callable, (array)$params);
        }
    }

    /**
     * 获取
     * @param string $key 键值
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (! isset(static::$storeData[$key])) {
            return $default;
        }

        list($value, $time) = static::$storeData[$key];
        if ($time > 0 && $time < time()) {
            unset(static::$storeData[$key]);
            return $default;
        }

        return $value;
    }

    /**
     * 存储
     * @param string $key 键值
     * @param mixed $val 值
     * @param int $time 存活时间
     * @return bool
     */
    public function set($key, $val, $time)
    {
        if (count(static::$storeData) >= self::MAX_ITEMS) {
            return false;
        }

        static::$storeData[$key] = [$val, $time + time()];
        return true;
    }

    /**
     * 清理过期数据
     */
    public static function cron()
    {
        static::$storeData = array_filter(static::$storeData, function ($item) {
            list(, $time) = $item;
            return $time >= time();
        });
    }

}
