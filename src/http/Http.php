<?php

namespace bot\http;

use bot\common\Config;
use bot\message\Message;
use Illuminate\Support\Arr;
use kaiheila\api\helpers\ApiHelper;
use yii\base\Behavior;

class Http extends Behavior
{
    const CHANNEL_TYPE_TEXT = 1;

    const CHANNEL_TYPE_VOICE = 2;

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
     * @throws \Exception
     */
    public function sender($path = '', $type = 'Bot', $language = 'zh-CN')
    {
        /**
         * @var $sender ApiHelper
         */
        $sender = app(Sender::class, [
            'token' => $this->token,
            'baseUrl' => $this->baseUrl,
            'path' => $path,
            'type' => $type,
            'language' => $language,
        ]);

        return $sender;
    }

    /**
     * 回应消息
     * @param string $msg 消息内容
     * @return mixed
     * @throws \Exception
     */
    public function reply($msg)
    {
        $body = [
            'target_id' => request()->get('target_id'),
            'content' => $msg,
            'quote' => request()->get('msg_id'),
        ];
        return $this->sender(Sender::MESSAGE_CREATE)->setBody($body)->send(Sender::POST);
    }

    /**
     * 当前用户信息
     * @param string $key 键值
     * @return mixed
     * @throws \Exception
     */
    public function user($key = null)
    {
        if (empty($this->user)) {
            $user = $this->sender(Sender::USER_ME)->send(Sender::GET);
            $this->user = $user['data'] ?? [];
        }

        return Arr::get($this->user, $key);
    }

