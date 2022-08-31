<?php

namespace bot\response;

use Illuminate\Support\Arr;
use kaiheila\api\helpers\ApiHelper;

class Sender extends ApiHelper
{

    const AIP_VERSION = '/api/v3';

    const MESSAGE_CREATE = self::AIP_VERSION . '/message/create';

    const USER_ME = self::AIP_VERSION . '/user/me';

    const JOINED_CHANNEL = self::AIP_VERSION . '/channel-user/get-joined-channel';

    protected $user = [];

    public function send($method = self::GET)
    {
        return parent::send($method);
    }

    public function sendMessage($body)
    {
        return $this->setBody($body)->setPath(self::MESSAGE_CREATE)->send(self::POST);
    }

    protected function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function me($key = null)
    {
        if (empty($this->user)) {
            $user = $this->setPath(self::USER_ME)->send(self::GET);
            $this->user = $user['data'] ?? [];
        }

        return Arr::get($this->user, $key);
    }

    public function joinedChannel($page = 1, $pageSize = 10, $user = null, $guild = null)
    {
        if (is_null($user)) {}

    }



}