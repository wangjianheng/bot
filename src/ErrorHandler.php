<?php
namespace bot;

use \yii\base\ErrorHandler as Base;
use Swoole\ExitException;

class ErrorHandler extends Base
{
    protected function renderException($exception)
    {
        if ($exception instanceof ExitException) {
            $this->silentExitOnException = true;
        }

        print_r($exception);
    }
}