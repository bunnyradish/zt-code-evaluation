<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/11
 * Time: 12:22
 */

header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");

class Register extends CI_Controller
{
    private $logFilename = 'err_auth_user_register-';
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
            $register_msg = json_decode(file_get_contents('php://input'), true);
            $this->checkData($register_msg);
            $db = $this->usermodel->init();
            $check_account = $this->checkAccount($register_msg, $db);
            if(!$check_account) {
                throw new Exception("Account has been registered " . __line__, -90001);
            }
            $result = $this->userRegister($register_msg, $db);
            if($result) {
                if(!isset($_SESSION['uin'])) {
                    session_start();
                    $_SESSION['uin'] = $this->encryptionUid($result);
                    $this->msg['data']['uin'] = $result;
                    $this->msg['data']['account'] = $register_msg['user_account'];
                    $this->msg['data']['user_nick'] = $register_msg['user_nick'];
                    $this->msg['data']['sid'] = $_SESSION['uin'];
                    $this->msg['msg'] = 'success';
                }
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

    public function userRegister($data, $db)
    {
        $salt = base64_encode($this->randString());
        $data['user_password'] = sha1($data['user_password'].$salt);
        $data['salt'] = $salt;
        $codeRes = $this->usermodel->userRegister($data, $db);
        if ($codeRes < 0) {
            throw new Exception("db error " . __line__, -90007);
        } elseif ($codeRes == 0) {
            throw new Exception('insert to database failed ' . __LINE__, -90009);
        }
        return $codeRes;
    }

    public function checkAccount($data, $db)
    {
        $codeRes = $this->usermodel->checkAccount($data, $db);
        if($codeRes == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function checkData($data)
    {
        $columns = array('user_account', 'user_password', 'user_nick');
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