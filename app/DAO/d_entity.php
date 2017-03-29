<?php
/**
 * Entity DAO
 */

namespace DAO;

/**
* 实体数据库操作类
*/
class EntityDAO extends AppDAO
{
    /**
     * 数据库表名
     */
    const T_TEST_ENTITY = "test_entity";

    /**
     * 状态的定义
     * ENABLED 启用
     * DISABLED 禁用
     * @author liwenlong
     */
    public $entity_status = [
        'ENABLED' => 0,
        'DISABLED' => 10
    ];

    /**
     * 添加实体
     * @author liwenlong
     */
    public function insert($param){
        $now = $this->isoDateTime();
        return $this->insertRow(self::T_TEST_ENTITY, [
            'entity_name' => $param['entity_name'],
            'entity_desc' => $param['entity_desc'],
            'ref_id' => $param['ref_id'],
            'status' => $this->entity_status['ENABLED'],
            'create_time' => $now,
            'update_time' => $now
        ]);
    }

    /**
     * 批量添加记录
     * @author liwenlong
     */
    public function batchInsert($param){
        $now = $this->isoDateTime();
        $entities = [];
        foreach ($param as $item) {
            $entity['entity_name'] = $item['entity_name'];
            $entity['entity_desc'] = $item['entity_desc'];
            $entity['ref_id'] = $item['ref_id'];
            $entity['status'] = $this->entity_status['ENABLED'];
            $entity['create_time'] = $now;
            $entity['update_time'] = $now;

            $entities[] = $entity;
        }
        return $this->insertRows(self::T_TEST_ENTITY, $entities);
    }


    /**
     * 修改实体
     * @author liwenlong
     */
    public function update($entity_id, $param){
        $update_time = $this->isoDateTime();

        //可修改字段
        $field_list = ['entity_name', 'entity_desc', 'ref_id'];
        foreach($field_list as $field){
            if(array_key_exists($field, $param)){
                $entity[$field] = $param[$field];
            }
        }
        $entity['update_time'] = $update_time;

        return $this->updateRow(self::T_TEST_ENTITY, $entity_id, $entity);
    }

    /**
     * 修改实体(根据名称)
     * @author liwenlong
     */
    public function udpateByName($entity_name, $param){
        $update_time = $this->isoDateTime();

        //可修改字段
        $field_list = ['entity_desc', 'ref_id'];
        foreach($field_list as $field){
            if(array_key_exists($field, $param)){
                $entity[$field] = $param[$field];
            }
        }
        $entity['update_time'] = $update_time;

        return $this->updateByFields(self::T_TEST_ENTITY, $entity, [
            'entity_name' => $entity_name,
            'is_delete' => false
        ]);
    }

    /**
     * 查询单个记录
     * @author liwenlong
     */
    public function load($entity_id){
        $entity = $this->selectByFields(self::T_TEST_ENTITY, 
        [
            'id' => $entity_id,
            'is_delete' => false
        ],
        ['id', 'entity_name', 'entity_desc', 'ref_id', 'status', 'create_time', 'update_time']);
        return $entity;
    }

    /**
     * 根据名称查询单个记录
     * @author liwenlong
     */
    public function selectByName($entity_name){
        $entity = $this->selectByFields(self::T_TEST_ENTITY, [
            'entity_name' => $entity_name,
            'is_delete' => false
        ]);
        return $entity;
    }

    /**
     * 删除entity
     * @author liwenlong
     */
    public function delete($entity_id){
        $entity['is_delete'] = 1;
        $entity['update_time'] = $this->isoDateTime();

        return $this->updateRow(self::T_TEST_ENTITY, $entity_id, $entity);
    }

    /**
     * 清理已删除的数据
     * @author liwenlong
     */
    public function clear(){
        return $this->deleteByFields(self::T_TEST_ENTITY, [
            'is_delete' => true
        ]);
    }

    /**
     * entity分页查询
     * @author liwenlong
     */
    public function paging($skip, $size, $param){
        $ref_id = isset($param['ref_id']) ? $param['ref_id'] : null;
        $query = isset($param['query']) && $param['query'] != '' ? "%{$param['query']}%" : null;
        $id_list = isset($param['id_list']) && count($param['id_list']) > 0 ? $param['id_list'] : null;
        $sql = 'SELECT id,entity_name,entity_desc,ref_id,status,create_time,update_time FROM ' . self::T_TEST_ENTITY;
        $sql_count = 'SELECT COUNT(1) as count FROM ' . self::T_TEST_ENTITY;

        $conditions = [
            [0, 'WHERE', 'is_delete = ?'],
            [$ref_id, 'AND', 'ref_id = ?'],
            [$query, 'AND', '(entity_name like ? OR entity_desc like ?)'],
            [$id_list, 'AND', 'id IN (?)']
        ];

        $count_pieces = array_merge([[$sql_count]], $conditions);

        $count = $this->getCell($count_pieces);

        if($count == 0){
            return ['count' => 0, 'list' => []];
        }

        $paging_pieces = array_merge([[$sql]], $conditions);
        $paging_pieces[] = ["LIMIT {$skip},{$size}"];

        $list = $this->getAll($paging_pieces);
        return ['count' => $count, 'list' => $list];
    }

    /**
     * 根据RefID分页查询列表
     * @author liwenlong
     */
    public function getListByRefId($skip, $size, $ref_id){
        $skip = (int)$skip;
        $size = (int)$size;

        $sql = 'SELECT id,entity_name,entity_desc,ref_id,status,create_time,update_time FROM ' . self::T_TEST_ENTITY . ' WHERE ref_id = ? AND is_delete = 0 LIMIT ?,?';
        $sql_count = 'SELECT COUNT(1) FROM ' . self::T_TEST_ENTITY . ' WHERE ref_id = ? AND is_delete = 0 LIMIT ?,?';
        $binding = [$ref_id, $skip, $size];

        $count = $this->getCell($sql_count, $binding);

        $list = $this->getAll($sql, $binding);
        
        return ['count' => $count, 'list' => $list];
    }

    /**
     * entity状态管理
     * @author liwenlong
     */
    public function updateStatus($entity_id, $status){
        $entity['status'] = $status;
        $entity['update_time'] = $this->isoDateTime();
        return $this->updateRow(self::T_TEST_ENTITY, $entity_id, $entity);
    }


}



