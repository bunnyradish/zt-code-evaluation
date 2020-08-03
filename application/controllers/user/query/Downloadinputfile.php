<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/30
 * Time: 12:54
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");
class Downloadinputfile extends CI_Controller
{
    private $logFilename = 'err_user_query-downloadinputfile-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('comparemodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     * 下载
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $data = $this->input->get();
            $db = $this->comparemodel->init();
            $sid = $data['user_id'];
            $sid = substr($sid, 5);
            $sid = substr($sid, 0, -5);
            $result = 0;
            $tmp = 1;
            for ($i = 0; $i < strlen($sid); $i++){
                $result += intval(ord($sid[$i]) - ord('A'))*$tmp;
                $tmp *= 10;
            }
            $uin = intval($result);
            $compare_id = $data['compare_id'];
            $id = $data['id'];
            $db = $this->comparemodel->init();
            $path = $this->comparemodel->queryCompareData($uin, $compare_id, $db);
            $path = json_decode($path[0]['compare_data'], true);
            if(isset($path[$id]['Input_path'])) {
                $path = $path[$id]['Input_path'];
            }else {
                return ;
            }

            $this->load->helper('download');//加载插件
            $name = 'input.txt';//下载文件的名字
            $data = file_get_contents($path);//打开文件读取其中的内容
            force_download($name, $data);//下载
        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }
}
