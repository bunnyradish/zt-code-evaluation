<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/15
 * Time: 17:36
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Code extends CI_Controller
{
    private $logFilename = 'err_user_delete-code-';
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
     * 删除数据库记录和删掉文件是原子性的
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $postMsg = json_decode(file_get_contents('php://input'), true);
            $uin = checkLogin($postMsg);
            $data = $this->input->get();
            $db = $this->codemodel->init();
            $code_id = $data['code_id'];
            if($this->checkCode($code_id, $uin, $db)){ //检查这个代码是否是这个用户的

                if($this->checkCompare($code_id, $db))//检查是否有正在运行的有这个代码的对拍
                {
                    throw new Exception("There is a compare running using this code" . __LINE__, '-90066');
                }

                $this->codemodel->trans_begin($db);//开事务，删掉数据库记录和删掉文件夹里的code是原子的
                $path = $this->codemodel->getPathByCodeId($code_id, $db);
                $resDel = $this->deleteCode($code_id, $uin, $db);
                if(!$resDel) {
                    $this->codemodel->trans_rollback($db);
                    throw new Exception("delete code database failed" . __LINE__, '-90010');
                }
                if(!unlink($path)) {
                    $this->codemodel->trans_rollback($db);
                    throw new Exception("delete code failed", '-90012');
                }
                $this->codemodel->trans_commit($db);
                $this->msg['msg'] = 'success';

            } else {
                throw new Exception("code not exist " . __line__, -90005);
            }

            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }

    public function checkCode($code_id, $uin, $db)
    {
        $user_id = $this->codemodel->checkCode($code_id, $db);
        $user_id = intval($user_id);
        if($user_id == $uin) {
            return true;
        }
        return false;
    }

    public function deleteCode($code_id, $uin, $db)
    {
        if($this->codemodel->deleteCode($code_id, $uin, $db)) {
            return true;
        }
        else {
            return false;
        }
    }

    public function checkCompare($code_id, $db)
    {
        $result = $this->codemodel->checkCompareRunning($code_id, $db);
        foreach ($result as $res) {
            if($res['version'] == '1') {
                return true;
            }
        }
        return false;
    }

}