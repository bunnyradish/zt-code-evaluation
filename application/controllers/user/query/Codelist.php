<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/15
 * Time: 19:33
*/
if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Codelist extends CI_Controller
{
    private $logFilename = 'err_user_query-codelist-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('codemodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     * 分页查找代码列表  每页10个 传参为：时间（上页末尾数据的修改时间）  给的数据：按照时间逆序来 往下数10个
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
            $db = $this->codemodel->init();
            $count = 20;
            if(empty($data['start_id'])) {
                $start_id = 0;
            }else {
                $start_id = $data['start_id'];
            }
            $result = $this->codemodel->queryCodeList($uin, $start_id, $count, $db);
            $this->msg['data'] = $result;
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
