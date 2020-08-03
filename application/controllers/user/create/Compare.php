<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/17
 * Time: 13:53
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Compare extends CI_Controller
{
    private $logFilename = 'err_user_create-compare-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;
    private $compileErrorData;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('comparemodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     *
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $getData = json_decode(file_get_contents('php://input'), true);
            $uin = checkLogin($getData);
            $this->checkData($getData);
            $db = $this->comparemodel->init();
            if(!$this->checkCompareName($uin, $getData['compare_name'], $db)) {//避免重复名称
                throw new Exception('compare name cannot be duplicate ' . __LINE__, -90009);
            }
            if(!$this->checkMyCodeId($uin, $getData['first_code_id'], $getData['second_code_id'], $db)) {//检查代码是不是自己的
                throw new Exception('code not exist ' . __LINE__, -90007);
            }
            if(intval($getData['first_code_id']) == intval($getData['second_code_id'])) {//两个id相同的代码不能做对拍
                throw new Exception('Two codes id cannot be the same ' . __LINE__, -90008);
            }
            $input_data = $getData['input_data_text'];
            if(strlen($input_data) == 0) {
                throw new Exception('input data code empty ' . __LINE__, -90006);
            }
            if(isset($getData['max_input_group'])){
                if(intval($getData['max_input_group']) > 200 || intval($getData['max_input_group']) < 1) {
                    throw new Exception('group error ' . __LINE__, -90005);
                }
            }
            $input_data = "#include<bits/stdc++.h>
using namespace std;
#define random(a,b) ((a)+rand()%((b)-(a)+1))

stringstream ss;
#define ll long long
int main( int argc, char *argv[] )
{
	int seed=time(NULL);
	if(argc)
	{
		ss.clear();
		ss<<argv[1];
		ss>>seed;
	}
	srand(seed);
	//以上为随机数初始化，请勿修改
	//random(a,b)生成[a,b]的随机整数

	//以下写你自己的数据生成代码
	int myMaxInputGroup;
	cin >> myMaxInputGroup;". "\n".$input_data."\n"."
	

	return 0;
}
";
            $compile = $this->checkCompile($uin, $input_data);//看是否编译成功，注：win因为权限问题，调用不了exec，故直接return true做开发环境
            if($compile) {
                $ci =& get_instance();
                $ci->load->config('my_conf');
                if(strtoupper(substr(PHP_OS,0,3)) == "WIN") {
                    $getData['input_data_path'] = $ci->config->item('win_input_file_path') . $getData['compare_name'] . $uin . 'input.cpp';
                } else {
                    $getData['input_data_path'] = $ci->config->item('linux_input_file_path') . $getData['compare_name'] . $uin . 'input.cpp';
                }
                //$getData['compare_name'] .= '.cpp';
                $this->comparemodel->trans_begin($db);//开事务，让插入数据库和上传生成输入数据的代码到file文件夹是原子的
                $this->comparemodel->insertMyCompare($getData, $uin, $db);
                if (!file_put_contents($getData['input_data_path'], $input_data)) {
                    $this->comparemodel->trans_rollback($db);
                    throw new Exception("write error" . __LINE__, '-90010');
                }
                $this->comparemodel->trans_commit($db);
                $this->msg['msg'] = 'success';
            } else{
                $this->msg['msg'] = 'Compilation failed ';
                $this->msg['code'] = '-90011';
                $this->msg['data'] = $this->compileErrorData;
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
        $tempPath .= $uin . 'compare.cpp';
        $errorPath .= $uin . 'compare.txt';
        if (!file_put_contents($tempPath, $text)) {
            throw new Exception("write error" . __LINE__, '-90010');
        }
        $myExec = 'g++ -o ' . $oName . ' ' . $tempPath . ' 2> ' . $errorPath;
        exec($myExec, $output, $var);
        $this->delFile($tempPath);
        $this->delFile($oName);
        if($var == 0) {
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

    public function checkCompareName($uin, $compareName, $db)
    {
        $result = $this->comparemodel->checkCompareName($uin, $compareName, $db);
        if(count($result)) {
            return false;
        }
        return true;
    }

    public function checkData($data)
    {
        $columns = array( 'compare_name', 'first_code_id', 'second_code_id');
        foreach ($columns as $key) {
            if (!isset($data[$key])) {
                throw new Exception('must have ' . $key . __LINE__, -90000);
            }
        }
    }

    public function delFile($path){ //删除文件
        $delExec = 'rm -f '. $path;
        exec($delExec, $output, $var);//删文件操作可以存在垃圾文件 故不throw
    }

    public function checkMyCodeId($uin, $first_code_id, $second_code_id, $db)
    {
        $first_user_id = $this->comparemodel->checkCode($first_code_id, $db);
        $second_user_id = $this->comparemodel->checkCode($second_code_id, $db);
        $first_user_id = intval($first_user_id);
        $second_user_id = intval($second_user_id);
        if(($first_user_id == $uin) && ($second_user_id == $uin)) {
            return true;
        }
        return false;
    }
}