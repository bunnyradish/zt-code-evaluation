<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/5/30
 * Time: 18:36
 */

if(!isset($_SERVER['HTTP_ORIGIN']))$_SERVER['HTTP_ORIGIN']="*";
header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']);
header("Access-Control-Allow-Headers:*");
/*星号表示所有的域都可以接受，*/
header("Access-Control-Allow-Methods:GET,POST");
header("Access-Control-Allow-Credentials: true");


class Uploadportrait extends CI_Controller
{
    private $logFilename = 'err_auth_user-update-uploadportrait-';
    private $msg = ['code' => 0, 'msg' => '', 'data' => []];
    private $requestId;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');
        $this->load->model('usermodel');
        $this->load->helper('common');
        $this->load->helper(array('form', 'url'));
        $this->logFilename = $this->logFilename . date("Y-m-d") . '.log';
    }

    /*
     * 上传头像
     * */
    public function index()
    {
        try {
            $requestHeaders = $this->input->request_headers();
            $this->requestId = isset($requestHeaders['X-Requestid']) ? $requestHeaders['X-Requestid'] : 0;
            $cookieArr = $this->input->cookie();
            $postMsg = json_decode(file_get_contents('php://input'), true);
            $data = $this->input->get();
            $sid = $data['user_id'];
            $sid = substr($sid, 5);
            $sid = substr($sid, 0, -5);
            $result = 0;
            $tmp = 1;
            for ($i = 0; $i < strlen($sid); $i++){
                $result += intval(ord($sid[$i]) - ord('A'))*$tmp;
                $tmp *= 10;
            }
            $uin = $result;
            $db = $this->usermodel->init();
            $oldPortrait = $this->usermodel->getUserPortrait($uin, $db);
            $oldPortrait = $oldPortrait['user_portrait'];
            $default = "http://47.107.83.200/zt-code-evaluation/user_pic/default.jpg";
            $error = "";
            $name = $uin.$this->getMillisecond();
            $config['file_name'] = $name;
            $config['upload_path'] = '/var/www/html/zt-code-evaluation/user_pic/';
            $config['allowed_types']    = 'gif|jpg|png';
            $config['max_size'] = 1024;
            $config['max_width'] = 0;
            $config['max_height'] = 0;
            $config['remove_spaces'] = true;
            $this->load->library('upload', $config);
            if (!$this->upload->do_upload('user_portrait_upload')) {
                $error = $this->upload->display_errors();
                $this->msg['code'] = -1;
                $this->msg['data'] = $error;
            }
            else {
                $data = $this->upload->data();
                $this->usermodel->trans_begin($db);//开事务先更改路径再删除旧图片
                if(!$this->usermodel->updatePortrait($uin, 'http://47.107.83.200/zt-code-evaluation/user_pic/'.$data['file_name'], $db)) {
                    $this->usermodel->trans_rollback($db);
                }
                if(strcmp($oldPortrait, $default)) {//待会用事务
                    $path = '/var/www/html/zt-code-evaluation/user_pic'. substr($oldPortrait,strrpos($oldPortrait ,"/"));
                    unlink($path);
                }
                $this->usermodel->trans_commit($db);
                $this->msg['data'] = "success";
            }
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($this->msg));

        } catch (Exception $e) {
            $logArr = ['requestid' => $this->requestId, 'errno' => $e->getCode(), 'errmsg' => $e->getMessage() . __LINE__];
            doLog($this->logFilename, $logArr);
            $output = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
            $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($output));
        }
    }

    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
}
