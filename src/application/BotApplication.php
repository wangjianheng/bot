<?php
namespace bot\application;

use bot\common\RequestEvent;
use bot\middleware\MiddleWare;
use yii\base\Application;
use bot\{common\LogDispatcher, common\Sync, ErrorHandler, http\Http, message\BotFrame, message\Card, session\Session};
use bot\request\{Router, Request};
use \yii\base\Response;
use yii\log\Logger;
use \Yii;

/**
 * Class BotApplication
 * @package bot\application
 * @property Http $http 向服务器发送请求
 */
class BotApplication extends Application
{
    /**
     * @var \kaiheila\api\base\StateSession
     */
    public $botSession;

    public $controllerNamespace = 'controllers';

    public function init()
    {
        //session
        $this->botSession = Session::build($this->botSession);

        //加一些的启动项
        $this->withBoot();

        //路径
        $this->aliasPath();

        parent::init();
    }

    public function handleRequest($frame)
    {
        /**
         * before request event
         * @var $event RequestEvent
         */
        $frame = new BotFrame($frame);
        $event = app(RequestEvent::class, [], [
            'sender' => $this,
            'name'   => self::EVENT_AFTER_REQUEST,
            'frame'  => $frame,
        ]);

        //中间件
        $this->trigger(self::EVENT_BEFORE_REQUEST, $event);
        list($abort, $by, $msg) = $event->isAborted();
        if ($abort) {
            Yii::getLogger()->log("abort by {$by} cause {$msg}:", Logger::LEVEL_INFO);
            return;
        }

        list($route, $params) = $this->request->setFrame($frame)->resolve();
        if ($route === null) {
            Yii::getLogger()->log('route match error:' . $this->request->requestType(), Logger::LEVEL_WARNING);
            return;
        }

        $this->requestedRoute = $route;
        $this->runAction($route, $params);

        $this->trigger(self::EVENT_AFTER_REQUEST, $event);
    }

    public function run()
    {
        $this->botSession->on(BotFrame::TYPE_GROUP . '_*', handleVal([$this, 'handleRequest'], [$this->errorHandler, 'handleException']));
        $this->botSession->on(BotFrame::TYPE_PERSION . '_*', handleVal([$this, 'handleRequest'], [$this->errorHandler, 'handleException']));
        $this->botSession->on(BotFrame::TYPE_BROADCAST . '_*', handleVal([$this, 'handleRequest'], [$this->errorHandler, 'handleException']));

        $this->botSession->start();
    }

    /**
     * 核心组件
     * @return array
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'router'       => ['class' => Router::class],
            'errorHandler' => ['class' => ErrorHandler::class],
            'response'     => ['class' => Response::class],
            'middleware'   => ['class' => MiddleWare::class],
            'log'          => ['class' => LogDispatcher::class],
            'http'         => ['class' => Http::class],
            'sync'         => ['class' => Sync::class],
            'card'         => ['class' => Card::class],
            'request'      => lazyComponent(Request::class, ['router' => 'router']),
        ]);
    }

    /**
     * 加boot
     */
    protected function withBoot()
    {
        $this->bootstrap = array_merge($this->bootstrap, [
            'log', 'middleware', 'sync', 'card',
        ]);
    }

    /**
     * 每个文件夹路径
     */
    protected function aliasPath()
    {
        //配置
        Yii::setAlias('@conf', $this->basePath  . '/config');

        //controllers
        Yii::setAlias('@controllers', $this->basePath  . '/controllers');

        //model
        Yii::setAlias('@model', $this->basePath  . '/model');

        //service
        Yii::setAlias('@service', $this->basePath  . '/service');
    }

}