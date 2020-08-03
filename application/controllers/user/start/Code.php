<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/16
 * Time: 10:56
 */
/*
 *
 *
 *
 * 究极大坑 白搞一天！
 *
 *
 * pcntl_fork 只能在cli模式下使用，否则会出现意料之外的错误！
 *
 * */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");

class Code extends CI_Controller
{
    private $logFilename = 'err_user_start-code-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;
    private $StartTime = 0;
    private $StopTime = 0;
    private $runTime = 0;
    private $runMemory = 0;
    private $outputData;
    private $resage;
    private $MAXTIME = 15000;//超过这个时间就直接关闭脚本 返回TLE

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('codemodel');
        $this->load->helper('common');
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     * 对text进行一组数据的运行并返回结果
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
            $inputData = $postMsg['input_text'];
            //$inputData = $this->input->raw_input_stream;
            $db = $this->codemodel->init();
            if(!isset($data['code_id'])) {
                throw new Exception("code not exist " . __line__, -90005);
            }
            $code_id = $data['code_id'];
            if($this->checkCode($code_id, $uin, $db)){ //检查这个代码是否是这个用户的
                $this->startCode($inputData, $code_id, $db);

            } else {
                throw new Exception("code not exist " . __line__, -90005);
            }
            $this->msg['data']['output'] = $this->outputData;
            $this->msg['data']['runTime'] = $this->runTime;
            $this->msg['data']['runMemory'] = $this->runMemory;
            $this->msg['msg'] = 'success';

            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }

    public function checkCode($code_id, $uin, $db)//检查这个代码是否是本人的
    {
        $user_id = $this->codemodel->checkCode($code_id, $db);
        $user_id = intval($user_id);
        if($user_id == $uin) {
            return true;
        }
        return false;
    }

    /*计时*/
    public function get_microtime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    public function start()
    {
        $this->StartTime = $this->get_microtime();
    }

    public function stop()
    {
        $this->StopTime = $this->get_microtime();
    }

    public function spent()
    {
        return round(($this->StopTime - $this->StartTime) * 1000, 1);
    }


    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    /*
     * 运行code
     * 找代码的存放路径，并从存放路径取出代码转移到运行文件夹中，并在运行文件夹中生成可执行文件，将可执行文件和输入数据字符串传参给run
     * 产生的垃圾文件需要删除（复制到运行文件夹中的cpp，运行文件夹中的可执行文件）
     * 这其中有个小bug：如果run中throw了，则不能删除运行文件夹中的cpp和可执行文件，这个有时间在解决
     * */
    public function startCode($inputData, $code_id, $db)
    {
        $codePath = $this->codemodel->getPathByCodeId($code_id, $db);//找代码存放的路径
        $ci =& get_instance();
        $ci->load->config('my_conf');
        if(strtoupper(substr(PHP_OS,0,3)) == "WIN") {
            $runCodePath = $ci->config->item('win_run_code_path');
        } else {
            $runCodePath = $ci->config->item('linux_run_code_path');
        }
        $nowDate = $this->getMillisecond();//随机生成个名字
        $runName = $runCodePath . $code_id . $nowDate;
        $runCodeName = $runCodePath . $code_id . $nowDate . '.cpp';
        exec('cp -f '. $codePath . ' ' . $runCodeName, $cpOutput, $cpVar);//复制原有存储路径下的代码到专门用于运行的文件夹中
        if($cpVar) {//复制文件失败
            throw new Exception("copy code error " . __line__, -90022);
        }
        $myExec = 'g++ -o ' . $runName . ' ' . $runCodeName;
        exec($myExec, $createOutput, $createVar);//生成可执行文件
        if($createVar) {//生成执行文件失败
            throw new Exception("Error generating executable " . __line__, -90023);
        }
        $this->start();//计算时间
        $startmem = memory_get_usage();//开始时占用的空间
        $this->run($runName, $inputData);//传入执行文件的路径和输入数据
        $this->delFile($runCodeName);//删除执行文件和复制过来的cpp文件
        $this->delFile($runName);
        $endmem = memory_get_usage();
        $this->stop();
        $mem = memory_get_peak_usage() - $startmem;//占用最高时刻的空间-开始时的
        if($mem < 1024) {
            $this->runMemory = $mem . 'B';
        } else {
            $this->runMemory = intval($mem/(1024)) . 'KB';
        }
        $this->runTime = ($this->spent()) . 'ms';
        return true;
    }

    /*
     * web服务器不能开fork，则利用脚本的方式 流程：传参（执行文件，输入数据） 然后异步执行脚本，并传参（执行文件，输入数据的文件路径）给脚本，脚本打开输入数据文件，并开fork执行将结果保存到输出数据文件中
     * 同时函数中监听看输出检查文件是否生成，生成则说明脚本执行完，将输出数据文件打开并把结果保存到output中，如果超过最大时间还没生成则说明跑超时了，直接throw TLE
     * 在这个函数中生成的过程文件要删除，过程文件有：1.输入数据文件 2.输出数据文件 3.异步检查check文件是否生成
     * */
    public function run($runName, $inputData)
    {
        $ci =& get_instance();
        $ci->load->config('my_conf');
        if(strtoupper(substr(PHP_OS,0,3)) == "WIN") {
            $ioPath = $ci->config->item('win_io_data_path');
            $checkOverPath = $ci->config->item('win_check_over_path');
        } else {
            $ioPath = $ci->config->item('linux_io_data_path');
            $checkOverPath = $ci->config->item('linux_check_over_path');
        }
        $nowDate = $this->getMillisecond();//随机生成个名字
        $inputDataName = $ioPath . $nowDate;// .'input.txt'
        $myInputFile = fopen($inputDataName.'input.txt', "w+");
        fwrite($myInputFile, $inputData);
        fclose($myInputFile);
        $path = '/var/www/html/zt-code-evaluation/';
        $myExec = 'php ' . $path . 'run.php -a ' . $runName . ' -b "'. $inputDataName . '" > '. $checkOverPath .  $nowDate . 'check.txt &';//跑脚本 注意：这里因为是ci模式，所以先对输入数据进行ascll码的转化，才能在传空格回车过去，同理回来的数据也要进行一次ascll的转化为字符
        exec($myExec, $output, $var);

        $nowTime = $this->getMillisecond();
        while(true) {
            if(strlen(file_get_contents($checkOverPath . $nowDate .'check.txt'))){
                break;
            }
            if($this->getMillisecond() - $nowTime > $this->MAXTIME){//超时

                $killPidFirst = fopen($inputDataName.'1pid.txt', "r+");
                $killExec = "kill -9 ". fread($killPidFirst, filesize($inputDataName.'1pid.txt'));
                fclose($killPidFirst);
                exec($killExec, $output, $var);
                $this->delFile($inputDataName.'1pid.txt');

                $killPidSecond = fopen($inputDataName.'2pid.txt', "r+");
                $killExec = "kill -9 ". fread($killPidSecond, filesize($inputDataName.'2pid.txt'));
                fclose($killPidSecond);
                exec($killExec, $output, $var);
                $this->delFile($inputDataName.'2pid.txt');

                $killPidThird = fopen($inputDataName.'3pid.txt', "r+");
                $killExec = "kill -9 ". fread($killPidThird, filesize($inputDataName.'3pid.txt'));
                fclose($killPidThird);
                exec($killExec, $output, $var);
                $this->delFile($inputDataName.'3pid.txt');

                $this->delFile($inputDataName . 'input.txt');//删除IO过程文件
                $this->delFile($inputDataName . 'output.txt');
                throw new Exception("TLE " . __line__, -99991);
            }
            usleep(50000);
        }
        if($var) {//执行脚本失败

            $killPidFirst = fopen($inputDataName.'1pid.txt', "r+");
            $killExec = "kill -9 ". fread($killPidFirst, filesize($inputDataName.'1pid.txt'));
            fclose($killPidFirst);
            exec($killExec, $output, $var);
            $this->delFile($inputDataName.'1pid.txt');

            $killPidSecond = fopen($inputDataName.'2pid.txt', "r+");
            $killExec = "kill -9 ". fread($killPidSecond, filesize($inputDataName.'2pid.txt'));
            fclose($killPidSecond);
            exec($killExec, $output, $var);
            $this->delFile($inputDataName.'2pid.txt');

            $killPidThird = fopen($inputDataName.'3pid.txt', "r+");
            $killExec = "kill -9 ". fread($killPidThird, filesize($inputDataName.'3pid.txt'));
            fclose($killPidThird);
            exec($killExec, $output, $var);
            $this->delFile($inputDataName.'3pid.txt');

            $this->delFile($inputDataName . 'input.txt');//删除IO过程文件
            $this->delFile($inputDataName . 'output.txt');
            $this->delFile($checkOverPath . $nowDate . 'check.txt');//删除异步检查超时过程文件
            throw new Exception("Error executing run script " . __line__, -99999);
        }
        $myOutputFile = fopen($inputDataName.'output.txt', "r+");
        if(!filesize($inputDataName.'output.txt')) {
            $this->delFile($inputDataName.'1pid.txt');
            $this->delFile($inputDataName.'2pid.txt');
            $this->delFile($inputDataName.'3pid.txt');
            $this->delFile($inputDataName . 'input.txt');//删除IO过程文件
            $this->delFile($inputDataName . 'output.txt');
            $this->delFile($checkOverPath . $nowDate . 'check.txt');//删除异步检查超时过程文件
            throw new Exception("Error in code execution " . __line__, -99995);
        }
        $this->outputData = fread($myOutputFile, filesize($inputDataName.'output.txt'));
        fclose($myOutputFile);

        $this->delFile($inputDataName.'1pid.txt');
        $this->delFile($inputDataName.'2pid.txt');
        $this->delFile($inputDataName.'3pid.txt');
        $this->delFile($inputDataName . 'input.txt');//删除IO过程文件
        $this->delFile($inputDataName . 'output.txt');
        $this->delFile($checkOverPath . $nowDate . 'check.txt');//删除异步检查超时过程文件
    }

    public function encode($c, $prefix="&#") {//字符串转为ASCLL
        $scill = "";
        $len = strlen($c);
        $a = 0;
        while ($a < $len) {
            $ud = 0;
            if (ord($c{$a}) >= 0 && ord($c{$a}) <= 127) {
                $ud = ord($c{$a});
                $a += 1;
            } else if (ord($c{$a}) >= 192 && ord($c{$a}) <= 223) {
                $ud = (ord($c{$a}) - 192) * 64 + (ord($c{$a + 1}) - 128);
                $a += 2;
            } else if (ord($c{$a}) >= 224 && ord($c{$a}) <= 239) {
                $ud = (ord($c{$a}) - 224) * 4096 + (ord($c{$a + 1}) - 128) * 64 + (ord($c{$a + 2}) - 128);
                $a += 3;
            } else if (ord($c{$a}) >= 240 && ord($c{$a}) <= 247) {
                $ud = (ord($c{$a}) - 240) * 262144 + (ord($c{$a + 1}) - 128) * 4096 + (ord($c{$a + 2}) - 128) * 64 + (ord($c{$a + 3}) - 128);
                $a += 4;
            } else if (ord($c{$a}) >= 248 && ord($c{$a}) <= 251) {
                $ud = (ord($c{$a}) - 248) * 16777216 + (ord($c{$a + 1}) - 128) * 262144 + (ord($c{$a + 2}) - 128) * 4096 + (ord($c{$a + 3}) - 128) * 64 + (ord($c{$a + 4}) - 128);
                $a += 5;
            } else if (ord($c{$a}) >= 252 && ord($c{$a}) <= 253) {
                $ud = (ord($c{$a}) - 252) * 1073741824 + (ord($c{$a + 1}) - 128) * 16777216 + (ord($c{$a + 2}) - 128) * 262144 + (ord($c{$a + 3}) - 128) * 4096 + (ord($c{$a + 4}) - 128) * 64 + (ord($c{$a + 5}) - 128);
                $a += 6;
            } else if (ord($c{$a}) >= 254 && ord($c{$a}) <= 255) { //error
                $ud = false;
            }
            $scill .= $prefix.$ud.";";
        }
        return $scill;
    }

    public function decode($str, $prefix="&#") {//ASCLL转字符串
        $utf = "";
        $str = str_replace($prefix, "", $str);
        $a = explode(";", $str);
        foreach ($a as $dec) {
            if($dec == 0) {
                continue;
            }
            if ($dec < 128) {
                $utf .= chr($dec);
            } else if ($dec < 2048) {
                $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            } else {
                $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096));
                $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            }
        }
        return $utf;
    }

    public function delFile($path){ //删除文件
        $delExec = 'rm -f '. $path;
        exec($delExec, $output, $var);//删文件操作可以存在垃圾文件 故不throw
    }

}

