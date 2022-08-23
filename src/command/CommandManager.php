<?php
namespace bot\command;

use bot\common\Config;
use bot\common\RequestEvent;
use bot\middleware\MiddleWareInterface;

class CommandManager implements MiddleWareInterface
{
    public $help = 'help';

    const CATCH_PATH = 9;

    protected $commands = [];

    public function __construct()
    {
        $commands = Config::get('command');
        $this->commands = array_map(function ($command) {
            return app(Command::class, [], $command);
        }, $commands);
    }

    public function handle(RequestEvent $event):RequestEvent
    {
        return $event;
    }


}