<?php

namespace bot\common;

use bot\application\BotApplication;
use Illuminate\Support\Arr;
use Swoole\Coroutine;
use Swoole\Timer;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;

class Sync implements BootstrapInterface
{
    const EVENT_BEFORE_DELETE = 'before_sync_del';

    const SYNC_KEY = 'sync_key:';

    const CRON_TIME = 10000;

    /**
     * 最多存1000个回调
     */
    const MAX_ITEMS = 1000;

    protected static $data = [];

    /**
     * 存储服务 实现get set就行
     * @var Sync
     */
    protected static $store;

    protected static $storeData = [];

    protected static $deferCall = [
        [Sync::class, 'del'],
    ];

    public function bootstrap($app)
    {
        Timer::tick(self::CRON_TIME, [self::class, 'cron']);

        !static::$store && static::setStore($this);

        bot()->on(BotApplication::EVENT_BEFORE_REQUEST, [Sync::class, 'defer']);
    }

    public static function defer()
    {
        Coroutine::defer(function () {
            foreach (self::$deferCall as $call) {
                is_callable($call) and call_user_func($call);
            }
        });
    }

    public static function setStore($store)
    {
        static::$store = $store;
    }

    public static function store()
    {
        return static::$store;
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
            'class' => Event::class,
            'name' => self::EVENT_BEFORE_DELETE,
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
    public static function registerCall($key, array $callable, $time = null)
    {
        if (!$key || !is_callable($callable)) {
            return;
        }

        static::$store->set(self::SYNC_KEY . $key, $callable, $time);
    }

    public static function unregisterCall($key)
    {
        static::$store->delete(self::SYNC_KEY . $key);
    }

    /**
     * 调用
     * @param string $key 键值
     * @param array $params 入参
     * @return mixed
     */
    public static function call($key, $params)
    {
        if ($callable = static::$store->get(self::SYNC_KEY . $key)) {
            return call_user_func_array($callable, (array) $params);
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
        return static::cacheGet($key, $default);
    }

    public static function cacheGet($key, $default = null)
    {
        if (!isset(static::$storeData[$key])) {
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
        return static::cacheSet($key, $val, $time);
    }

    public static function cacheSet($key, $val, $time)
    {
        if (count(static::$storeData) >= self::MAX_ITEMS) {
            return false;
        }

        static::$storeData[$key] = [$val, $time + time()];
        return true;
    }

    public function delete($key)
    {
        static::cacheDel($key);
    }

    public static function cacheDel($key)
    {
        unset(static::$storeData[$key]);
    }

    /**
     * 清理过期数据
     */
    public static function cron()
    {
        static::$storeData = array_filter(static::$storeData, function ($item) {
            list(, $time) = $item;
            return (int) $time >= time();
        });
    }
}
