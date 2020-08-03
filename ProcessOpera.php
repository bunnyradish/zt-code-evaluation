<?php 
/**
 * 入口函数
 * 将此文件保存为 ProcessOpera.php
 * 在terminal中运行 /usr/local/php/bin/php ProcessOpera.php &
 * 查看进程 ps aux|grep php
 */
ProcessOpera("runCode", array(), 8);
/**
 * run Code
 */
//runCode();

/**
 * $func为子进程执行具体事物的函数名称
 * $opt为$func的参数 数组形式
 * $pNum 为fork的子进程数量
 */
function ProcessOpera($func, $opts = array(), $pNum = 1) {
	while(true) {
	$pid = pcntl_fork();
	if($pid == -1) {
		exit("pid fork error");
	}
	if($pid) {
			static $execute = 0;
			$execute++;
			if($execute >= $pNum) {
                pcntl_wait($status);
                $execute--;
		    }
		} else {
            while(true) {
                //somecode
                $func($opts);
                sleep(1);
            }
            exit(0);
        }
	}
}


function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
}

function getCompareId($conn)
{
    mysqli_query($conn, "SET AUTOCOMMIT=0");
    mysqli_query($conn, "START TRANSACTION");
    $sql = "select compare_id from run_compare where version = 0 ORDER BY compare_id limit 1 for update";
    if($res = mysqli_query($conn, $sql)) {
        $compare_id = mysqli_fetch_array($res);
        $compare_id = $compare_id['compare_id'];
        $sqlUpdate = "update run_compare set version=1 where compare_id=".$compare_id;
        if(mysqli_query($conn, $sqlUpdate)) {
            mysqli_query($conn, "COMMIT");
            mysqli_query($conn, "SET AUTOCOMMIT=1");
            return $compare_id;
        } else {
            mysqli_query($conn, "SET AUTOCOMMIT=1");
            mysqli_query($conn, "ROLLBACK");
        }
    } else {
        mysqli_query($conn, "SET AUTOCOMMIT=1");
        mysqli_query($conn, "ROLLBACK");
    }
}

function runCode($opt = array()) {
    header("Content-type:text/html;charset=utf-8");
    $conn=mysqli_connect('47.107.83.200','root','zt261020.','code_evaluation');//三个参数分别对应服务器名，账号，密码
    if (!$conn) {
        die("连接数据库失败: " . mysqli_connect_error());//连接服务器失败退出程序
    }
    mysqli_set_charset($conn, 'UTF-8');
    $compare_id = getCompareId($conn);
    if(!isset($compare_id)) {
    } else {
        $nowDate = getMillisecond();//随机生成个名字
        $compare_path = "/var/www/html/zt-code-evaluation/compareGo/".$compare_id."/";
        createCompareDir($compare_path);
        $run_path = $compare_path."run_code/";//对拍的运行文件夹
        createCompareDir($run_path);
        $io_path = $compare_path."io_data/";//对拍的输入输出文件夹
        createCompareDir($io_path);
        $takeCodeIdSql = "select * from eva_compare where compare_id = ".$compare_id;
        $compareData = mysqli_fetch_array(mysqli_query($conn, $takeCodeIdSql));
        generateInputData($run_path, $io_path, $nowDate, $compareData);//执行完之后已经把随机生成输入数据的处理结果搞好了 之后就把处理结果循环放入code1 和 code2 中当输入数据跑


        $connect=mysqli_connect('47.107.83.200','root','zt261020.','code_evaluation');//三个参数分别对应服务器名，账号，密码
        if (!$connect) {
            die("连接数据库失败: " . mysqli_connect_error());//连接服务器失败退出程序
        }
        mysqli_set_charset($connect, 'UTF-8');

        $takeFirstCodePathSql = "select path from eva_code where code_id = ".$compareData['first_code_id'];
        $firstCodePath = mysqli_fetch_array(mysqli_query($connect, $takeFirstCodePathSql));
        $firstCodePath = $firstCodePath['path'];
        $takeSecondCodePathSql = "select path from eva_code where code_id = ".$compareData['second_code_id'];
        $secondCodePath = mysqli_fetch_array(mysqli_query($connect, $takeSecondCodePathSql));
        $secondCodePath = $secondCodePath['path'];
		$flag = false;
        for($i = 1; $i <= $compareData['max_input_group']; $i++) {
			if($flag == false) {
				list($outputData, $runTime, $runMemory) = runCodeGetResult($firstCodePath, $nowDate, $run_path, $compareData['first_code_id'], $io_path, $i);
				$firstCodeMsg['outputData'] = $outputData;
				$firstCodeMsg['runTime'] = $runTime;
				$firstCodeMsg['runMemory'] = $runMemory;
				list($outputData, $runTime, $runMemory) = runCodeGetResult($secondCodePath, $nowDate, $run_path, $compareData['second_code_id'], $io_path, $i);
				$secondCodeMsg['outputData'] = $outputData;
				$secondCodeMsg['runTime'] = $runTime;
				$secondCodeMsg['runMemory'] = $runMemory;
				if(insert($firstCodeMsg, $secondCodeMsg, $compare_id, $i, $compareData['max_input_group'], $io_path)){
					$flag = true;
					continue;
				}
			}
            delFile($io_path.$i . 'input.txt');//删除IO过程文件
        }
    }
}

