<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/24
 * Time: 16:52
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Code extends CI_Controller
{
    private $logFilename = 'err_user_update-code-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;
    private $codeData;
    private $compileErrorData;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('codemodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     * 传入text代码，代码先编译，看是否可编译
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $postMsg = json_decode(file_get_contents('php://input'), true);
            $uin = checkLogin($postMsg);
            $this->codeData = $this->input->get();
            $codeName = $postMsg['code_name'];
            $codeId = $postMsg['code_id'];
            if(!isset($postMsg['code_name'])) {
                throw new Exception('must have code_name ' . __LINE__, -90000);
            }
            $db = $this->codemodel->init();
            if($this->checkCode($codeId, $uin, $db)){ //检查这个代码是否是这个用户的
                if($this->checkCompare($codeId, $db))//检查是否有正在运行的有这个代码的对拍
                {
                    throw new Exception("There is a compare running using this code" . __LINE__, '-90066');
                }
                if(!$this->checkCodeName($uin, $codeName, $codeId, $db)) {//避免重复名称
                    throw new Exception('code name cannot be duplicate ' . __LINE__, -90009);
                }
                //$code_msg = $this->input->raw_input_stream;
                $code_msg = $postMsg['code_text'];
                if(!strlen($code_msg)) {
                    throw new Exception("must have code" . __LINE__, '-90001');
                }
                $text = $code_msg;
                $compile = $this->checkCompile($uin, $text);//看是否编译成功，注：win因为权限问题，调用不了exec，故直接return true做开发环境
                if($compile) {
                    $ci =& get_instance();
                    $ci->load->config('my_conf');
                    if(strtoupper(substr(PHP_OS,0,3)) == "WIN") {
                        $this->codeData['path'] = $ci->config->item('win_code_file_path') . $postMsg['code_name'] . $uin . '.cpp';
                    } else {
                        $this->codeData['path'] = $ci->config->item('linux_code_file_path') . $postMsg['code_name'] . $uin . '.cpp';
                    }
                    $this->codeData['code_name'] = $postMsg['code_name'];
                    $this->codemodel->trans_begin($db);//开事务，让修改数据库和删除之前的代码与上传新代码到file文件夹是原子的
                    $oldPath = $this->codemodel->getPathByCodeId($codeId, $db);
                    $this->codemodel->updateMyCode($this->codeData, $code_msg, $codeId, $db);
                    if(!unlink($oldPath)) {
                        $this->codemodel->trans_rollback($db);
                        throw new Exception("delete code failed", '-90012');
                    }
                    if (!file_put_contents($this->codeData['path'], $text)) {
                        $this->codemodel->trans_rollback($db);
                        throw new Exception("write error" . __LINE__, '-90010');
                    }
                    $this->codemodel->trans_commit($db);
                    $this->msg['msg'] = 'success';
                } else{
                    $this->msg['msg'] = 'Compilation failed ';
                    $this->msg['code'] = '-90011';
                    $this->msg['data'] = $this->compileErrorData;
                }
            }
            else {
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

    public function checkCompile($uin, $text)
    {
        $ci =& get_instance();
        $ci->load->config('my_conf');
        if(strtoupper(substr(PHP_OS,0,3)) == "WIN") {
            $tempPath = $ci->config->item('win_text_temp_path');
            $errorPath = $ci->config->item('win_text_temp_path');
            return true;
        } else {
            $tempPath = $ci->config->item('linux_text_temp_path');
            $errorPath = $ci->config->item('linux_text_temp_path');
        }
        $oName = $tempPath . $uin;
        $tempPath .= $uin . '.cpp';
        $errorPath .= $uin . '.txt';
        if (!file_put_contents($tempPath, $text)) {
            throw new Exception("write error" . __LINE__, '-90010');
        }
        $myExec = 'g++ -o ' . $oName . ' ' . $tempPath . ' 2> ' . $errorPath;
        exec($myExec, $output, $var);
        $rmTemp = 'rm -f ' . $tempPath . ' && rm -f ' . $oName;
        exec($rmTemp);
        if($var == 0) {
            $this->compileErrorData = file_get_contents($errorPath);
            exec('rm -f ' . $errorPath);
            return true;
        } else {
            $this->compileErrorData = file_get_contents($errorPath);
            exec('rm -f ' . $errorPath);
            $this->compileErrorData = str_replace($tempPath, "  compile error", $this->compileErrorData);
            if(empty($this->compileErrorData)) {
                throw new Exception("read error" . __LINE__, '-90010');
            }
            return false;
        }
    }

    public function checkCodeName($uin, $codeName, $codeId, $db)
    {
        $result = $this->codemodel->checkCodeNameExceptThis($uin, $codeName, $codeId, $db);
        if(count($result)) {
            return false;
        }
        return true;
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