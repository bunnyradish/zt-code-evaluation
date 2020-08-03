<?php
/**
 * Created by PhpStorm.
 * User: zhengtong
 * Date: 2020/4/17
 * Time: 14:19
 */

class Comparemodel extends CI_Model
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
     * @param $uin, $compareName, $db
     * @return mixed
     * @throws 判断对拍名称是否有重复的
     */
    public function checkCompareName($uin, $compareName, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "select compare_name from eva_compare where user_id = '{$uin}' and compare_name = '{$compareName}'";
            $result = $db->query($sql);
        }
        return $result->result_array();
    }


    /**
     * @param $compareData, $uin, $db
     * @return mixed
     * @throws 插入compare数据
     */
    public function insertMyCompare($compareData, $uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $info = array();
            $columns = array('compare_name', 'user_id', 'first_code_id', 'second_code_id', 'input_data_path', 'max_input_group');
            foreach ($columns as $key) {
                if (isset($compareData[$key])) {
                    $info[$key] = $compareData[$key];
                }
            }
            $info['first_code_id'] = intval($info['first_code_id']);
            $info['second_code_id'] = intval($info['second_code_id']);
            $info['max_input_group'] = intval($info['max_input_group']);
            $info['user_id'] = $uin;
            $info['create_time'] = date('Y-m-d H:i:s');
            $info['update_time'] = $info['create_time'];
            if(isset($compareData['remarks'])){
                $info['remarks'] = $compareData['remarks'];
            }
            //$sql = "insert into eva_compare set compare_name = '{$info['compare_name']}', user_id = '{$uin}', input_data_path = '{$info['input_data_path']}', max_input_group = '{$info['max_input_group']}', first_code_id = '{$info['first_code_id']}', second_code_id = '{$info['second_code_id']}', create_time = '{$info['create_time']}', update_time = '{$info['update_time']}'";
            //echo $sql;
            //$code = $db->query($sql);
            $code = $db->insert('eva_compare', $info);
            if (!$code) {
                throw new Exception('insert to database failed ' . __LINE__, -9);
            }
        }
        return $code;
    }

    /**
     * @param $compare_id, $db
     * @return mixed
     * @throws 判断对拍是否是这个用户的
     */
    public function checkCompare($compare_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "select user_id from eva_compare where compare_id = '{$compare_id}'";
            $result = $db->query($sql);
        }
        $res = $result->row_array();
        return empty($res) ? 0 : $res['user_id'];
    }

    /**
     * @param $compare_id, $uin, $db
     * @return mixed
     * @throws 添加跑对拍的队列
     */
    public function addRunCompare($compare_id, $uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $info['compare_id'] = $compare_id;
            $info['user_id'] = $uin;
            $info['version'] = 0;
            $code = $db->insert('run_compare', $info);
            if (!$code) {
                throw new Exception('insert to database failed ' . __LINE__, -9);
            }
        }
        return true;
    }

    /**
     * @param $compare_id, $uin, $db
     * @return mixed
     * @throws 检查是否放入运行队列中
     */
    public function checkVersion($compare_id, $uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "select version from run_compare where compare_id = '{$compare_id}'";
            $result = $db->query($sql);
        }
        $res = $result->row_array();
        return empty($res) ? -1 : $res['version'];
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
     * @param $uin, $db
     * @return mixed
     * @throws 查找compare列表
     */
    public function queryCompareList($uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $uin = intval($uin);
            $limit = "";
            /*if ($count) {
                $limit = " limit $count";
            }*/
            $sql = "SELECT compare_id, compare_name, user_id, first_code_id, second_code_id, max_input_group, UNIX_TIMESTAMP(update_time) as update_time, UNIX_TIMESTAMP(create_time) as create_time from eva_compare WHERE user_id={$uin} ORDER BY update_time DESC $limit";
            $query = $db->query($sql);
            return $query->result_array();
        }
    }



    /**
     * @param $uin, $db, $compare_id
     * @return mixed
     * @throws 查找compare信息
     */
    public function queryCompareData($uin, $compare_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $uin = intval($uin);
            $sql = "SELECT compare_data from run_compare WHERE user_id={$uin} AND compare_id = {$compare_id}";
            $query = $db->query($sql);
            return $query->result_array();
        }
    }


    /**
     * @param $uin, $db, $compare_id
     * @return mixed
     * @throws compare信息
     */
    public function queryCompareDetail($uin, $compare_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $uin = intval($uin);
            $sql = "SELECT compare_name, first_code_id, second_code_id, max_input_group, input_data_path, remarks from eva_compare WHERE user_id={$uin} AND compare_id = {$compare_id}";
            $query = $db->query($sql);
            return $query->result_array();
        }
    }

    /**
     * @param $uin, $db, $compare_id
     * @return mixed
     * @throws 查看这个对拍的运行状态
     */
    public function checkCompareRunning($compare_id, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $sql = "SELECT version from run_compare WHERE compare_id = {$compare_id}";
            $query = $db->query($sql);
            return $query->result_array();
        }
    }

    /**
     * @param $db, $compare_id, $uin
     * @return mixed
     * @throws 删除run_compare中信息
     */
    public function delRunCompare($compare_id, $uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $code = $db->delete('run_compare', array('compare_id' => $compare_id, 'user_id' => $uin));
            if($code == true) {
                return true;
            }
            return false;
        }
    }

    /**
     * @param $db, $compare_id, $uin
     * @return mixed
     * @throws 删除eva_compare中信息
     */
    public function delEvaCompare($compare_id, $uin, $db)
    {
        if (empty($db)) {
            throw new Exception('database connect failed ' . __LINE__, -8);
        } else {
            $code = $db->delete('eva_compare', array('compare_id' => $compare_id, 'user_id' => $uin));
            if($code == true) {
                return true;
            }
            return false;
        }
    }
}
