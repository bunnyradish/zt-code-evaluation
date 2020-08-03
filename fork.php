<?php
echo strtolower(php_sapi_name());
	$curr_pid = posix_getpid();//获取当前的进程id

        //将当前进程的id写入文件中
        echo '当前进程：'.$curr_pid.PHP_EOL;

        //开始创建子进程
        $son_pid = pcntl_fork();//返回子进程的id

        //查看当前进程
        echo '创建子进程之后当前的进程为：'.posix_getpid().PHP_EOL;

        //创建了子进程之后
        if($son_pid > 0){
            echo '子进程id：'.$son_pid.PHP_EOL;
        }exit;
