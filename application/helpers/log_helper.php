<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\ChromePHPFormatter;

if (!function_exists('doLog')) {
    function doLog($logFilename, $logDataArr)
    {
        $ci =& get_instance();
        $ci->load->config('my_conf');
        if(strtoupper(substr(PHP_OS,0,3)) == "WIN") {
            $basePath = $ci->config->item('win_log_path');
        } else {
            $basePath = $ci->config->item('linux_log_path');
        }
        if (empty($logFilename)) {
            return;
        }
        if (!is_string($logFilename)) {
            return;
        }
        if (empty($logDataArr)) {
            return;
        }
        if (!is_array($logDataArr)) {
            return;
        }
        $format = "%datetime%%message% %context% %extra%\n";
        //样例
        //2016-07-07 10:02:42.135||ip=192.168.32.128||status=200

        $log = new Logger('');
        $streamHandler = new StreamHandler($basePath . $logFilename, Logger::DEBUG);
        $formatter = new LineFormatter($format, null, false, true);
        $streamHandler->setFormatter($formatter);
        $log->pushHandler($streamHandler);
        if (ENVIRONMENT !== 'production') {
            //$chromeHandler = new ChromePHPHandler();
            //$log->pushHandler($chromeHandler);
        }
        $logStr = '';
        foreach ($logDataArr as $k => $v) {
            if (is_array($k) || is_object($k)) {
                continue;
            }
            if (is_array($v)) {
                $logStr = $logStr . '||' . $k . '=' . json_encode($v);
            } else {
                $logStr = $logStr . '||' . $k . '=' . $v;
            }
        }

        $log->info($logStr);
    }
}

