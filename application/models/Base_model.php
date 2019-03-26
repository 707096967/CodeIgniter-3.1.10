<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Base_model extends CI_Model
{
    /*
     * 1. 通用数据库操作方法 增删改查
     * 2. 部分权限
     */
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    /*
     * 获取值
     * $table: 表名
     * $select: 查找项， $select = '*' 或 $select = 'id,title'
     * $where: 条件项 $where= 'id=1' 或 $where = 'id=1 and title="blah"'
     * $order: $order = 'id desc'
     * 返回值是数组
     */
    function _get_key($table, $select = '', $where = '', $order = '')
    {
        if ($select) {
            $this->db->select($select);
        }
        if ($where) {
            $this->db->where($where);
        }
        if ($order) {
            $this->db->order_by($order);
        }

        $query = $this->db->get($table);
        return $query->result_array();
    }

    function _insert_key($table, $data)
    {
        $this->db->insert($table, $data);
        // 如果$table没有配置自增主键，则insert_id返回值为0
        return $this->db->insert_id();
    }

    function _update_key($table, $data, $where)
    {
        $this->db->where($where);
        $this->db->update($table, $data);
        return $this->db->affected_rows();
    }

    function _key_exists($table, $where)
    {
        $this->db->where($where);
        $this->db->from($table);
        return $this->db->count_all_results();
    }

    function _delete_key($table, $where)
    {
        $this->db->where($where);
        $this->db->delete($table);
        return $this->db->affected_rows();
    }


    function total($table, $field, $keyword)
    {
        $sql = "select count(*) numrows from $table where $field like '%$keyword%' ";
        $query = $this->db->query($sql);
        if (($query->row_array()) == null) {
            return 0;
        } else {
            $result = $query->result_array();
            return $result;
        }
    }

    /* 根据perm_type 获取关联的基础表名称
     * @return array
     */
    function getBaseTable($perm_type)
    {
        $sql = "select r_table from sys_perm_type where type = '" . $perm_type . "'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /*
     *  根据token, perm_type 获取 perm_id,perm_type,r_id
     *  @return array
     */
    function getPerm($basetable, $token, $perm_type, $menuCtrl)
    {
        $hasCtrl = $menuCtrl ? "" : " WHERE basetbl.type != 2";

        $sql = "SELECT
                        t.id perm_id,
                        basetbl.*
                    FROM
                        (
                            SELECT
                                p.*
                            FROM
                                sys_user_token ut,
                                sys_user_role ur,
                                sys_role_perm rp,
                                sys_perm p
                            WHERE
                                ut.token = '" . $token . "'
                            AND ur.user_id = ut.user_id
                            AND rp.role_id = ur.role_id
                            AND p.id = rp.perm_id
                            AND p.perm_type = '" . $perm_type . "'
                        ) t
                    LEFT JOIN " . $basetable. " basetbl ON t.r_id = basetbl.id" . $hasCtrl ." order by basetbl.listorder" ;

        $query = $this->db->query($sql);
        return $query->result_array();

    }

    function getCtrlPerm($token)
    {
        $sql = "SELECT
                        basetbl.path
                    FROM
                        (
                            SELECT
                                p.*
                            FROM
                                sys_user_token ut,
                                sys_user_role ur,
                                sys_role_perm rp,
                                sys_perm p
                            WHERE
                                ut.token = '" . $token . "'
                            AND ur.user_id = ut.user_id
                            AND rp.role_id = ur.role_id
                            AND p.id = rp.perm_id
                            AND p.perm_type = 'menu'
                        ) t
                    LEFT JOIN  sys_menu basetbl ON t.r_id = basetbl.id 
                    WHERE basetbl.type = 2";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /*
     * 菜单是否拥有子节点
     */
    function hasChildMenu($id)
    {
        $sql = "SELECT getChildLst(" . $id . ") children";
        $query = $this->db->query($sql);
        // var_dump($query->result_array()[0]["children"]);
        // string(14) "$,2,5,6,8,9,10"
        $array = explode(",", $query->result_array()[0]["children"]);

        if (count($array) > 2) {
            return true;
        } else {
            return false;
        }
    }

}