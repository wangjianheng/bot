<?php
namespace bot\request;

use bot\common\Sync;
use bot\message\BotFrame;
use Illuminate\Support\Arr;
use yii\console\Request as Base;

class Request extends Base
{
    const TEXT_TYPE = 1;

    const KMARKDOWN_TYPE = 9;

    const OTHER_TYPE = 255;

    /**
     * @var Router
     */
    public $router = null;

    protected $frame = 'frame';

    public $defaultType = 'default';

    public function resolve()
    {
        //文字走console
        if (in_array($this->get('type'), [self::TEXT_TYPE, self::KMARKDOWN_TYPE])) {
            return parent::resolve();
        }

        //其他事件走router
        return $this->router->trans($this);
    }

    /**
     * 仅console走这个方法
     * @return array|void
     */
    public function getParams()
    {
        $content = $this->get('content');
        $params = explode(' ', $content);

        return array_filter(
            array_map('trim', $params)
        );
    }

    /**
     * 从data里拉数据
     * @param string|array $keys 键值
     * @return mixed
     */
    public function get($keys = null, $default = null)
    {
        $data = $this->frame()->d;
        if (is_null($keys)) {
            return $data;
        }

        $valuse = array_map(function ($key) use ($data, $default) {
            return Arr::get($data, $key, $default);
        }, Arr::wrap($keys));

        return is_string($keys) ? current($valuse) : $valuse;
    }

    /**
     * 获取frame
     * @return BotFrame $frame
     */
    public function frame()
    {
        return Sync::map($this->frame) ?? new BotFrame();
    }

    /**
     * 设置frame
     * @param BotFrame $frame
     * @return $this
     */
    public function setFrame(BotFrame $frame)
    {
        Sync::map($this->frame, $frame);
        return $this;
    }

    /**
     * 类型
     * @return string
     */
    public function requestType()
    {
        /**
         * 渠道&类型
         * 非系统消息(255) 取extra.type
         */
        list($channel, $type, $extraType) = $this->get(['channel_type', 'type', 'extra.type']);
        if ($type == self::OTHER_TYPE) {
            $type = $extraType;
        }

        if (! $channel || ! $type) {
            return $this->defaultType;
        }

        return $channel . '/' . $type;
    }
}