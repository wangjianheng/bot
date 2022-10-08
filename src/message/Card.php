<?php

namespace bot\message;

use bot\common\RequestEvent;
use bot\common\Sync;
use Illuminate\Support\Arr;
use Swoole\Timer;
use yii\base\Application;
use yii\base\BootstrapInterface;

/**
 * Class Card
 * @package bot\message
 */
class Card extends Message implements BootstrapInterface
{
    const TYPE_SECTION = 'section';

    const TYPE_HEADER = 'header';

    const TYPE_DIVIDER = 'divider';

    const TYPE_PLAIN = 'plain-text';

    const TYPE_KMARKDOWN = 'kmarkdown';

    const TYPE_PARAGRAPH = 'paragraph';

    const TYPE_CARD = 'card';

    const TYPE_BUTTON = 'button';

    const TYPE_IMAGE = 'image';

    const TYPE_FILE = 'file';

    const TYPE_IMAGE_GROUP = 'image-group';

    const TYPE_ACTION_GROUP = 'action-group';

    const TYPE_CONTEXT = 'context';

    const TYPE_AUDIO = 'audio';

    const TYPE_VIDEO = 'video';

    const TYPE_COUNT_DOWN = 'countdown';

    const TYPE_CONTAINER = 'container';

    const BUTTON_MAX_NUM = 4;

    public $buttonTime = 5 * 3600;

    /**
     * 卡片信息包含多个卡片 都保存到这里 渲染的时候把$this也push到这里
     * @var array
     */
    protected $nodes = [];

    protected $size;

    protected $theme;

    protected $color;

    protected $type = Message::TYPE_CARD;

    /**
     * @var int 投票按钮位置
     */
    protected $voteIndex = 0;

    /**
     * @var array 投票选项
     */
    protected $voteOptions = [];

    /**
     * @var array 已经投票的用户
     */
    protected $userHasBeenVote = [];

    /**
     * @var callable 投票完成回调
     */
    protected $voteCallBack = null;

    /**
     * @var array 按钮回调事件（临时存一下）
     */
    protected $buttonCallBackTmp = [];

    protected $downSecondCall = [];

    public function bootstrap($app)
    {
        $app->on(Application::EVENT_BEFORE_REQUEST, [Card::class, 'handleEvent']);
    }

    public function __construct($size = 'lg', $theme = 'primary', $color = null)
    {
        $this->size = $size;
        $this->theme = $theme;
        $this->color = $color;
    }

    /**
     * 普通文本
     * @param string|array $content 内容
     * @return $this
     */
    public function text($content)
    {
        return $this->push(static::eContent($content));
    }

    public function container($images)
    {
        return $this->push(static::eContainer($images));
    }

    /**
     * 标题
     * @param $content 内容
     * @return $this
     */
    public function header($content)
    {
        return $this->push([
            'type' => self::TYPE_HEADER,
            'text' => static::eText($content),
        ]);
    }

    /**
     * 分割线
     * @return $this
     */
    public function divider()
    {
        return $this->push([
            'type' => self::TYPE_DIVIDER,
        ]);
    }

    /**
     * kmarkdown
     * @param KmarkDown $kmarkDown markdown
     * @return $this
     */
    public function kmarkDown(KmarkDown $kmarkDown)
    {
        return $this->push(static::eKmarkdown($kmarkDown));
    }

    /**
     * 表格
     * @param array $items 元素
     * @return $this
     */
    public function paragragp($items = [])
    {
        //旋转
        $turn = [];
        for ($i = 0; $i < count($items[0]); $i++) {
            $col = array_map([Message::class, 'content'], Arr::pluck($items, $i));
            array_push($turn, $col);
        }

        //拼回车
        $turn = array_map(function ($item) {
            return [
                'type' => 'kmarkdown',
                'content' => join(PHP_EOL, $item),
            ];
        }, $turn);

        return  $this->push([
            'type' => self::TYPE_SECTION,
            'text' => [
                'type' => self::TYPE_PARAGRAPH,
                'cols' => count($turn),
                'fields' => $turn,
            ],
        ]);
    }

    /**
     * 文本+图片
     * @param string $content 文本
     * @param string $img  图片
     * @param string $mode 位置
     * @return $this
     */
    public function textWithImg($content, $img, $mode = 'right')
    {
        return $this->push([
            'type' => self::TYPE_SECTION,
            'text' => static::eText($content),
            'accessory' => static::eImg($img),
            'mode' => $mode,
        ]);
    }