    /**
     * 服务器列表
     * @param int $page 页数
     * @param int $size 页大小
     * @param string $sort 排序
     * @return array
     * @throws \Exception
     */
    public function guildList($page = 1, $size = 10, $sort = 'id')
    {
        $body = [
            'page' => $page,
            'page_size' => $size,
            'sort' => $sort,
        ];

        $response = $this->sender(Sender::GUILD_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 服务器详情
     * @param int $guildId 服务器id
     * @return array
     * @throws \Exception
     */
    public function guildView($guildId)
    {
        $body = [
            'guild_id' => $guildId,
        ];

        $response = $this->sender(Sender::GUILD_VIEW)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 用户列表
     * @param int $guild 服务器
     * @param int $guildId 服务器id
     * @param int $page 页数
     * @param int $size 页大小
     * @param $condition array 过滤条件
     * @return array
     * @throws \Exception
     */
    public function userList($guild, $page = 1, $size = 10, $condition = [])
    {
        $body = array_merge(
            [
                'guild_id' => $guild,
                'page' => $page,
                'page_size' => $size,
            ],
            $condition
        );

        $response = $this->sender(Sender::GUILD_USER_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 修改服务器中用户的昵称
     * @param int $guild 服务器
     * @param int $user 用户
     * @param string $nickname 昵称
     * @return bool
     * @throws \Exception
     */
    public function nickname($guild, $user, $nickname = '')
    {
        $body = [
            'guild_id' => $guild,
            'user_id' => $user,
            'nickname' => $nickname,
        ];

        $response = $this->sender(Sender::NICKNAME)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 离开服务器
     * @param int $guild 服务器
     * @return bool
     * @throws \Exception
     */
    public function leave($guild)
    {
        $body = [
            'guild_id' => $guild,
        ];

        $response = $this->sender(Sender::GUILD_LEAVE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 离开服务器
     * @param int $guild 服务器
     * @param int $target 目标
     * @return bool
     * @throws \Exception
     */
    public function kickOut($guild, $target)
    {
        $body = [
            'guild_id' => $guild,
            'target_id' => $target,
        ];

        $response = $this->sender(Sender::GUILD_KICKOUT)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 闭麦列表
     * @param int $guild 服务器
     * @param string $ret 返回格式
     * @return bool
     * @throws \Exception
     */
    public function muteList($guild, $ret = 'detail')
    {
        $body = [
            'guild_id' => $guild,
            'return_type' => $ret,
        ];

        $response = $this->sender(Sender::MUTE_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 闭麦|静音
     * @param int $guild 服务器
     * @param int $user 用户
     * @param int $type 静音|闭麦
     * @return bool
     * @throws \Exception
     */
    public function muteCreate($guild, $user, $type)
    {
        $body = [
            'guild_id' => $guild,
            'user_id' => $user,
            'type' => $type,
        ];

        $response = $this->sender(Sender::MUTE_CREATE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 解除闭麦|静音
     * @param int $guild 服务器
     * @param int $user 用户
     * @param int $type 静音|闭麦
     * @return bool
     * @throws \Exception
     */
    public function muteDelete($guild, $user, $type)
    {
        $body = [
            'guild_id' => $guild,
            'user_id' => $user,
            'type' => $type,
        ];

        $response = $this->sender(Sender::MUTE_DELETE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 频道列表
     * @param int $guild 服务器
     * @param int $type 类型
     * @param int $page 页数
     * @param int $size 页大小
     * @return array
     * @throws \Exception
     */
    public function channelList($guild, $type = 1, $page = 1, $size = 10)
    {
        $body = [
            'guild_id' => $guild,
            'type' => $type,
            'page' => $page,
            'page_size' => $size,
        ];

        $response = $this->sender(Sender::CHANNEL_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 频道详情
     * @param int $target 目标
     * @return array
     * @throws \Exception
     */
    public function channelView($target)
    {
        $body = [
            'target_id' => $target,
        ];

        $response = $this->sender(Sender::CHANNEL_VIEW)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 创建频道
     * @param int $guild 服务器
     * @param string $name 名称
     * @param array $properties 其他属性
     * @return array
     * @throws \Exception
     */
    public function channelCreate($guild, $name, $properties = [])
    {
        $body = array_merge(
            [
                'guild_id' => $guild,
                'name' => $name,
            ],
            $properties
        );

        $response = $this->sender(Sender::CHANNEL_CREATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 频道更改
     * @param int $channel 频道
     * @param array $properties 其他属性
     * @return bool
     * @throws \Exception
     */
    public function channelUpdate($channel, $properties = [])
    {
        $body = array_merge(
            [
                'channel_id' => $channel,
            ],
            $properties
        );

        $response = $this->sender(Sender::CHANNEL_UPDATE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 删除频道
     * @param int $channel 频道
     * @return bool
     * @throws \Exception
     */
    public function channelDelete($channel)
    {
        $body = [
            'channel_id' => $channel,
        ];

        $response = $this->sender(Sender::CHANNEL_DELETE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 频道用户列表
     * @param int $channel 频道
     * @return array
     * @throws \Exception
     */
    public function channelUserList($channel)
    {
        $body = [
            'channel_id' => $channel,
        ];

        $response = $this->sender(Sender::CHANNEL_USER_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 移动用户
     * @param int $target 目标
     * @param array $users 用户
     * @return bool
     * @throws \Exception
     */
    public function channelUserMove($target, $users)
    {
        $body = [
            'target_id' => $target,
            'user_ids' => $users,
        ];

        $response = $this->sender(Sender::CHANNEL_MOVE_USER)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 频道角色权限
     * @param int $channel 频道
     * @return array
     * @throws \Exception
     */
    public function channelRole($channel)
    {
        $body = [
            'channel_id' => $channel,
        ];

        $response = $this->sender(Sender::CHANNEL_ROLE)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 创建频道角色权限
     * @param int $channel 频道
     * @param int $value   赋值目标
     * @param string $type 类型 role|user
     * @return array
     * @throws \Exception
     */
    public function channelRoleCreate($channel, $value, $type = 'user_id')
    {
        $body = [
            'channel_id' => $channel,
            'value' => $value,
            'type' => $type,
        ];

        $response = $this->sender(Sender::CHANNEL_ROLE_CREATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 修改频道角色权限
     * @param int $channel 频道
     * @param int $value   赋值目标
     * @param string $type 类型 role|user
     * @return array
     * @throws \Exception
     */
    public function channelRoleUpdate($channel, $value, $type = 'user_id', $allow = 0, $deny = 0)
    {
        $body = [
            'channel_id' => $channel,
            'value' => $value,
            'type' => $type,
            'allow' => $allow,
            'deny' => $deny,
        ];

        $response = $this->sender(Sender::CHANNEL_ROLE_UPDATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 删除频道角色权限
     * @param int $channel 频道
     * @param int $value   赋值目标
     * @param string $type 类型 role|user
     * @return bool
     * @throws \Exception
     */
    public function channelRoleDelete($channel, $value, $type = 'user_id')
    {
        $body = [
            'channel_id' => $channel,
            'value' => $value,
            'type' => $type,
        ];

        $response = $this->sender(Sender::CHANNEL_ROLE_DELETE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 消息列表
     * @param int $targe 频道
     * @param array $properties 其他
     * @return array
     * @throws \Exception
     */
    public function messageList($targe, $properties = [])
    {
        $body = array_merge(
            [
                'target_id' => $targe,
            ],
            $properties
        );

        $response = $this->sender(Sender::MESSAGE_LIST)->setQuery($body)->send(Sender::GET);
        $items = Arr::get($response, 'data.items', []);
        return array_map([Message::class, 'instance'], $items);
    }

    /**
     * 发送消息
     * @param int $targe 频道
     * @param string $content 内容
     * @param array $properties 其他
     * @return array
     * @throws \Exception
     */
    public function messageCreate($targe, $content, $properties = [])
    {
        $body = array_merge(
            [
                'target_id' => $targe,
                'content' => $content,
            ],
            $properties
        );

        $response = $this->sender(Sender::MESSAGE_CREATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 修改消息
     * @param string $msgid 消息id
     * @param string $content 内容
     * @param array $properties 其他
     * @return array
     * @throws \Exception
     */
    public function messageUpdate($msgid, $content, $properties = [])
    {
        $body = array_merge(
            [
                'msg_id' => $msgid,
                'content' => $content,
            ],
            $properties
        );

        $response = $this->sender(Sender::MESSAGE_UPDATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 修改消息
     * @param string $msgid 消息id
     * @return bool
     * @throws \Exception
     */
    public function messageDelete($msgid)
    {
        $body = [
            'msg_id' => $msgid,
        ];

        $response = $this->sender(Sender::MESSAGE_DELETE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 回应列表
     * @param string $msgid 消息id
     * @param string $emoji emoji id
     * @return array
     * @throws \Exception
     */
    public function reactionLists($msgid, $emoji)
    {
        $body = [
            'msg_id' => $msgid,
            'emoji' => $emoji,
        ];

        $response = $this->sender(Sender::REACTION_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 添加回应
     * @param string $msgid 消息id
     * @param string $emoji 表情id
     * @return bool
     * @throws \Exception
     */
    public function addReaction($msgid, $emoji)
    {
        $body = [
            'msg_id' => $msgid,
            'emoji' => $emoji,
        ];

        $response = $this->sender(Sender::REACTION_ADD)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 删除回应
     * @param string $msgid 消息id
     * @param string $emoji 表情id
     * @param string $user 用户
     * @return bool
     * @throws \Exception
     */
    public function delReaction($msgid, $emoji, $user = null)
    {
        $body = [
            'msg_id' => $msgid,
            'emoji' => $emoji,
            'user_id' => $user,
        ];

        $response = $this->sender(Sender::REACTION_DEL)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 用户所在语音
     * @param string $guild 服务器
     * @param string $user 用户
     * @return array
     * @throws \Exception
     */
    public function joinedChannel($guild, $user)
    {
        $body = [
            'guild_id' => $guild,
            'user_id' => $user,
        ];

        $response = $this->sender(Sender::JOINED_CHANNEL)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data.items.0', '');
    }

    /**
     * 私聊会话列表
     * @return array
     * @throws \Exception
     */
    public function chatList()
    {
        $body = [];

        $response = $this->sender(Sender::USER_CHAT_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data.items', []);
    }

    /**
     * 会话详情
     * @param string $code 会话code
     * @return array
     * @throws \Exception
     */
    public function chatView($code)
    {
        $body = [
            'chat_code' => $code,
        ];

        $response = $this->sender(Sender::USER_CHAT_VIEW)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 创建会话
     * @param string $target 目标用户
     * @return array
     * @throws \Exception
     */
    public function chatCreate($target)
    {
        $body = [
            'target_id' => $target,
        ];

        $response = $this->sender(Sender::USER_CHAT_CREATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 删除会话
     * @param string $code 会话code
     * @return bool
     * @throws \Exception
     */
    public function chatDelete($code)
    {
        $body = [
            'chat_code' => $code,
        ];

        $response = $this->sender(Sender::USER_CHAT_DELETE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 私聊消息列表 by code
     * @param string $code 会话code
     * @param  array $properties 其他
     * @return array
     * @throws \Exception
     */
    public function directMsgListByCode($code, $page = 1, $size = 50, $properties = [])
    {
        $body = array_merge(
            [
                'chat_code' => $code,
                'page' => $page,
                'page_size' => $size,
            ],
            $properties
        );

        $response = $this->sender(Sender::DIRECT_MESSAGE_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data.items', []);
    }

    /**
     * 私聊消息列表 by target
     * @param string $targe 目标
     * @param  array $properties 其他
     * @return array
     * @throws \Exception
     */
    public function directMsgListByTarget($targe, $page = 1, $size = 50, $properties = [])
    {
        $body = array_merge(
            [
                'target_id' => $targe,
                'page' => $page,
                'page_size' => $size,
            ],
            $properties
        );

        $response = $this->sender(Sender::DIRECT_MESSAGE_LIST)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data.items', []);
    }

    /**
     * 发送私聊消息 by targe
     * @param string $targe 目标
     * @param string $content 内容
     * @param  array $properties 其他
     * @return array
     * @throws \Exception
     */
    public function directMsgCreateByTarget($targe, $content, $properties = [])
    {
        $body = array_merge(
            [
                'target_id' => $targe,
                'content' => $content,
            ],
            $properties
        );

        $response = $this->sender(Sender::DIRECT_MESSAGE_CREATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 发送私聊消息 by code
     * @param string $targe 目标
     * @param string $content 内容
     * @param  array $properties 其他
     * @return array
     * @throws \Exception
     */
    public function directMsgCreateByCode($code, $content, $properties = [])
    {
        $body = array_merge(
            [
                'chat_code' => $code,
                'content' => $content,
            ],
            $properties
        );

        $response = $this->sender(Sender::DIRECT_MESSAGE_CREATE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 更新私聊消息
     * @param string $msg 消息
     * @param string $content 内容
     * @param  array $properties 其他
     * @return bool
     * @throws \Exception
     */
    public function directMsgUpdate($msg, $content, $properties = [])
    {
        $body = array_merge(
            [
                'msg_id' => $msg,
                'content' => $content,
            ],
            $properties
        );

        $response = $this->sender(Sender::DIRECT_MESSAGE_UPDATE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 更新私聊消息
     * @param string $msg 消息
     * @param string $content 内容
     * @param  array $properties 其他
     * @return bool
     * @throws \Exception
     */
    public function directMsgDelete($msg)
    {
        $body = [
            'msg_id' => $msg,
        ];

        $response = $this->sender(Sender::DIRECT_MESSAGE_DELETE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 添加回应
     * @param string $msg 消息
     * @param string $emoji 表情
     * @return bool
     * @throws \Exception
     */
    public function directMsgAddReaction($msg, $emoji)
    {
        $body = [
            'msg_id' => $msg,
            'emoji' => $emoji,
        ];

        $response = $this->sender(Sender::DIRECT_MESSAGE_ADD_REACTION)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 删除回应
     * @param string $msg 消息
     * @param string $emoji 表情
     * @param string $user 用户
     * @return bool
     * @throws \Exception
     */
    public function directMsgDelReaction($msg, $emoji, $user = null)
    {
        $body = [
            'msg_id' => $msg,
            'emoji' => $emoji,
            'user_id' => $user,
        ];

        $response = $this->sender(Sender::DIRECT_MESSAGE_DEL_REACTION)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    /**
     * 目标用户
     * @param string $guild 服务器
     * @param string $user 用户
     * @return bool
     * @throws \Exception
     */
    public function userView($user, $guild = null)
    {
        $body = [
            'user_id' => $user,
            'guild_id' => $guild,
        ];

        $response = $this->sender(Sender::USER_VIEW)->setQuery($body)->send(Sender::GET);
        return Arr::get($response, 'data', []);
    }

    /**
     * 下线
     * @return bool
     * @throws \Exception
     */
    public function offLine()
    {
        $body = [];

        $response = $this->sender(Sender::USER_OFFLINE)->setBody($body)->send(Sender::POST);
        return $this->isSuccess($response);
    }

    public function channelInvite($channel, $duration = null, $times = -1)
    {
        $body = [
            'channel_id' => $channel,
            'duration' => $duration,
            'setting_times' => $times,
        ];
        $response = $this->sender(Sender::CREATE_INVITE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    public function guildInvite($channel, $duration = 1800, $times = -1)
    {
        $body = [
            'guild_id' => $channel,
            'duration' => $duration,
            'setting_times' => $times,
        ];
        $response = $this->sender(Sender::CREATE_INVITE)->setBody($body)->send(Sender::POST);
        return Arr::get($response, 'data', []);
    }

    /**
     * 是否成功
     * @param array $response 接口相应
     * @return bool
     */
    protected function isSuccess($response)
    {
        return Arr::get($response, 'code') === Sender::SUCCESS_CODE;
    }
}