function insert($firstCodeMsg, $secondCodeMsg, $compare_id, $i, $maxx, $io_path)
{
	$flag = false;
    $conn=mysqli_connect('47.107.83.200','root','zt261020.','code_evaluation');//三个参数分别对应服务器名，账号，密码
    if (!$conn) {
        die("连接数据库失败: " . mysqli_connect_error());//连接服务器失败退出程序
    }
    mysqli_set_charset($conn, 'UTF-8');
    mysqli_query($conn, "SET AUTOCOMMIT=0");
    mysqli_query($conn, "START TRANSACTION");
    $select = "select compare_data from run_compare where compare_id = ".$compare_id;
    $tmp = mysqli_fetch_array(mysqli_query($conn, $select));
    $tmp = $tmp['compare_data'];
    $compareData = json_decode($tmp, true);
    $insertData = $compareData;
    $insertData[$i]['first_runTime'] = $firstCodeMsg['runTime'];
    $insertData[$i]['first_runMemory'] = $firstCodeMsg['runMemory'];
    $insertData[$i]['second_runTime'] = $secondCodeMsg['runTime'];
    $insertData[$i]['second_runMemory'] = $secondCodeMsg['runMemory'];
    if(strcmp($firstCodeMsg['outputData'], $secondCodeMsg['outputData'])) {
        //$insertData[$i]['first_outputData'] = $firstCodeMsg['outputData'];
        //$insertData[$i]['second_outputData'] = $secondCodeMsg['outputData'];
        $insertData[$i]['input_path'] = $io_path.$i . 'input.txt';
		$flag = true;
    }
    $insertData = json_encode($insertData);
	$str = "";
	if($i == $maxx || flag == true){
		$str = " ,version=2 ";
	}
    $sql = "update run_compare set compare_data='".$insertData."'".$str." where compare_id=".$compare_id;
    if(mysqli_query($conn, $sql)) {
        mysqli_query($conn, "COMMIT");
        mysqli_query($conn, "SET AUTOCOMMIT=1");
    } else {
        mysqli_query($conn, "SET AUTOCOMMIT=1");
        mysqli_query($conn, "ROLLBACK");
    }
	return $flag;
}

