<?php
require './config.php';

class Main
{
    public function __construct()
    {
        $this->_config = null;

        $this->_BL_DOMAIN = '';
        $this->_BL_PROJECT_KEY = '';

        $this->_CW_API_TOKEN = '';
        $this->_CW_ROOM_ID = '';
        $this->_CW_DEV_ROOM_ID = '';

        // 送信先を開発環境にするか
        $this->_dev = false;
        $this->_input = null;
        $this->_msg = '';
    }

    public function init($input)
    {
        // リファラーチェック
        if (!$this->_check_referer()) return;

        $this->_config = new Config();

        $bl_domain_id = $this->_config->get('bl_domain_id');
        $this->_BL_DOMAIN = 'https://'.$bl_domain_id.'.backlog.jp/';
        $this->_BL_PROJECT_KEY = $_GET['pkey'];

        $this->_CW_API_TOKEN = $this->_config->get('cw_api_token');
        $this->_CW_ROOM_ID = $_GET['rid'];
        $this->_CW_DEV_ROOM_ID = $this->_config->get('cw_dev_room_id');

        // POST取得
        $input_json = @file_get_contents($input);
        $this->_input = @json_decode($input_json, true);
    }

    private function _check_referer()
    {
        // $ref = $_SERVER['HTTP_REFERER'];
        $this->_dev = true;
        $this->_msg = $_SERVER['HTTP_REFERER'];
        $this->_send_msg();
        $this->_dev = false;

        return true;
    }

    public function exe()
    {
        // メッセージ作成  
        $this->_msg = $this->_get_msg();
        // メッセージ送信
        $this->_send_msg();
    }


    private function _get_msg()
    {
        $input = $this->_input;
        $domain = $this->_BL_DOMAIN;
        $project_key = $this->_BL_PROJECT_KEY;

        $type = $input['type'];
        $id = $input['content']['key_id'];

        $msg = '';
        switch($type) {

            case 1:
                $msg.= $this->_get_msg_title('課題を追加したコン！');

                $summary = $input['content']['summary'];
                $msg.= 'summary : '.$summary."\n";
                $msg.= $domain.'view/'.$project_key.'-'.$id;
                break;

            case 2:
                $msg.= $this->_get_msg_title('課題を更新したコン！');

                $summary = $input['content']['summary'];
                $msg.= 'summary : '.$summary."\n";
                $msg.= $domain.'view/'.$project_key.'-'.$id;
                break;

            case 3:
                $msg.= $this->_get_msg_title('課題にコメントしたコン！');

                $comment = $input['content']['comment']['content'];
                $msg.= $comment."\n";
                $msg.= $domain.'view/'.$project_key.'-'.$id;
                break;

            case 5:
                $msg.= $this->_get_msg_title('Wikiを登録したコン！');

                $comment = $input['content']['content'];
                $msg.= $comment."\n";
                $msg.= $domain.'wiki/'.$project_key.'/'.$input['content']['name'];
                break;

            case 6:
                $msg.= $this->_get_msg_title('Wikiを更新したコン！');

                $msg.= $domain.'wiki/'.$project_key.'/'.$input['content']['name'];
                break;

            case 12:
                $this->_dev = true;
                $msg.= $this->_get_msg_title('Gitプッシュしたコン！');
                $comment = $input['content']['revisions'][0]['comment'];
                $msg.= $comment."\n";
                $msg.= $domain.'view/'.$project_key.'-'.$id;
                break;

            default:
                $this->_dev = true;
                $msg.= '[title]';
                $msg.= $type.' は登録されていないタイプです。';
                $msg.= '[/title]';
                $msg.= json_encode($input);
        }

        $tMeg = '[info]'.$msg.'[/info]';
        return $tMeg;
    }

    private function _get_msg_title($action)
    {
        $created = date('Y/m/d G:i', strtotime($this->_input['created']));
        $created_user_name = $this->_input['createdUser']['name'];

        $title = '';
        $title.= '[title]';
        $title.= $created.'   ';
        $title.= $created_user_name.' が';
        $title.= $action;
        $title.= '[/title]';
        return $title;
    }

    private function _send_msg()
    {
        if ($this->_dev)
            $room_id = $this->_CW_DEV_ROOM_ID;
        else
            $room_id = $this->_CW_ROOM_ID;

        $token = $this->_CW_API_TOKEN;

        header('Content-type: text/html; charset=utf-8');
        $data = array('body' => $this->_msg);
        $opt = array(
            CURLOPT_URL => 'https://api.chatwork.com/v2/rooms/'.$room_id.'/messages',
            CURLOPT_HTTPHEADER => array('X-ChatWorkToken:'.$token),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => http_build_query($data, '', '&')
        );

        $ch = curl_init();
        curl_setopt_array($ch, $opt);
        curl_exec($ch);
        curl_close($ch);
    }
}

$main = new Main();
$main->init('php://input');
$main->exe();