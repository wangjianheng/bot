<?php

namespace bot\application;

use bot\session\Session;
use Illuminate\Support\Arr;

class Boot
{
    protected $conf = [];

    public static $dev = false;

    public static function create($conf)
    {
        return new static($conf);
    }

    public function __construct($conf = [])
    {
        $this->conf = $conf;
    }

    public function run()
    {
        $app = app(BotApplication::class, ['config' => $this->conf]);
        $session = Arr::get($this->conf, 'botSession.type');

        go([$app, 'run']);
    }
}