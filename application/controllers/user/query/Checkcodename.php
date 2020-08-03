<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/3
 * Time: 20:52
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Checkcodename extends CI_Controller
{
    private $logFilename = 'err_user_query-checkcodename-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;
    private $codeData;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('codemodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $postMsg = json_decode(file_get_contents('php://input'), true);
            $uin = intval(checkLogin($postMsg));
            $this->codeData = $this->input->get();
            $codeName = $this->codeData['code_name'] . '.cpp';
            if (!isset($this->codeData['code_name'])) {
                throw new Exception('must have code_name ' . __LINE__, -90000);
            }
            $db = $this->codemodel->init();
            $result = $this->checkCodeName($uin, $codeName, $db);
            if(!$result){
                $this->msg['code'] = -1;
            }


            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }
    
    public function checkCodeName($uin, $codeName, $db)
    {
        $result = $this->codemodel->checkCodeName($uin, $codeName, $db);
        if(count($result)) {
            return false;
        }
        return true;
    }
}
