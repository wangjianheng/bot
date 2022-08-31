<?php

namespace bot\http;

use bot\common\Config;
use Illuminate\Support\Arr;
use kaiheila\api\helpers\ApiHelper;
use yii\base\Behavior;

class Http extends Behavior
{
    const AIP_VERSION = '/api/v3';

    const MESSAGE_CREATE = self::AIP_VERSION . '/message/create';

    const USER_ME = self::AIP_VERSION . '/user/me';

    const JOINED_CHANNEL = self::AIP_VERSION . '/channel-user/get-joined-channel';

    protected $token;

    protected $baseUrl;

    protected $user = [];

    public function __construct()
    {
        $this->token = Config::val('main.botSession.token');
        $this->baseUrl = Config::val('main.botSession.baseUrl');
    }

    /**
     * 实例化一个ApiHelper
     * @param string $path
     * @param string $type
     * @param string $language
     * @return ApiHelper
     * @throws \yii\base\InvalidConfigException
     */
    public function sender($path = '', $type = 'Bot', $language = 'zh-CN')
    {
        /**
         * @var $sender ApiHelper
         */
        $sender =  app(ApiHelper::class, [
            'token'    => $this->token,
            'baseUrl'  => $this->baseUrl,
            'path'     => $path,
            'type'     => $type,
            'language' => $language,
        ]);

        return $sender;
    }

    /**
     * 回应消息
     * @param string $msg 消息内容
     */
    public function reply($msg)
    {
        $body = [
            'target_id' => request()->get('target_id'),
            'content'   => $msg,
            'quote'     => request()->get('msg_id'),
        ];
        return $this->sender(self::MESSAGE_CREATE)->setBody($body)->send(ApiHelper::POST);
    }

    /**
     * 当前用户信息
     * @param string $key 键值
     */
    public function user($key = null)
    {
        if (empty($this->user)) {
            $user = $this->sender(self::USER_ME)->send(ApiHelper::GET);
            $this->user = $user['data'] ?? [];
        }

        return Arr::get($this->user, $key);
    }

    public function joinedChannel($page = 1, $pageSize = 10, $user = null, $guild = null)
    {
        $body = [
            'page'      => $page,
            'pageSize'  => $pageSize,
            'user_id'   => $user ?? request()->get('author_id'),
            'guild'     => $guild ?? request()->get('extra.guild_id'),
        ];
//        return $this->sender->setBody($body)
//            ->setPath(Sender::JOINED_CHANNEL)
//            ->send(Sender::GET);
    }

}