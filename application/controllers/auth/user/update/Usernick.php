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


class Usernick extends CI_Controller
{
    private $logFilename = 'err_auth_user-update-usernick-';
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
            if(!isset($postMsg['nick'])){
                throw new Exception('must have nick ' . __LINE__, -90001);
            }
            if(!preg_match('/^[_0-9a-z]{1,16}$/i', $postMsg['nick'])) {
                throw new Exception('Nicknames can only be a combination of letters and numbers 1-16   ' . __LINE__, -90002);
            }
            $db = $this->usermodel->init();
            $this->usermodel->saveNick($uin, $postMsg['nick'], $db);
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
}
