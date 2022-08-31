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

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }



}
