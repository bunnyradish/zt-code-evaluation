<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/15
 * Time: 21:21
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");


class Info extends CI_Controller
{
    private $logFilename = 'err_auth_user-query-info-';
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
     * 查看用户的基本信息
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $postMsg = json_decode(file_get_contents('php://input'), true);
            $uin = intval(checkLogin($postMsg));
            $db = $this->usermodel->init();
            $result = $this->usermodel->getUserInfo($uin, $db);
            if(!$result) {
                throw new Exception('user not exist ' . __LINE__, -90009);
            }
            $res['user_id'] = $result['user_id'];
            $res['user_account'] = $result['user_account'];
            $res['user_nick'] = $result['user_nick'];
            $res['user_status'] = $result['user_status'];
            $res['user_portrait'] = $result['user_portrait'];
            $this->msg['data'] = $res;
            $this->msg['msg'] = 'success';

            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }
}