    /**
     * 文本+按钮
     * @param string $content 文本
     * @param string $button  按钮
     * @param string $mode 位置
     * @return $this
     */
    public function textWithButton($content, $button, $mode = 'right')
    {
        return $this->push([
            'type' => self::TYPE_SECTION,
            'text' => static::eText($content),
            'accessory' => static::registerButtonCallback($button),
            'mode' => $mode,
        ]);
    }

    /**
     * 图片
     * @param array|string $images 图片
     * @return $this
     */
    public function images($images)
    {
        return $this->push([
            'type' => self::TYPE_IMAGE_GROUP,
            'elements' => array_map([static::class, 'eImg'], (array) $images),
        ]);
    }

    /**
     * 按钮
     * @param array|string $buttons 按钮
     * @return $this
     */
    public function buttons($buttons)
    {
        if (count($buttons) > self::BUTTON_MAX_NUM) {
            $buttons = array_chunk($buttons, self::BUTTON_MAX_NUM);
            foreach ($buttons as $button) {
                $this->push($button);
            }
            return $this;
        }

        return $this->push(
            static::eActionGroup(array_map([$this, 'registerButtonCallback'], (array) $buttons))
        );
    }

    /**
     * 注册按钮回调事件
     * @param array $button 按钮
     * @return array
     */
    protected function registerButtonCallback($button)
    {
        //format
        $button = static::eButton($button);

        //注册了回调
        $callback = Arr::pull($button, 'callback');
        if (Arr::get($button, 'click') == 'return-val' && is_callable($callback)) {
            array_push($this->buttonCallBackTmp, $callback);
        }

        return $button;
    }

    /**
     * 备注
     * @param array $elements 元素
     * @return $this
     */
    public function remarks($elements)
    {
        return $this->push([
            'type' => self::TYPE_CONTEXT,
            'elements' => $elements,
        ]);
    }

    /**
     * 文件
     * @param string $title 文件名
     * @param string $url 链接
     * @param int $size 大小
     * @return $this
     */
    public function file($title, $url, $size)
    {
        return $this->push([
            'type' => self::TYPE_FILE,
            'title' => $title,
            'size' => $size,
            'src' => $url,
        ]);
    }

    /**
     * 音频
     * @param string $title 文件名
     * @param string $url 链接
     * @param string $cover 封面
     * @return $this
     */
    public function audio($title, $url, $cover)
    {
        return $this->push([
            'type' => self::TYPE_AUDIO,
            'title' => $title,
            'cover' => $cover,
            'src' => $url,
        ]);
    }

    /**
     * 视频
     * @param string $title 文件名
     * @param string $url 链接
     * @return $this
     */
    public function video($title, $url)
    {
        return $this->push([
            'type' => self::TYPE_VIDEO,
            'title' => $title,
            'src' => $url,
        ]);
    }

    /**
     * 常规倒计时
     * @param int $end 结束时间
     * @return $this
     */
    public function countDownDay($end)
    {
        return $this->push([
            'type' => self::TYPE_COUNT_DOWN,
            'mode' => 'day',
            'endTime' => $end,
        ]);
    }

    /**
     * 小时倒计时
     * @param int $end 结束时间
     * @return $this
     */
    public function countDownHour($end)
    {
        return $this->push([
            'type' => self::TYPE_COUNT_DOWN,
            'mode' => 'hour',
            'endTime' => $end,
        ]);
    }

    /**
     * 读秒倒计时
     * @param int $start 开始时间
     * @param int $end   结束时间
     * @param callable $callback 结束后回调
     * @return $this
     */
    public function countDownSecond($start, $end, $callback = null)
    {
        //回调
        $callback and $this->downSecondCall[$end - $start] = $callback;

        return $this->push([
            'type' => self::TYPE_COUNT_DOWN,
            'mode' => 'second',
            'startTime' => $start,
            'endTime' => $end,
        ]);
    }

    public function setItem($key, $val)
    {
        //文字
        is_string($val) and $val = static::eContent($val);

        //kmarkdown
        $val instanceof KmarkDown and $val = static::eKmarkdown($val);

        //button
        if (Arr::get($val, '0.type', '') == self::TYPE_BUTTON) {
            $val = self::eActionGroup(
                array_map([$this, 'registerButtonCallback'], $val)
            );
        }

        return parent::setItem($key, $val);
    }

    public static function eContainer($images)
    {
        return [
            'type' => self::TYPE_CONTAINER,
            'elements' => array_map([static::class, 'eImg'], (array) $images),
        ];
    }

    public static function eContent($content)
    {
        return [
            'type' => self::TYPE_SECTION,
            'text' => static::eText($content),
        ];
    }

    /**
     * 文案元素
     * @param string $content 文案
     * @return array
     */
    public static function eText($content)
    {
        return [
            'type' => self::TYPE_PLAIN,
            'content' => $content,
        ];
    }

