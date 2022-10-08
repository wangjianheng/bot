<?php

namespace bot\http;

use kaiheila\api\helpers\ApiHelper;

class Sender extends ApiHelper
{
    const AIP_VERSION = '/api/v3';

    /**
     * 用户相关
     */
    const USER_ME = self::AIP_VERSION . '/user/me';            //当前用户

    const USER_VIEW = self::AIP_VERSION . '/user/view';        //目标用户

    const USER_OFFLINE = self::AIP_VERSION . '/user/offline';  //下线用户

    /**
     * 服务器相关
     */
    const GUILD_LIST = self::AIP_VERSION . '/guild/list';          //服务器列表

    const GUILD_VIEW = self::AIP_VERSION . '/guild/view';          //服务器详情

    const GUILD_USER_LIST = self::AIP_VERSION . '/guild/user-list';      //用户列表

    const NICKNAME = self::AIP_VERSION . '/guild/nickname';        //修改昵称

    const GUILD_LEAVE = self::AIP_VERSION . '/guild/leave';        //离开服务器

    const GUILD_KICKOUT = self::AIP_VERSION . '/guild/kickout';    //踢出服务器

    const MUTE_LIST = self::AIP_VERSION . '/guild-mute/list';      //静音列表

    const MUTE_CREATE = self::AIP_VERSION . '/guild-mute/create';  //静音

    const MUTE_DELETE = self::AIP_VERSION . '/guild-mute/delete';  //取消静音

    /**
     * 频道相关
     */
    const CHANNEL_LIST = self::AIP_VERSION . '/channel/list';               //频道列表

    const CHANNEL_VIEW = self::AIP_VERSION . '/channel/view';               //频道详情

    const CHANNEL_CREATE = self::AIP_VERSION . '/channel/create';           //频道创建

    const CHANNEL_UPDATE = self::AIP_VERSION . '/channel/update';           //频道更改

    const CHANNEL_DELETE = self::AIP_VERSION . '/channel/delete';           //频道更改

    const CHANNEL_USER_LIST = self::AIP_VERSION . '/channel/user-list';     //用户列表

    const CHANNEL_MOVE_USER = self::AIP_VERSION . '/channel/move-user';     //移动用户

    const CHANNEL_ROLE = self::AIP_VERSION . '/channel-role/index';         //角色权限

    const CHANNEL_ROLE_CREATE = self::AIP_VERSION . '/channel-role/create'; //创建权限

    const CHANNEL_ROLE_UPDATE = self::AIP_VERSION . '/channel-role/update'; //修改权限

    const CHANNEL_ROLE_DELETE = self::AIP_VERSION . '/channel-role/delete'; //删除权限

    /**
     * 频道消息相关
     */
    const MESSAGE_LIST = self::AIP_VERSION . '/message/list';               //消息列表

    const MESSAGE_CREATE = self::AIP_VERSION . '/message/create';           //创建消息

    const MESSAGE_UPDATE = self::AIP_VERSION . '/message/update';           //更新消息

    const MESSAGE_DELETE = self::AIP_VERSION . '/message/delete';           //删除消息

    const REACTION_LIST = self::AIP_VERSION . '/message/reaction-list';     //表情回应列表

    const REACTION_ADD = self::AIP_VERSION . '/message/add-reaction';       //添加回应

    const REACTION_DEL = self::AIP_VERSION . '/message/delete-reaction';    //删除回应

    const JOINED_CHANNEL = self::AIP_VERSION . '/channel-user/get-joined-channel';  //用户所在语音频道

    /**
     * 私聊会话相关
     */
    const USER_CHAT_LIST = self::AIP_VERSION . '/user-chat/list';          //私聊会话列表

    const USER_CHAT_VIEW = self::AIP_VERSION . '/user-chat/view';          //私聊会话详情

    const USER_CHAT_CREATE = self::AIP_VERSION . '/user-chat/create';      //私聊会话创建

    const USER_CHAT_DELETE = self::AIP_VERSION . '/user-chat/delete';      //私聊会话删除

    /**
     * 私聊信息相关
     */
    const DIRECT_MESSAGE_LIST = self::AIP_VERSION . '/direct-message/list';     //私聊消息列表

    const DIRECT_MESSAGE_CREATE = self::AIP_VERSION . '/direct-message/create'; //发送私聊

    const DIRECT_MESSAGE_UPDATE = self::AIP_VERSION . '/direct-message/update'; //更新私聊

    const DIRECT_MESSAGE_DELETE = self::AIP_VERSION . '/direct-message/delete'; //删除私聊

    const DIRECT_MESSAGE_ADD_REACTION = self::AIP_VERSION . '/direct-message/add-reaction'; //添加回应

    const DIRECT_MESSAGE_DEL_REACTION = self::AIP_VERSION . '/direct-message/delete-reaction'; //添加回应

    /**
     * 邀请相关
     */
    const CREATE_INVITE = self::AIP_VERSION . '/invite/create';     //创建邀请

    const SUCCESS_CODE = 0;
}
