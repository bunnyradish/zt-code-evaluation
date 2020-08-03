<?php

class Usermodel extends CI_Model
{
    private $dbConnectHandle;

    public function __construct()
    {
        $this->dbConnectHandle = null;
    }

    public function trans_begin($dbConnectHandle)
    {
        $dbConnectHandle->trans_begin();
    }

    public function trans_rollback($dbConnectHandle)
    {
        $dbConnectHandle->trans_rollback();
    }

    public function trans_commit($dbConnectHandle)
    {
        $dbConnectHandle->trans_commit();
    }

    public function init()
    {
        $this->dbConnectHandle = $this->load->database('default', true);
        if (empty($this->dbConnectHandle)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        }
        return $this->dbConnectHandle;
    }

    /**
     * @param $user_account, $user_password, $user_nick, $db
     * @return mixed
     * @throws 注册
     */
    public function userRegister($data, $db)
    {
        if (empty($db)) {
            $code = -8;
        } else {
            $info['user_portrait'] = 'http://47.107.83.200/zt-code-evaluation/user_pic/default.jpg';
            $columns = array('user_account', 'user_password', 'user_nick', 'salt');
            foreach ($columns as $key) {
                if (isset($data[$key])) {
                    $info[$key] = $data[$key];
                }
            }
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $code = $db->insert('eva_user', $info);
            if (!$code) {
                throw new Exception('insert to database failed ' . __LINE__, -9);
            }
            $code = $db->insert_id('eva_user');
        }
        return $code;
    }

    /**
     * @param $user_account, $user_password, $user_nick, $db
     * @return mixed
     * @throws 检查账号是否重复
     */
    public function checkAccount($data, $db)
    {
        $code = 0;
        if (empty($db)) {
            $code = -8;
        } else {
            if(!isset($data['user_account'])) {
                throw new Exception('have no account' . __LINE__, -10);
            }
            $sql = "SELECT user_account from eva_user where user_account = '{$data['user_account']}'";
            $result = $db->query($sql);
            $row = $result->row_array();

            if($row) {
                $code = 999;
            }
        }
        return $code;
    }


    /**
     * @param $data, $db
     * @return mixed
     * @throws 根据用户账号查询密码
     */
    public function getPswByAccount($data, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            $columns = array('user_account');
            $info = array();
            foreach ($columns as $key) {
                if (isset($data[$key])) {
                    $info[$key] = $data[$key];
                }
            }
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $sql = "select user_password from eva_user where user_account = '{$info['user_account']}'";
            $result = $db->query($sql);
        }
        return $result->row_array();
    }

    /**
     * @param $user_account, $db
     * @return mixed
     * @throws 根据用户账号查询用户信息
     */
    public function getUserDataByAccount($user_account, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $sql = "select * from eva_user where user_account = '{$user_account}'";
            $result = $db->query($sql);
        }
        return $result->row_array();
    }

    /**
     * @param $uin, $db
     * @return mixed
     * @throws 根据用户标识查询用户信息
     */
    public function getUserInfo($uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $sql = "select * from eva_user where user_id = '{$uin}'";
            $result = $db->query($sql);
        }
        return $result->row_array();
    }

    /**
     * @param $account, $db
     * @return mixed
     * @throws 根据用户账户获得盐
     */
    public function getSaltByAccount($user_account, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $sql = "select salt from eva_user where user_account = '{$user_account}'";
            $result = $db->query($sql);
        }
        return $result->row_array();
    }

    /**
     * @param $uin, $db
     * @return mixed
     * @throws 根据用户查询用户头像路径
     */
    public function getUserPortrait($uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $sql = "select user_portrait from eva_user where user_id = '{$uin}'";
            $result = $db->query($sql);
        }
        return $result->row_array();
    }


    /**
     * @param $uin, $path, $db
     * @return mixed
     * @throws 改用户头像存储路径
     */
    public function updatePortrait($uin, $path, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $data = array(
                'user_portrait' => $path
            );
            $db->where('user_id', $uin);
            $db->update('eva_user', $data);
        }
        return $db->affected_rows();
    }


    /**
     * @param $uin, $nick, $db
     * @return mixed
     * @throws 保存用户昵称
     */
    public function saveNick($uin, $nick, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $data = array(
                'user_nick' => $nick
            );
            $db->where('user_id', $uin);
            $db->update('eva_user', $data);
        }
        return $db->affected_rows();
    }


    /**
     * @param $data, $db
     * @return mixed
     * @throws 更改用户密码
     */
    public function updatePwd($data, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect read failed ' . __LINE__, -8);
        } else {
            //$sql = "insert into eva_user set user_account = '{$info['user_account']}', user_password = '{$info['user_password']}', user_nick = '{$info['user_nick']}'";
            //$code = $db->query($sql);
            $info = array(
                'user_password' => $data['newPwd'],
                'salt' => $data['salt']
            );
            $db->where('user_id', $data['uin']);
            $db->update('eva_user', $info);
        }
        return $db->affected_rows();
    }
}
