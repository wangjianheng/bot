<?php

namespace bot\message;

use Illuminate\Support\Arr;

class Message
{
    /**
     * 消息类型
     */
    const TYPE_TEXT = 1;      //文字类型

    const KMARK_DOWN = 9;     //kmarkdown

    const TYPE_CARD = 10;     //card

    protected $type = self::TYPE_TEXT;

    /**
     * 发送渠道
     */
    const SEND_CHANNEL = 1;

    const SEND_CODE = 2;

    const SEND_TARGET = 3;

    public $items = [];

    /**
     * @var string 发送的消息id
     */
    public $msgId = null;

    /**
     * @param int $channel 消息发送渠道
     */
    public $channel = null;

    public function __construct($params = null)
    {
    }

    public static function create($msgid, $channel, $items = [])
    {
        $msg = static::instance();
        $msg->msgId = $msgid;
        $msg->items = $items;
        $msg->channel = $channel;
        return $msg;
    }

    /**
     * 发消息
     * @param int $target 频道id
     * @param int $channel 发送渠道
     * @param array $properties 属性
     * @return array
     * @throws \Exception
     */
    public function send($target, $channel = self::SEND_CHANNEL, $properties = [])
    {
        //发到哪里
        $handle = $this->sendChannel('create', $channel);
        if (!$handle) {
            return [];
        }

        $properties['type'] = $this->type;
        $newMsg = call_user_func_array($handle, [$target, $this->out(), $properties]);
        isset($newMsg['msg_id']) and $this->afterSend($newMsg);
        $this->msgId = Arr::get($newMsg, 'msg_id');
        $this->channel = $channel;

        return $newMsg;
    }

    /**
     * 发送渠道对应的函数
     * @param string $oper 操作
     * @param int $channel 渠道
     * @return callable
     */
    protected function sendChannel($oper, $channel)
    {
        $handleMap = [
            'create' => [
                self::SEND_CHANNEL => 'messageCreate',
                self::SEND_CODE => 'directMsgCreateByCode',
                self::SEND_TARGET => 'directMsgCreateByTarget',
            ],

            'update' => [
                self::SEND_CHANNEL => 'messageUpdate',
                self::SEND_CODE => 'directMsgUpdate',
                self::SEND_TARGET => 'directMsgUpdate',
            ],

            'delete' => [
                self::SEND_CHANNEL => 'messageDelete',
                self::SEND_CODE => 'directMsgDelete',
                self::SEND_TARGET => 'directMsgDelete',
            ],
        ];

        if (!$channel || !$handle = Arr::get($handleMap, "{$oper}.{$channel}")) {
            return null;
        }

        return [\Yii::$app->http, $handle];
    }

    /**
     * 发消息并清空消息
     * @param int $target 频道id
     * @param array $properties 属性
     * @return array
     * @throws \Exception
     */
    public function cleanSend($target, $properties = [])
    {
        $msg = $this->send($target, $properties);
        $this->clean();
        return $msg;
    }

    /**
     * 更新消
     * @param array $properties 属性
     * @return array
     * @throws \Exception
     */
    public function update($properties = [])
    {
        if (
            !$this->msgId ||
            !in_array($this->type, [self::KMARK_DOWN, self::TYPE_CARD]) ||
            !$handle = $this->sendChannel('update', $this->channel)
        ) {
            return [];
        }

        $this->afterSend(['msg_id' => $this->msgId]);
        return call_user_func_array($handle, [$this->msgId, $this->out(), $properties]);
    }

    /**
     * 删除
     * @return bool
     * @throws \Exception
     */
    public function delete()
    {
        if (
            !($msg = $this->msgId) ||
            !($handle = $this->sendChannel('delete', $this->channel))
        ) {
            return true;
        }

        //清空
        $this->channel = $this->msgId = null;
        $this->clean();

        //删除
        return call_user_func_array($handle, [$msg]);
    }

    /**
     * 清空
     * @return $this
     */
    public function clean()
    {
        $this->items = [];
        return $this;
    }

    public static function instance()
    {
        return new static(...func_get_args());
    }

    /**
     * 获取消息内容
     * @param $msg 消息
     * @return string
     */
    public static function content($msg)
    {
        if ($msg instanceof Message) {
            return $msg->out();
        }

        return $msg;
    }

    /**
     * 转成可发送的消息
     * @return string
     */
    public function out()
    {
        return join('', $this->items);
    }

    /**
     * 是否为空
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * 加一个元素
     * @param mixed $item 元素
     * @return $this
     */
    public function push($item)
    {
        array_push($this->items, $item);

        return $this;
    }

    public function drop($key)
    {
        unset($this->items[$key]);
        return $this;
    }

    public function setItem($key, $val)
    {
        $this->items[$key] = $val;
        return $this;
    }

    /**
     * 发送后钩子
     */
    public function afterSend($newMsg)
    {
    }
}
