<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/11
 * Time: 15:32
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Comparedetail extends CI_Controller
{
    private $logFilename = 'err_user_query-comparedetail-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('comparemodel');
        $this->load->model('codemodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     * 查找代码的详情
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $postMsg = json_decode(file_get_contents('php://input'), true);
            $uin = intval(checkLogin($postMsg));
            $data = $this->input->get();
            $db = $this->comparemodel->init();
            if(!isset($postMsg['compare_id'])) {
                throw new Exception("compare not exist " . __line__, -90005);
            }
            $compare_id = $postMsg['compare_id'];
            if($this->checkCompare($compare_id, $uin, $db)){ //检查这个对拍是否是这个用户的
                $result = $this->comparemodel->queryCompareDetail($uin, $compare_id, $db);
                $result = $result[0];
                $path = $result['input_data_path'];
                unset($result['input_data_path']);
                $result['input_data'] = file_get_contents($path);
                $result['input_data'] = substr($result['input_data'], 437, -18);
                $codeDb = $this->codemodel->init();
                $result['first_code_name'] = $this->codemodel->queryCodeDetail($uin, $result['first_code_id'], $codeDb);
                $result['first_code_name'] = $result['first_code_name']['code_name'];
                $result['second_code_name'] = $this->codemodel->queryCodeDetail($uin, $result['second_code_id'], $codeDb);
                $result['second_code_name'] = $result['second_code_name']['code_name'];
                $this->msg['data'] = $result;
                $this->msg['msg'] = 'success';
            } else {
                throw new Exception("compare not exist " . __line__, -90005);
            }

            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }
    public function checkCompare($compare_id, $uin, $db)
    {
        $user_id = $this->comparemodel->checkCompare($compare_id, $db);
        $user_id = intval($user_id);
        if($user_id == $uin) {
            return true;
        }
        return false;
    }
}
