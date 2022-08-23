<?php

namespace bot\response;
use bot\common\Config;
use \yii\base\Response as Base;

class Response extends Base
{
    /**
     * @var $sender Sender
     */
    protected $sender;

    public function __construct($config = [])
    {
        $this->sender = app(Sender::class, [
            'path'    => '',
            'token'   => Config::val('main.botSession.token'),
            'baseUrl' => Config::val('main.botSession.baseUrl'),
        ]);

        parent::__construct($config);
    }

    public function reply($msg)
    {
        $body = [
            'target_id' => request()->get('target_id'),
            'content'   => $msg,
            'quote'     => request()->get('msg_id'),
        ];

        return $this->sender->setBody($body)
            ->setPath(Sender::MESSAGE_CREATE)
            ->send(Sender::POST);
    }

    public function joinedChannel($page = 1, $pageSize = 10, $user = null, $guild = null)
    {
        $body = [
            'page'      => $page,
            'pageSize'  => $pageSize,
            'user_id'   => $user ?? request()->get('author_id'),
            'guild'     => $guild ?? request()->get('extra.guild_id'),
        ];
        return $this->sender->setBody($body)
            ->setPath(Sender::JOINED_CHANNEL)
            ->send(Sender::GET);
    }

}