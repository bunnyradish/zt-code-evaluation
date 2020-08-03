<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/15
 * Time: 13:21
 */
function checkLogin($cookieArr)
{
    if (empty($cookieArr['sid'])) {
        throw new Exception('user not login', -90006);
    }
    $sid = $cookieArr['sid'];
    $sid = substr($sid, 5);
    $sid = substr($sid, 0, -5);
    $result = 0;
    $tmp = 1;
    for ($i = 0; $i < strlen($sid); $i++){
        $result += intval(ord($sid[$i]) - ord('A'))*$tmp;
        $tmp *= 10;
    }
    return intval($result);
}
/*
function checkAuth($uin = 0)
{
    $ci =& get_instance();
    $ci->load->config('auth');
    $auth = $ci->config->item('auth');
    $ci->load->model('usermodel');
    $role = $ci->usermodel->getUserInfo($uin);
    //获取当前请求的URI
    $uri = strtolower(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
    if (!isset($auth[$role['role']]) || !isset($auth[$role['role']][$uri])) {
        throw new Exception('user not auth', -90100);
    }
    return true;
}

function checkAuthExpire($uin = 0)
{
    $ci =& get_instance();
    $ci->load->model('usertrymodel');
    $roleinfo = $ci->usertrymodel->getUserInfoTry($uin);

    if ($roleinfo['end_time'] < time() || empty($roleinfo['end_time'])){
        throw new Exception('haved expire', -90101);
    }
    return true;
}
*/