/*下面2个函数是代码的编译与运行*/
function runCodeGetResult($codePath, $nowDate, $run_path, $code_id, $io_path, $i)
{
    $runName = $run_path . $code_id . $nowDate;
    $runCodeName = $run_path . $code_id . $nowDate . '.cpp';
    exec('cp -f '. $codePath . ' ' . $runCodeName, $cpOutput, $cpVar);//复制原有存储路径下的代码到专门用于运行的文件夹中
    if($cpVar) {//复制文件失败
        throw new Exception("copy code error " . __line__, -90022);
    }
    $myExec = 'g++ -o ' . $runName . ' ' . $runCodeName;
    exec($myExec, $createOutput, $createVar);//生成可执行文件
    if($createVar) {//生成执行文件失败
        throw new Exception("Error generating executable " . __line__, -90023);
    }
    $startTime = start();
    $startmem = memory_get_usage();
    list($outputData, $runTime) = runGet($runName, $io_path, $i);
    delFile($runCodeName);//删除执行文件和复制过来的cpp文件
    delFile($runName);
    $endmem = memory_get_usage();
    $stopTime = stop();
    $mem = memory_get_peak_usage() - $startmem;
    if($mem < 1024) {
        $runMemory = $mem . 'B';
    } else {
        $runMemory = intval($mem/(1024)) . 'KB';
    }
    if($runTime == 0){
        $runTime = (spent($startTime, $stopTime)) . 'ms';
    } else {
        $runTime = "-1";
    }
    return [$outputData, $runTime, $runMemory];
}

function runGet($runName, $ioPath, $i)
{
    $checkOverPath = $ioPath."/check_over/";
    createCompareDir($checkOverPath);
    $runTime = 0;
    $nowDate = getMillisecond();//随机生成个名字
    $inputDataName = $ioPath . $i;
    $path = '/var/www/html/zt-code-evaluation/';
    $myExec = 'php ' . $path . 'run.php -a ' . $runName . ' -b "'. $inputDataName . '" > '. $checkOverPath .  $nowDate . 'check.txt &';//跑脚本 注意：这里因为是ci模式，所以先对输入数据进行ascll码的转化，才能在传空格回车过去，同理回来的数据也要进行一次ascll的转化为字符

    exec($myExec, $output, $var);
    $nowTime = getMillisecond();
    while(true) {
        if(strlen(file_get_contents($checkOverPath . $nowDate .'check.txt'))){
        //if(file_exists($ioPath.$i.'output.txt')){
            break;
        }
        if(getMillisecond() - $nowTime > 15000){//超时
            $killPidFirst = fopen($inputDataName.'1pid.txt', "r+");
            $killExec = "kill -9 ". fread($killPidFirst, filesize($inputDataName.'1pid.txt'));
            fclose($killPidFirst);
            exec($killExec, $output, $var);
			
			$killPidSecond = fopen($inputDataName.'2pid.txt', "r+");
			$killExec = "kill -9 ". fread($killPidSecond, filesize($inputDataName.'2pid.txt'));
			fclose($killPidSecond);
			exec($killExec, $output, $var);
			
            $killPidThird = fopen($inputDataName.'3pid.txt', "r+");
            $killExec = "kill -9 ". fread($killPidThird, filesize($inputDataName.'3pid.txt'));
            fclose($killPidThird);
            exec($killExec, $output, $var);
			
            echo $i. " : ".getMillisecond()." - " .$nowTime . " = ".(getMillisecond() - $nowTime)."\n";
            $runTime = -1;
            break;
        }
        usleep(50000);
    }
    $myOutputFile = fopen($inputDataName.'output.txt', "r+");
    if(!filesize($inputDataName .'output.txt')) {//执行结果为空或者错误
        $outputData = "";
    } else {
        $outputData = fread($myOutputFile, filesize($inputDataName.'output.txt'));
    }
    fclose($myOutputFile);
    if($var) {//执行脚本失败
        $outputData="";
        $runTime=-1;
    }
    delFile($checkOverPath . $nowDate .'check.txt');
	delFile($inputDataName.'1pid.txt');
	delFile($inputDataName.'2pid.txt');
    delFile($inputDataName.'3pid.txt');
    delFile($inputDataName . 'output.txt');//删除IO过程文件
    return [$outputData, $runTime];
}


function createCompareDir($str)//创文件夹
{
    $dir = iconv("UTF-8", "GBK", $str);
    if (!file_exists($dir)){
        mkdir ($dir,0777,true);
		chmod ($dir, 0777);
    }
}


