<?php

$data = getopt('a:b:');
$runName = $data['a'];
$inputData = $data['b'];
var_dump($runName);
var_dump($inputData);
run($runName, $inputData);

//print_r(json_encode($resage));

function run($runName, $inputData)
{
	$pid = pcntl_fork();//�����ӽ��̵�id
    if($pid < 0) {
        throw new Exception("fork error " . __line__, -90099);
    } else if($pid == 0) {
		$fpid = fopen($inputData.'1pid.txt', "w+");
		fwrite($fpid, posix_getpid());
		fclose($fpid);
		global $outputData;
		$outputData = exec_stdin($runName, $inputData);//��ȡ�������н��
		$outputData = encode($outputData);
		//echo $outputData;
		//echo "\n";echo "0000000";echo "cat /proc/".posix_getpid()."/status";var_dump(exec("cat /proc/".posix_getpid()."/status > /var/www/html/myproc.txt"));echo "0000000";
		//$myexec = 'echo "' . $outputData . '" > ' . $runName . '.txt';
		//exec($myexec, $output);
//echo "gogogo";var_dump(memory_get_peak_usage()-memory_get_usage());echo "gogogo";
		exit(0);
    } else {
		$fpid = fopen($inputData.'2pid.txt', "w+");
		fwrite($fpid, posix_getpid());
		fclose($fpid);
		pcntl_waitpid(0, $status);
        getRunMsg($pid);
    }
}
	
function exec_stdin($Command, $Data)//���ӽ����ܳ���
{
	$_ = proc_open($Command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $p);
	if (is_resource($_)) {
		$procData = proc_get_status($_);
		$procPid = $procData['pid'];
		$fpid = fopen($Data.'3pid.txt', "w+");
		fwrite($fpid, $procPid);
		fclose($fpid);
		//$mem = exec('cat /proc/'.$procPid.'/status|grep -e VmRSS| awk \'{print $2}\'');
		$fin = fopen($Data.'input.txt', "r+");
		$fout = fopen($Data.'output.txt', "w+");
		fwrite($p[0], fread($fin, filesize($Data.'input.txt')));
		fclose($p[0]);
		$o = stream_get_contents($p[1]);echo "ooooo";var_dump(1);echo "ooooo";
		fwrite($fout, $o);
		fclose($fin);
		fclose($fout);
		fclose($p[1]);//echo "\n-----\n";var_dump(getrusage($procPid));echo "\n-----\n";
		$_ = proc_close($_);
		return $o;
    }
    return false;
}
	
function getRunMsg($pid)
{
	global $resage;
    $resage = getrusage();
}

function encode($c, $prefix="&#") {//�ַ���תΪASCLL
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

  function decode($str, $prefix="&#") {//ASCLLת�ַ���
	$utf = "";
    $str = str_replace($prefix, "", $str);
    $a = explode(";", $str);
    foreach ($a as $dec) {
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
