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
        array_walk($frames, [$this, 'receiveFrame']);
    }
}
