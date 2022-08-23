<?php

namespace bot\common;

use yii\base\BootstrapInterface;
use yii\log\Dispatcher;
use \Swoole\Timer;

class LogDispatcher extends Dispatcher implements BootstrapInterface
{

    public $flushTimeStep = 10000;

    public function bootstrap($app)
    {
        Timer::tick($this->flushTimeStep, function() {
            \Yii::getLogger()->flush(true);
        });
    }
}


