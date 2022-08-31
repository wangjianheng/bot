<?php
namespace bot\session;

use bot\common\Config;
use kaiheila\api\base\Frame;
use \kaiheila\api\base\Session;

class DevSession extends Session
{
    public function sendData($data)
    {
        return;
    }

    public function start()
    {
        $frames = array_map([Frame::class, 'getFromData'], Config::get('frame'));
        foreach ($frames as $frame) {
            go(function () use ($frame) {
                $this->receiveFrame($frame);
            });
        }
    }
}
