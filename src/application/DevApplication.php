<?php
namespace bot\application;
use bot\common\Config;
use kaiheila\api\base\Frame;

class DevApplication extends BotApplication
{
    public function run()
    {
        $frame = $this->frames();
        array_walk($frame, [$this, 'handleRequest']);
    }

    protected function frames()
    {
        return array_map(function ($frame) {
            return app(Frame::class, [], $frame);
        }, Config::get('frame'));
    }
}
