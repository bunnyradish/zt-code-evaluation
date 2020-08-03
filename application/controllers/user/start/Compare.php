<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/1
 * Time: 16:04
 */


if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Compare extends CI_Controller
{
    private $logFilename = 'err_user_start-compare-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;
    private $StartTime = 0;
    private $StopTime = 0;
    private $runTime = 0;
    private $runMemory = 0;
    private $outputData;
    private $resage;
    private $MAXTIME = 20000;//超过这个时间就直接关闭脚本 返回TLE

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
            $db = $this->comparemodel->init();
            if(!isset($postMsg['compare_id'])) {
                throw new Exception("compare not exist " . __line__, -90005);
            }
            $compare_id = $postMsg['compare_id'];
            if($this->checkCompare($compare_id, $uin, $db)){ //检查这个对拍是否是这个用户的
                $this->addRunCompare($compare_id, $uin, $db);
            } else {
                throw new Exception("compare not exist " . __line__, -90005);
            }
            $this->msg['msg'] = 'success';

            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }

    public function checkCompare($compare_id, $uin, $db)//检查这个对拍是否是本人的
    {
        $user_id = $this->comparemodel->checkCompare($compare_id, $db);
        $user_id = intval($user_id);
        if($user_id == $uin) {
            return true;
        }
        return false;
    }


    public function addRunCompare($compare_id, $uin, $db)
    {
        $version = intval($this->comparemodel->checkVersion($compare_id, $uin, $db));
        if($version == -1) {
            $this->comparemodel->addRunCompare($compare_id, $uin, $db);
        }
        else if($version == 1){//检查这个对拍是否在运行
            throw new Exception("compare is running " . __line__, -90022);
        }
        else if($version == 2){//检查这个对拍是否在运行
            throw new Exception("This compare has been completed " . __line__, -90023);
        }
    }
}

