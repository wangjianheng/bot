<?php

namespace bot\message;

use bot\common\traits\AsArray;
use Illuminate\Support\Arr;
use kaiheila\api\base\Frame;

class BotFrame extends Frame implements \ArrayAccess
{
    use AsArray;

    const TYPE_GROUP = 'GROUP';

    const TYPE_PERSION = 'PERSON';

    const TYPE_BROADCAST = 'BROADCAST';

    public function __construct(Frame $frame = null)
    {
        $frame = $frame ?: new Frame();

        $this->d = $frame->d;

        $this->s = $frame->s;

        $this->sn = $frame->sn;

        $this->access = 'd';
    }

    /**
     * 回调value
     * @return string
     */
    public function value()
    {
        return Arr::get($this, 'extra.body.value', '');
    }

    public function user($key = null)
    {
        $user = Arr::get($this, 'extra.body.user_info', []);
        return Arr::get($user, $key);
    }

    public function send($contents)
    {
        if ($this['channel_type'] == self::TYPE_GROUP) {
            return http()->messageCreate($this['target_id'], $contents);
        }
    }

    public function replay($contents)
    {
        if ($contents instanceof Message) {
            return $contents->send($this['target_id']);
        }

        return http()->messageCreate($this['target_id'], $contents, ['quote' => $this['msg_id']]);
    }
}
