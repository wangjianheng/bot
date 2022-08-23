<?php

namespace bot\session;

use kaiheila\api\base\WebhookSession as Base;
use Swoole\Coroutine\Http\Server as SwooleSer;

class WebhookSession extends Base
{
    protected $http;

    public function __construct($ip, $port, $encrypt_key = '', $verify_token = '', $compress = 1)
    {
        $this->http = new SwooleSer($ip, $port);

        //接请求
        $this->http->handle('', [$this, 'requestHandle']);

        parent::__construct($encrypt_key, $verify_token, $compress);
    }

    public function requestHandle($request, $response)
    {
        $response->header('Content-Type', 'application/json');
        try {
            $result = $this->receiveData($request->getContent());
            $response->end($result);
        } catch (\Exception $e) {
            $response->status(500, 500);
            $response->end($e->getMessage());
        }
    }

    public function start()
    {
        $this->http->start();
    }
}
