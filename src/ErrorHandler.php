<?php
namespace bot;

use bot\application\BotApplication;
use \yii\base\ErrorHandler as Base;
use Swoole\ExitException;
use yii\base\InvalidRouteException;

class ErrorHandler extends Base
{

    public function handleException($exception)
    {
        \Yii::$app->trigger(BotApplication::EVENT_AFTER_REQUEST);
        try {
            $this->renderException($exception);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function renderException($exception){
        switch (get_class($exception)) {
            case ExitException::class:
                $this->silentExitOnException = true;
                break;

            case InvalidRouteException::class:
                break;

            default:
                throw $exception;
        }
    }

    public function unregister(){}
}