<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/8
 * Time: 11:48
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Checkcomparename extends CI_Controller
{
    private $logFilename = 'err_user_query-checkcomparename-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;
    private $compareData;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('comparemodel');
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
            $this->compareData = $this->input->get();
            $compareName = $this->compareData['compare_name'] . '.cpp';
            if (!isset($this->compareData['compare_name'])) {
                throw new Exception('must have compare_name ' . __LINE__, -90000);
            }
            $db = $this->comparemodel->init();
            $result = $this->checkCompareName($uin, $compareName, $db);
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

    public function checkCompareName($uin, $compareName, $db)
    {
        $result = $this->comparemodel->checkCompareName($uin, $compareName, $db);
        if(count($result)) {
            return false;
        }
        return true;
    }
}
