<?php
use kaiheila\api\base\Frame;

$config = require BASE_PATH . '/config/main.php';
function bot()
{
    global $config;
    static $bot = null;
    if (is_null($bot)) {
        $bot = new \bot\BotApplication($config);
    }

    return $bot;
}

function frame($d = [], $s = 0, $sn = 0)
{
    return app(Frame::class, [], [
        'd' => $d,
        's' => $s,
        'sn' => $sn,
    ]);
}
