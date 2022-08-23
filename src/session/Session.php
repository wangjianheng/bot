<?php

namespace bot\session;

use Illuminate\Support\Arr;
use kaiheila\api\base\WebsocketSession;

class Session
{
    const SESSION_HOOK   = 1;

    const SESSION_SOCKET = 2;

    const SESSION_DEV    = 3;

    /**
     * 实例化session
     * @param $conf array seesion配置
     * @return object session
     * @throws \yii\base\InvalidConfigException
     */
    public static function build($conf)
    {
        $sessionType = Arr::pull($conf, 'type', false);

        if (is_null($abstract = static::sessionClassMap($sessionType))) {
            throw new \LogicException('session type error');
        }

        return app($abstract, $conf);
    }

    /**
     * @param $sessoin int 枚举值
     * @return string
     */
    protected static function sessionClassMap($session)
    {
        $map = [
            self::SESSION_SOCKET => WebsocketSession::class,
            self::SESSION_HOOK   => WebhookSession::class,
            self::SESSION_DEV    => DevSession::class,
        ];

        return Arr::get($map, $session);
    }
}