<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/15
 * Time: 16:46
 */
class Codemodel extends CI_Model
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
     * @param $codeData, $code_msg, $uin, $db
     * @return mixed
     * @throws 插入code数据
     */
    public function insertMyCode($codeData, $code_msg, $uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $info = array();
            $columns = array('code_name', 'path');
            foreach ($columns as $key) {
                if (isset($codeData[$key])) {
                    $info[$key] = $codeData[$key];
                }
            }
            $info['code_text'] = $code_msg;
            $info['user_id'] = $uin;
            $info['create_time'] = date('Y-m-d H:i:s');
            $info['update_time'] = $info['create_time'];
            //$sql = "insert into eva_code set code_name = '{$info['code_name']}', user_id = '{$uin}', path = '{$info['path']}', code_text = '{$code_msg}'";
            //$code = $db->query($sql);
            $code = $db->insert('eva_code', $info);
            if (!$code) {
                throw new Exception('insert to database failed ' . __LINE__, -9);
            }
        }
        return $code;
    }

    /**
     * @param $uin, $codeName, $db
     * @return mixed
     * @throws 判断代码名称是否有重复的
     */
    public function checkCodeName($uin, $codeName, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "select code_name from eva_code where user_id = '{$uin}' and code_name = '{$codeName}'";
            $result = $db->query($sql);
        }
        return $result->result_array();
    }

    /**
     * @param $code_id, $db
     * @return mixed
     * @throws 判断代码是否是这个用户的
     */
    public function checkCode($code_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "select user_id from eva_code where code_id = '{$code_id}'";
            $result = $db->query($sql);
        }
        $res = $result->row_array();
        return empty($res) ? 0 : $res['user_id'];
    }

    /**
     * @param $code_id, $uin, $db
     * @return mixed
     * @throws 删除该用户下代码id为code_id的代码
     */
    public function deleteCode($code_id, $uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $code_id = intval($code_id);
            $uin = intval($uin);
            $code = $db->delete('eva_code', array('code_id' => $code_id, 'user_id' => $uin));
            if($code == true) {
                return true;
            }
            return false;
        }
    }
    /**
     * @param $code_id, $db
     * @return mixed
     * @throws 根据代码id查询路径
     */
    public function getPathByCodeId($code_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $code_id = intval($code_id);
            $sql = "select path from eva_code where code_id = {$code_id}";
            $result = $db->query($sql);
            $result = $result->row_array();
            if(isset($result['path'])) {
                return $result['path'];
            } else {
                throw new Exception('path does not exist ' . __LINE__, -9);
            }
        }
    }

    /**
     * @param $uin, $num, $db
     * @return mixed
     * @throws 分页查找code列表
     */
    public function queryCodeList($uin, $start_id, $count, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            if (empty($uin) || empty($count)) {
                throw new Exception('empty or userId or count ' . __LINE__, -11);
            }
            $uin = intval($uin);
            $limit = "";
            /*if ($count) {
                $limit = " limit $count";
            }*/
            $whereStart = "";
            if ($start_id) {
                $startId = date('Y-m-d H:i:s', $start_id);
                $whereStart = " and update_time < '{$startId}' ";
            }
            $sql = "SELECT code_id, code_name, path, UNIX_TIMESTAMP(update_time) as update_time, UNIX_TIMESTAMP(create_time) as create_time from eva_code WHERE user_id={$uin} {$whereStart} ORDER BY update_time DESC $limit";
            $query = $db->query($sql);
            return $query->result_array();
        }
    }

    /**
     * @param $uin, $code_id, $db
     * @return mixed
     * @throws 查找某一具体code
     */
    public function queryCodeDetail($uin, $code_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            if (empty($uin) || empty($code_id)) {
                throw new Exception('empty or userId or code_id ' . __LINE__, -11);
            }
            $uin = intval($uin);
            $code_id = intval($code_id);
            $sql = "SELECT code_id, code_name, code_text, path,  UNIX_TIMESTAMP(create_time) as create_time, UNIX_TIMESTAMP(update_time) as update_time  from eva_code WHERE $uin = {$uin} and code_id = {$code_id}";
            $query = $db->query($sql);
            return $query->row_array();
        }
    }


    /**
     * @param $uin, $codeName, $codeId, $db
     * @return mixed
     * @throws 判断代码名称是否有重复的 除了这个code
     */
    public function checkCodeNameExceptThis($uin, $codeName, $codeId, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "select code_name from eva_code where user_id = '{$uin}' and code_name = '{$codeName}' and code_id != '{$codeId}'";
            $result = $db->query($sql);
        }
        return $result->result_array();
    }

    /**
     * @param $codeData, $code_msg, $uin, $db
     * @return mixed
     * @throws 修改代码
     */
    public function updateMyCode($codeData, $code_msg, $code_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $data = array(
                'code_name' => $codeData['code_name'],
                'code_text' => $code_msg,
                'path' => $codeData['path'],
                'update_time' => date('Y-m-d H:i:s')
            );
            $db->where('code_id', $code_id);
            $db->update('eva_code', $data);
        }
        return $db->affected_rows();
    }

    /**
     * @param $codeData, $code_msg, $uin, $db
     * @return mixed
     * @throws 检查是否有正在运行的有这个代码的对拍
     */
    public function checkCompareRunning($code_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "select version from run_compare where compare_id in (select compare_id from eva_compare where first_code_id = '{$code_id}' union select compare_id from eva_compare where second_code_id = '{$code_id}')";
            $result = $db->query($sql);
        }
        return $result->result_array();
    }

}
