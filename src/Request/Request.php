<?php
namespace bot;

use Illuminate\Support\Arr;
use \yii\base\Request as Base;
use kaiheila\api\base\Frame;

class Request extends Base
{
    const OTHER_TYPE = 255;

    /**
     * @var Router
     */
    public $router = null;

    /**
     * @var Frame
     */
    protected $frame = null;

    public $defaultType = 'default';

    public function resolve()
    {
        $path = $this->router->trans($this->requestType());

        return [$path, []];
    }

    /**
     * 从data里拉数据
     * @param string|array $keys 键值
     * @return mixed
     */
    public function get($keys = null)
    {
        $data = $this->frame->d ?? [];
        if (is_null($keys)) {
            return $data;
        }

        $valuse = array_map(function ($key) use ($data) {
            return Arr::get($data, $key);
        }, Arr::wrap($keys));

        return is_string($keys) ? current($valuse) : $valuse;
    }

    /**
     * 设置frame
     * @param Frame $frame
     * @return $this
     */
    public function setFrame(Frame $frame)
    {
        $this->frame = $frame;
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