/*下面2个函数是随机生成数据的编译与运行*/
function generateInputData($run_path, $io_path, $nowDate, $compareData)//跑随机生成数据的cpp代码并生成输入数据
{
    $max_input_group = $compareData['max_input_group'];//组数
    $runName = $run_path . $compareData['compare_id'] . $nowDate;
    $runCodeName = $run_path . $compareData['compare_id'] . $nowDate . '.cpp';
    exec('cp -f '. $compareData['input_data_path'] . ' ' . $runCodeName, $cpOutput, $cpVar);//复制原有存储路径下的代码到专门用于运行的文件夹中
    if($cpVar) {//复制文件失败
        throw new Exception("copy code error " . __line__, -90022);
    }
    $myExec = 'g++ -o ' . $runName . ' ' . $runCodeName;
    exec($myExec, $createOutput, $createVar);//生成可执行文件
    if($createVar) {//生成执行文件失败
        throw new Exception("Error generating executable " . __line__, -90023);
    }
    for($i = 1; $i <= $max_input_group; $i++) {
        generateInputRun($runName, $i, $io_path, $nowDate);
    }
    delFile($runCodeName);//删除执行文件和复制过来的cpp文件
    delFile($runName);
}



function generateInputRun($runName, $inputData, $io_path, $nowDate)//跑随机生成数据的代码 iopath在这个函数里主要是将随机生成的数据作为输出数据生成文件放进去 但其实是供给真正跑代码的输入数据
{
    $inputDataName = $io_path . "generate" . $inputData . $nowDate;// .'input.txt'  作为随机生成文件的输入文件
    $myInputFile = fopen($inputDataName.'input.txt', "w+");
    fwrite($myInputFile, $inputData);
    fclose($myInputFile);
    run($runName, $inputDataName);

    $myOutputFile = fopen($inputDataName.'output.txt', "r+");
    $codeInputData = fread($myOutputFile, filesize($inputDataName.'output.txt'));
    fclose($myOutputFile);
    delFile($inputDataName . 'input.txt');//删除IO过程文件
    delFile($inputDataName . 'output.txt');
    $InputData = fopen($io_path.$inputData.'input.txt', "w+");
    fwrite($InputData, $codeInputData);
    fclose($InputData);
}

function delFile($path){ //删除文件
    $delExec = 'rm -f '. $path;
    exec($delExec, $output, $var);//删文件操作可以存在垃圾文件 故不throw
}



function run($runName, $inputData)
{
    $pid = pcntl_fork();//返回子进程的id
    if($pid < 0) {
        throw new Exception("fork error " . __line__, -90099);
    } else if($pid == 0) {
        global $outputData;
        $outputData = exec_stdin($runName, $inputData);//获取程序运行结果
        //$myexec = 'echo "' . $outputData . '" > ' . $runName . '.txt';
        //exec($myexec, $output);
        exit(0);
    } else {
        pcntl_waitpid(0, $status);
    }
}

function exec_stdin($Command, $Data)//开子进程跑程序
{
    $_ = proc_open($Command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $p);
    if (is_resource($_)) {
        $procData = proc_get_status($_);
        $procPid = $procData['pid'];
        //$mem = exec('cat /proc/'.$procPid.'/status|grep -e VmRSS| awk \'{print $2}\'');
        $fin = fopen($Data.'input.txt', "r+");
        $fout = fopen($Data.'output.txt', "w+");
        fwrite($p[0], fread($fin, filesize($Data.'input.txt')));
        fclose($p[0]);
        $o = stream_get_contents($p[1]);
        fwrite($fout, $o);
        fclose($fin);
        fclose($fout);
        fclose($p[1]);
        $_ = proc_close($_);
        return $o;
    }
    return false;
}

/*计时*/
function get_microtime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

function start()
{
    $startTime = get_microtime();
    return $startTime;
}

function stop()
{
    $stopTime = get_microtime();
    return $stopTime;
}

function spent($startTime, $stopTime)
{
    return round(($stopTime - $startTime) * 1000, 1);
}
