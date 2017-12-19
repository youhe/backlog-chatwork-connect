<?php
class Config
{
    public function __construct()
    {
        // backlog スペースID
        $this->_attrs['bl_domain_id'] = '';

        // chatwork APIトークン
        $this->_attrs['cw_api_token'] = '';

        // chatwork 開発用チャットルーム
        // エラー、未登録のメッセージタイプなどの通知用
        $this->_attrs['cw_dev_room_id'] = 0;
    }

    public function get($key)
    {
        return $this->_attrs[$key];
    }
}