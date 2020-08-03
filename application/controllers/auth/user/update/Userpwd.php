<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/30
 * Time: 22:46
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");


class Userpwd extends CI_Controller
{
    private $logFilename = 'err_auth_user-update-userpwd-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('usermodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     * 保存用户名
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $postMsg = json_decode(file_get_contents('php://input'), true);
            $data = $this->input->get();
            $uin = intval(checkLogin($postMsg));
            if(!isset($postMsg['oldPwd'])){
                throw new Exception('must have oldPwd ' . __LINE__, -90001);
            }
            if(!isset($postMsg['newPwd'])){
                throw new Exception('must have newPwd ' . __LINE__, -90001);
            }
            $postMsg['uin'] = $uin;
            $db = $this->usermodel->init();
            $this->updatePwd($postMsg, $db);
            $this->msg['data'] = "success";

            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }

    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
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

    public function updatePwd($data, $db)
    {
        $checkRes = $this->usermodel->getUserInfo($data['uin'], $db);
        $oldPwd = $data['oldPwd'];
        $oldPwd = sha1($oldPwd.$checkRes['salt']);
        if(strcmp($oldPwd, $checkRes['user_password'])) {
            throw new Exception('old password error ' . __LINE__, -90008);
        }


        $salt = base64_encode($this->randString());
        $info['newPwd'] = sha1($data['newPwd'].$salt);
        $info['salt'] = $salt;
        $info['uin'] = $data['uin'];
        $codeRes = $this->usermodel->updatePwd($info, $db);
        if ($codeRes < 0) {
            throw new Exception("db error " . __line__, -90007);
        } elseif ($codeRes == 0) {
            throw new Exception('update to database failed ' . __LINE__, -90009);
        }
        return $codeRes;
    }

}
