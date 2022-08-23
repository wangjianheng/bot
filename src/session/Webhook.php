<?php

namespace bot\session;

use kaiheila\api\base\WebhookSession;
use Swoole\Http\Server as SwooleSer;

class Webhook extends WebhookSession
{
    protected $http;

    public function __construct($ip, $port, $encrypt_key = '', $verify_token = '', $compress = 1)
    {
        $this->http = new SwooleSer($ip, $port);

        //接请求
        $this->http->on('request', [$this, 'requestHandle']);

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
