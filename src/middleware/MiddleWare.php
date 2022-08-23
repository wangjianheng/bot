<?php

namespace bot\middleware;

use bot\command\CommandManager;
use bot\common\Config;
use bot\common\RequestEvent;
use Illuminate\Support\Collection;
use yii\base\Application;
use yii\base\BootstrapInterface;

class MiddleWare implements BootstrapInterface, MiddleWareInterface
{
    /**
     * @var Collection
     */
    protected $middleWares;

    public function bootstrap($app)
    {
        //listen
        $app->on(Application::EVENT_BEFORE_REQUEST, [$this, 'handle']);

        //load
        $middleWare = array_merge(
            Config::get('middle'),
            [
                CommandManager::class,
            ]
        );

        $this->middleWares = collect($middleWare)
            ->map(function ($class) {
                return app($class);
            })
            ->filter(function ($class) {
                return $class instanceof MiddleWareInterface;
            });
    }

    /**
     * 接收事件
     */
    public function handle(RequestEvent $event) : RequestEvent
    {
        foreach ($this->middleWares as $middleWare) {
            /**
             * @var MiddleWareInterface $middleWare
             */
            $event = $middleWare->handle($event);

            if ($event->isAborted()) {
                break;
            }
        }

        return $event;
    }
}
