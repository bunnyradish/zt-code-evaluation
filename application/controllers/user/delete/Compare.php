<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/6/2
 * Time: 13:16
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Compare extends CI_Controller
{
    private $logFilename = 'err_user_delete-compare-';
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
            $db = $this->comparemodel->init();
            $compare_id = $data['compare_id'];
            if($this->checkCompare($compare_id, $uin, $db)){ //检查这个对拍是否是这个用户的

                list($delRun, $running) = $this->checkCompareRunning($compare_id, $db);
                if($running)//检查是否有正在运行的有这个代码的对拍
                {
                    throw new Exception("There is a compare running " . __LINE__, '-90066');
                }
                $this->comparemodel->trans_begin($db);//开事务，删掉数据库记录和删掉文件夹里的compare是原子的
                if($delRun) {
                    if(!$this->comparemodel->delRunCompare($compare_id, $uin, $db)) {
                        $this->comparemodel->trans_rollback($db);
                        throw new Exception("delete run_compare database failed" . __LINE__, '-90010');
                    }
                    if(!$this->deldir("/var/www/html/zt-code-evaluation/compareGo/".$compare_id)) {
                        $this->comparemodel->trans_rollback($db);
                        throw new Exception("delete run file failed" . __LINE__, '-90010');
                    }
                }
                if(!$this->comparemodel->delEvaCompare($compare_id, $uin, $db)) {
                    $this->comparemodel->trans_rollback($db);
                    throw new Exception("delete eva_compare database failed" . __LINE__, '-90010');
                }
                $this->comparemodel->trans_commit($db);
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

    public function deleteCode($code_id, $uin, $db)
    {
        if($this->codemodel->deleteCode($code_id, $uin, $db)) {
            return true;
        }
        else {
            return false;
        }
    }

    public function checkCompareRunning($compare_id, $db)//后面是running 前面是run_compare中是否有了
    {
        $result = $this->comparemodel->checkCompareRunning($compare_id, $db);
        if(isset($result[0])) {
            if($result[0]['version'] == 1) {
                return [false, true];
            }
            return [true, false];
        }
        return [false, false];
    }

    public function deldir($dir) {
        //先删除目录下的文件：
        $dh=opendir($dir);
        while ($file=readdir($dh)) {
            if($file!="." && $file!="..") {
                $fullpath=$dir."/".$file;
                if(!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }

        closedir($dh);
        //删除当前文件夹：
        if(rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

}