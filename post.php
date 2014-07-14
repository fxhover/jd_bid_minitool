<?php
header('Content-type:application/json;charset=utf-8');

//cookie存放文件
$cookie_file = './jdcookie.txt';

//CURL请求方法封装
function get_url($params = array(), $header = array()){
    global $cookie_file;
    $httpheader = array(
        'CLIENT-IP:202.103.22.51',
        'X-FORWARDED-FOR:202.103.22.51'
    );
    $httpheader += $header;
    if ($params['url']) {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $params['url'],
            CURLOPT_HEADER => FALSE,
            CURLOPT_HTTPHEADER => $httpheader,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_COOKIEJAR => $cookie_file,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 5.1; rv:27.0) Gecko/20100101 Firefox/27.0'
        );

        if (isset($params['referer'])) {
            $options += array(CURLOPT_REFERER => $params['referer']);
        }
        if (isset($params['https']) && $params['https']) {
            $options += array(CURLOPT_SSL_VERIFYPEER => FALSE);
        }
        if (isset($params['data'])) {
            $options += array(CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $params['data']);
        }
        if (isset($params['use_cookie'])) {
            $options += array(CURLOPT_COOKIEFILE => $cookie_file);
        }
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

//获取商品竞标信息函数
function get_bid_list($id = 0){
    if ($id) {
        $params = array(
            'url' => "http://auction.jd.com/json/paimai/bid_records?t=" . time() . "&dealId={$id}&pageNo=1&pageSize=100",
            'use_cookie' => true
        );
        $result = json_decode(trim(get_url($params)));
        return $result->datas;
    }
}

//竞拍商品函数
function to_bid($id = 0, $price = 0){
    $data = array('status' => false, 'errmsg' => '');
    if ($id && $price) {
        $params = array(
            'url' => "http://auction.jd.com/json/paimai/bid?dealId={$id}&price={$price}&t=" . time(),
            'use_cookie' => true
        );
        $result = json_decode(trim(get_url($params)));
        if ($result->code == 200) {
            $data['status'] = true;
        } else {
            $data['errmsg'] = '由于某种原因，出价失败！';
        }
        return $data;
    }
}

//获取验证码
if (isset($_POST['get_code'])){
  $data = array('status' => false, 'errmsg' => '');
  $params = array(
      'url' => 'https://passport.jd.com/new/login.aspx?ReturnUrl=http%3A%2F%2Fwww.jd.com%2F',
      'https' => true
  );
  
  $page_html = get_url($params, array('X-Requested-With'=>'XMLHttpRequest'));
  $uuid = '';
  if (preg_match('/<input type="hidden" id="uuid" name="uuid" value="([\w-]+)"\/>/', $page_html, $matchs)){
    $uuid = $matchs[1]; 
    $data['uuid'] = $matchs[1]; 
  }
  $rand_key = '';
  $rand_val = '';
  if (preg_match('/<span class="clr"><\/span><input type="hidden" name="([\w-]+)" value="([\w-]+)" \/>/', $page_html, $matchs)){
    //print_r($matchs);
    $rand_key = $matchs[1];
    $rand_val = $matchs[2];
    $data['rand_key'] = $matchs[1];
    $data['rand_val'] = $matchs[2];
  }
  
  //匹配验证码图片
  if (preg_match('/<label\s+class="img">\s+(.*?)\s+<\/label>/si', $page_html, $img)) {
      $img_str = $img[1];
      #$img_str = preg_replace('/src="(.*?)"/', 'src="https://passport.jd.com/emReg/$1"' , $img_str);
      $data['status'] = true;
      $data['html'] = $img_str;
  } else {
      print_r($page_html);
      $data['errmsg'] = '获取验证码失败！';
  }
  echo json_encode($data);
}

//登录
if (isset($_POST['login'])) {
    $uuid = trim($_POST['uuid']);
    $rand_key = trim($_POST['rand_key']);
    $rand_val = trim($_POST['rand_val']);
    $user = trim($_POST['username']);
    $pwd = trim($_POST['password']);
    $purl = trim($_POST['purl']);
    $high_price = intval($_POST['high_price']);

    $purl = explode('/', $purl);
    $id = array_pop($purl);
    
    //将信息记录到一个文件中
    file_put_contents('./user.txt', serialize(array('user'=>$user, 'id'=>$id, 'high_price'=>$high_price)));
    
    $params = array(
        'url' => "https://passport.jd.com/uc/loginService?uuid={$uuid}&ReturnUrl=http%3A%2F%2Fauction.jd.com",
        'data' => array("{$rand_key}" => "{$rand_val}", 'authcode' => '', 'chkRememberMe' => 'on', 'loginname' => $user, 'loginpwd' => $pwd, 'nloginpwd' => $pwd, 'machineCpu' => '', 'machineDisk' => '', 'machineNet' => '', 'uuid' => $uuid),
        'https' => true,
        'use_cookie' => true
    );
    $result = get_url($params);
    $result1 = json_decode(trim(trim($result, '('), ')'));
    
    $data = array('status' => false, 'errmsg' => '');
    //print_r($result1);
    //登录成功
    if (isset($result1->success)) {
        $data['status'] = true;
        $data['id'] = $id;
    } else {
        print_r($params);
        print_r($result1);exit;
        $data['errmsg'] = '登录失败！';
    }
    echo json_encode($data);
    exit;
}

//获取商品竞标信息列表
if(isset($_POST['get_bid_list'])) {
    $id = $_POST['id'];
    $data = array('status' => true, 'list' => array(), 'errmsg' => true);
    $list = get_bid_list($id);
    if ($list) {
        $data['list'] = (array)$list;
    } else {
        $data['status'] = false;
        $data['errmsg'] = '获取竞标信息列表失败！';
    }
    echo json_encode($data);
    exit;
}

//竞拍商品
if(isset($_POST['to_bid'])) {
    $id = $_POST['id'];
    $bid_info = get_bid_list($id);
    $price = $bid_info[0]->price + 1;
    //这里只适用于用户名跟昵称一样的用户
    $user_info = unserialize(file_get_contents('./user.txt'));
    if ($bid_info[0]->userNickName != $user_info['user']){
        if ($user_info['high_price'] != 0) {
            if ($price <= $user_info['high_price']) {
                echo json_encode(to_bid($id, $price));
            } else {
                echo json_encode(array('status'=>true,'code'=>300));
            }
        } else {
            echo json_encode(to_bid($id, $price));
        }
    } else {
        echo json_encode(array('status'=>true));
    }
    exit;
}