    /**
     * 图片元素
     * @param string $src 链接
     * @return array
     */
    public static function eImg($src)
    {
        return [
            'type' => self::TYPE_IMAGE,
            'src' => $src,
        ];
    }

    public static function eKmarkdown(KmarkDown $kmarkDown)
    {
        return [
            'type' => self::TYPE_SECTION,
            'text' => [
                'type' => self::TYPE_KMARKDOWN,
                'content' => $kmarkDown->out(),
            ],
        ];
    }

    public static function eActionGroup($elements)
    {
        return [
            'type' => self::TYPE_ACTION_GROUP,
            'elements' => $elements,
        ];
    }

    /**
     * 按钮元素
     * @param string|array $content 文案
     * @param array $properties 其他属性
     * @param callable $callback 回调
     * @return array
     */
    public static function eButton($content, $properties = [], $callback = [])
    {
        if (is_array($content)) {
            return $content;
        }

        isset($properties['value']) and $properties['value'] = (string) $properties['value'];

        $callback and $properties['click'] = 'return-val';
        $button = array_merge(
            [
                'type' => self::TYPE_BUTTON,
                'text' => static::eText($content),
                'callback' => $callback,
            ],
            $properties
        );

        return $button;
    }

    /**
     * 加一个新卡片
     * @param Card $card 卡片
     * @return Card
     */
    public function addNew(Card $card)
    {
        $this->nodes = array_merge($this->nodes, $card->cards());
        return $this;
    }

    /**
     * 投票
     * @param array $options 选项  name, value
     * @param callable $callback 投票后回调
     * @return Card
     * @throws \Exception
     */
    public function vote($options, $callback)
    {
        $options = array_map(function ($option) {
            $option['items'] = [];
            return $option;
        }, $options);

        $this->voteOptions = array_column($options, null, 'value');
        $this->voteCallBack = $callback;
        $this->voteIndex = count($this->items);
        foreach ($this->voteButtons() as $button) {
            $this->buttons($button);
        }

        return $this;
    }

    /**
     * 投票按钮
     */
    protected function voteButtons()
    {
        $buttons = array_map(function ($option) {
            $content = $option['name'] . '(' . count($option['items']) . '票)';
            return static::eButton($content, ['value' => $option['value']], [$this, 'voteCallBack']);
        }, $this->voteOptions);
        return array_chunk($buttons, 4);
    }

    /**
     * 投票回调
     * @param BotFrame $frame array 消息
     * @throws \Exception
     */
    public function voteCallBack($frame)
    {
        //已经投过了
        if (in_array($frame->user('id'), $this->userHasBeenVote)) {
            return;
        }

        //没有这个选项
        if (!isset($this->voteOptions[$frame->value()])) {
            return;
        }

        array_push($this->voteOptions[$frame->value()]['items'], $frame->user());

        is_callable($this->voteCallBack) and call_user_func($this->voteCallBack, $this->voteOptions);

        //修改卡片
        foreach ($this->voteButtons() as $index => $buttons) {
            $this->setItem($this->voteIndex + $index, $buttons);
        }
        $this->update();

        //记录已经投过票
        array_push($this->userHasBeenVote, $frame->user('id'));
    }

    /**
     * 所有卡片
     * @return array
     */
    public function cards()
    {
        return $this->isEmpty() ? $this->nodes : array_merge($this->nodes, [$this]);
    }

    public function clean()
    {
        $this->nodes = [];
        return parent::clean(); // TODO: Change the autogenerated stub
    }

    /**
     * 输出
     * @return string
     */
    public function out()
    {
        $cards = array_map(function ($card) {
            return [
                'type' => self::TYPE_CARD,
                'theme' => $this->theme,
                'size' => $this->size,
                'color' => $this->color,
                'modules' => array_values($card->items),
            ];
        }, $this->cards());

        return json_encode($cards);
    }

    public function afterSend($newMsg)
    {
        foreach ($this->cards() as $card) {
            //按钮回调
            foreach ($card->buttonCallBackTmp as $callback) {
                Sync::registerCall($newMsg['msg_id'], $callback, $this->buttonTime);
            }

            //倒计时回调
            foreach ($card->downSecondCall as $time => $callback) {
                Timer::after($time, $callback);
            }
        }
    }

    /**
     * 处理卡片点击事件
     */
    public static function handleEvent(RequestEvent $event)
    {
        if (Arr::get($event->frame, 'extra.type') !== 'message_btn_click') {
            return;
        }

        $msgId = Arr::get($event->frame, 'extra.body.msg_id');
        Sync::call($msgId, [$event->frame]);
    }
}
