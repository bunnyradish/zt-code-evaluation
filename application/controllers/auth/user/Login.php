<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/14
 * Time: 13:43
 */

header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");

class Login extends CI_Controller
{
    private $logFilename = 'err_auth_user_login-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('usermodel');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $login_msg = json_decode(file_get_contents('php://input'), true);
            $this->checkData($login_msg);
            $db = $this->usermodel->init();
            $result = $this->user_login($login_msg, $db);
            if($result) {
                $userAccount = $login_msg['user_account'];
                $userData = $this->usermodel->getUserDataByAccount($userAccount, $db);
                if(!isset($_SESSION['uin'])) {
                    session_start();
                    $_SESSION['uin'] = $this->encryptionUid($userData['user_id']);
                    $this->msg['data']['uin'] = $userData['user_id'];
                    $this->msg['data']['account'] = $userData['user_account'];
                    $this->msg['data']['user_nick'] = $userData['user_nick'];
                    $this->msg['data']['sid'] = $_SESSION['uin'];
                    $this->msg['msg'] = 'success';
                }
            } else {
                throw new Exception("Account or Password error " . __line__, -90011);
            }
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }

    public function randString()
    {
        $string = "123!@#abc*xyz";
        $res = "";
        $strlen = strlen($string);
        for($i = 0; $i < 32; $i++) {
            $get = rand(0, $strlen-1);
            $res .= $string[$get];
        }
        return $res;
    }

    public function user_login($data, $db)
    {
        $salt = $this->usermodel->getSaltByAccount($data['user_account'], $db);
        $userPsw = sha1($data['user_password'].$salt['salt']);
        $userAccount = $data['user_account'];
        $userPswData = $this->usermodel->getPswByAccount($data, $db);
        $userPswData = $userPswData['user_password'];
        if(!strcmp($userPsw, $userPswData)) {
            return true;
        }
        return false;
    }

    public function checkData($data)
    {
        $columns = array('user_account', 'user_password');
        foreach ($columns as $key) {
            if (!isset($data[$key])) {
                throw new Exception('must have ' . $key . __LINE__, -90000);
            }
        }
    }

    public function encryptionUid($id)
    {
        $result = "";
        $rndString = "ABCDEFGHIJKLMNOPQRSTOVWXYZ";
        for($i = 0; $i < 5; $i++) {
            $result .= $rndString[rand(0, 25)];
        }
        $tmp = $id;
        while(intval($tmp) != 0) {
            $result .= $rndString[$tmp%10];
            $tmp /= 10;
            $tmp = intval($tmp);
        }
        for($i = 0; $i < 5; $i++) {
            $result .= $rndString[rand(0, 25)];
        }
        return $result;
    }
}