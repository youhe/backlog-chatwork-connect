<?php
require './config.php';

class Main
{
  public function __construct()
  {
    $this->_BL_DOMAIN = 'https://'.$BL_DOMAIN_ID.'.backlog.jp/';

    $this->_BL_PROJECT_KEY;
    $this->_CW_ROOM_ID;
    $this->_CW_DEV_ROOM_ID = $CW_DEV_ROOM_ID;

    $this->_dev = false; // 送信先を開発環境にするか
    $this->_input;
    $this->_msg = '';
  }

  public function init()
  {
    // リファラーチェック
    if (!$this->_check_referer()) return;

    // GET取得
    $this->_BL_PROJECT_KEY = $_GET['pkey'];
    $this->_CW_ROOM_ID = $_GET['rid'];

    // POST取得
    $input_json = @file_get_contents('php://input');
    $this->_input = @json_decode($input_json, true);
  }

  private function _check_referer()
  {
    $ref = $_SERVER['HTTP_REFERER'];
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
        $msg.= get_msg_title($input, '課題を追加したコン！');

        $summary = $input['content']['summary'];
        $msg.= 'summary : '.$summary."\n";
        $msg.= $domain.'view/'.$project_key.'-'.$id;
        break;

      case 2:
        $msg.= get_msg_title($input, '課題を更新したコン！');

        $summary = $input['content']['summary'];
        $msg.= 'summary : '.$summary."\n";
        $msg.= $domain.'view/'.$project_key.'-'.$id;
        break;

      case 3:
        $msg.= get_msg_title($input, '課題にコメントしたコン！');

        $comment = $input['content']['comment']['content'];
        $msg.= $comment."\n";
        $msg.= $domain.'view/'.$project_key.'-'.$id;
        break;

      case 5:
        $msg.= get_msg_title($input, 'Wikiを登録したコン！');

        $comment = $input['content']['content'];
        $msg.= $comment."\n";
        $msg.= $domain.'wiki/'.$project_key.'/'.$input['content']['name'];
        break;

      case 6:
        $msg.= get_msg_title($input, 'Wikiを更新したコン！');

        $msg.= $domain.'wiki/'.$project_key.'/'.$input['content']['name'];
        break;

      case 12:
        $this->_dev = true;
        $msg.= get_msg_title($input, 'Gitプッシュしたコン！');
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

  private function _get_msg_title($input, $action = '')
  {
    $created = date('Y/m/d G:i', strtotime($input['created']));
    $created_user_name = $input['createdUser']['name'];

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
    if ($this->_dev;)
      $room_id = $this->_CW_DEV_ROOM_ID;
    else
      $room_id = $this->_CW_ROOM_ID;

    $token = $CW_API_TOKEN;

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
$main->init();
$main->exe();