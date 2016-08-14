<?php
/**
 * Entity Model
 */

namespace Model;

use CustomError;
use R;
use Exception;

/**
* 用户
*/
class Entity extends BaseModel
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
	 * @param array
	 * @return int 影响行数
	 * @author liwenlong
	 */
	function insert($param){
		try {
			$create_time = date("Y-m-d H:i:s", time());

			$entity = R::dispense(self::T_TEST_ENTITY);
	        $entity->entity_name = $param['entity_name'];
	        $entity->entity_desc = $param['entity_desc'];
	        $entity->ref_id = $param['ref_id'];
	        $entity->status = $this->entity_status['ENABLED'];
	        $entity->create_time = $create_time;
	        $entity->update_time = $create_time;

	        $task_id = R::store($entity);
	        return $task_id;
		} catch (Exception $e) {
			throw new Exception($e->getMessage(),CustomError::MYSQL_ERR_CODE);
		}
	}

	/**
	 * 修改实体
	 * @param int $entity_id 待修改的实体ID
	 * @param array $param 待修改的参数
	 * @return int 影响行数
	 * @author liwenlong
	 */
	function update($entity_id, $param){
		try {
			$update_time = date("Y-m-d H:i:s", time());
			$entity = R::load(self::T_TEST_ENTITY, $entity_id);
			//可修改字段
			$field_list = ['entity_name', 'entity_desc', 'ref_id'];
			foreach($field_list as $field){
				if(array_key_exists($field, $param)){
					$entity->$field = $param[$field];
				}
			}
	        $entity->update_time = $update_time;
	        R::store($entity);
	        return 1;
        } catch (Exception $e) {
			throw new Exception($e->getMessage(), CustomError::MYSQL_ERR_CODE);
        }
	}

	/**
     * 查询单个记录
     * @param int $entity_id 实体ID
     * @return array 实体详情
     * @author liwenlong
     */
    function load($entity_id){
        try {
        	$entity = R::load(self::T_TEST_ENTITY, $entity_id);
	        if($entity->id == 0 || $entity->is_delete == 1){
	            return array();
	        }else{
	            return $entity->export();
	        }
        } catch (Exception $e) {
			throw new Exception($e->getMessage(), CustomError::MYSQL_ERR_CODE);
        }
    }

    /**
     * 删除entity
     * @param int $entity_id entity_id
     * @return int 受影响行数
     * @author liwenlong
     */
    function delete($entity_id){
		try {
			$update_time = date("Y-m-d H:i:s", time());

	        $entity = R::load(self::T_TEST_ENTITY, $entity_id);
	        $entity->is_delete = 1;
	        $entity->update_time = $update_time;
	        R::store($entity);
	        return 1;
		} catch (Exception $e) {
			throw new Exception($e->getMessage(), CustomError::MYSQL_ERR_CODE);
		}
    }

    /**
	 * entity分页查询
	 * @param int $skip 跳过的记录数量
	 * @param int $size 获取的记录数量
	 * @param array $param 查询参数
	 * @return array 查询结果
	 * @author liwenlong
	 */
	function paging($skip, $size, $param){
		try {
			$skip = (int)$skip;
	        $size = (int)$size;

	        $ref_id = isset($param['ref_id']) ? $param['ref_id'] : false;
	        $query = isset($param['query']) ? "%{$param['query']}%" : false;
	        $ids = isset($param['ids']) ? $param['ids'] : false;

	        $sql = "SELECT id,entity_name,entity_desc,ref_id,status,create_time,update_time FROM ".self::T_TEST_ENTITY;
	        $sql_count = "SELECT COUNT(*) as count FROM ".self::T_TEST_ENTITY;

	        $conditions = [
	            [$ref_id, "AND", "ref_id = ?"],
	            [$query, "AND", "entity_name like ?"],
	            [$ids, "AND", "id = ?"],
	            [0, "AND", "is_delete = ?"]
	        ];

	        $count = $this->getCell($sql_count, $conditions);
	        if($count == 0){
	        	return ['count' => 0, 'list' => []];
	        }

	        $list = $this->getAll($sql, $conditions, false ," limit {$skip},{$size}");
	        return ['count' => $count, 'list' => $list];
		} catch (Exception $e) {
			throw new Exception($e->getMessage(), CustomError::MYSQL_ERR_CODE);
		}
	}

	/**
	 * entity状态管理
	 * @param int $entity_id entity ID
	 * @param int status entity状态
	 * @return int 影响行数
	 * @author liwenlong
	 */
	function updateStatus($entity_id, $status){
		try {
			$entity = R::load(self::T_TEST_ENTITY, $entity_id);
			$entity->status = $status;
			R::store($entity);
			return 1;
		} catch (Exception $e) {
			throw new Exception($e->getMessage(), CustomError::MYSQL_ERR_CODE);
		}

	}


}


?>
