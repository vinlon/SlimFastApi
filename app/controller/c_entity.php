<?php
/**
 *  Entity Controller
 */

namespace Controller;

use CustomError;
use Model\Entity;

/**
 * Entity管理
 */
class EntityController extends BaseController
{
	/**
	 * 构造函数
	 */
	public function __construct(){
        //统一创建Model实例
        $this->entity = new Entity();
        $this->redis0 = $this->getRedis(0);
    }

    /**
     * 添加entity
     * @param array entity_name \ entity_desc \ ref_id
     * @return array return_code \ return_msg \ data
     * @author liwenlong
     */
    public function create($param){
		//身份验证
		$this->authenticate();

    	//检查参数
        $this->checkParam(['entity_name', 'entity_desc', 'ref_id'], $param);

       	$entity_id = $this->entity->insert($param);
       	return $this->success(['entity_id' => $entity_id]);
    }

    /**
     * 修改entity
     * @param entity_id \ entity_name \ entity_desc \ ref_id
     * @return array return_code \ return_msg \ data
     * @author liwenlong
     */
    public function update($param){
		//身份验证
		$this->authenticate();

		//检查参数
        $this->checkParam(['entity_id'], $param);

        $entity_id = $param['entity_id'];

        //检查entity
        $entity = $this->entity->load($entity_id);
        if(empty($entity)){
            return $this->error(CustomError::ENTITY_ID_NOT_FOUND);
        }

        //修改entity
        $this->entity->update($entity_id, $param);
        return $this->success();
    }

    /**
     * entity删除
     * @param array entity_id
     * @return array return_code \ return_msg \ data
     * @author liwenlong
     */
    public function delete($param){
		//身份验证
		$this->authenticate();

    	//检查参数
        $this->checkParam(['entity_id'], $param);

        $entity_id = $param['entity_id'];

        //判断entity是否存在
        $entity = $this->entity->load($entity_id);
        if(empty($entity)){
        	return $this->error(CustomError::ENTITY_ID_NOT_FOUND);
        }

        //删除BeaconTask
        $count = $this->entity->delete($entity_id);
        return $this->success();
    }

    /**
     * 查询entity信息
     * @param array entity_id
     * @return array return_code \ return_msg \ data
     * @author liwenlong
     */
    public function getInfo($param){
		//检查ticket
		$this->authenticate();

    	//检查参数
    	$this->checkParam(['entity_id'], $param);

        $entity_id = $param['entity_id'];

        //检查缓存
        $redis_entity_cache_key = 'test_entity_' . $entity_id;
        $cache = $this->redis0->get($redis_entity_cache_key);
        if($cache){
            //缓存命中
            $result = json_decode($cache, true);
        }else{
            //缓存未命中
            //查询entity信息
            $entity = $this->entity->load($entity_id);
            if(empty($entity)){
                return $this->error(CustomError::ENTITY_ID_NOT_FOUND);
            }

            //返回字段列表
            $field_list = ['id', 'entity_name', 'entity_desc', 'ref_id', 'status', 'create_time', 'update_time'];
            $result = $this->array_select($entity,$field_list);

            //状态转码
            $result['status'] = array_search($result['status'],$this->entity->entity_status);

            //缓存数据（有效期15分钟）
            $cache = $result;
            $cache['_cached'] = true;
            $this->redis0->setex($redis_entity_cache_key, 15*60, json_encode($cache));
        }
        return $this->success($result);
    }

    /**
     * 查询entity列表
     * @param array size \ skip
     * @return array return_code \ return_msg \ data
     * @author liwenlong
     */
    public function getList($param){
		//检查ticket
		$this->authenticate();

    	//检查参数
    	$this->checkParam(['skip', 'size'], $param);

        //查询列表
        $skip = intval($param['skip']);
        $size = intval($param['size']);

        $entity_list = $this->entity->paging($skip, $size, $param);
        $selected = [];

        //返回的字段列表
        $field_list = ['id', 'entity_name', 'entity_desc', 'ref_id', 'status', 'create_time', 'update_time'];

        foreach ($entity_list['list'] as $entity) {
            $result = $this->array_select($entity,$field_list);

            //状态转码
            $result['status'] = array_search($result['status'], $this->entity->entity_status);
            $selected[] = $result;
        }
        $entity_list['list'] = $selected;
        return $this->success($entity_list);
    }

    /**
     * entity状态管理
     * @param array entity_id \ status
     * @return array return_code \ return_msg \ data
     * @author liwenlong
     */
    public function manageStatus($param){
		//检查ticket
		$this->authenticate();

        //检查参数
        $this->checkParam(['entity_id', 'status'], $param);

        $status = $param['status'];
        $entity_id = $param['entity_id'];

        //查询entity信息
        $entity = $this->entity->load($entity_id);
        if(empty($entity)){
			return $this->error(CustomError::ENTITY_ID_NOT_FOUND);
        }

        //状态判定
        switch ($status) {
            case 'ENABLED':
                $this->enable($entity_id);
                break;
            case 'DISABLED':
                $this->disable($entity_id);
                break;
            default:
                return $this->error(CustomError::STATUS_INVALID);
                break;
        }
        return $this->success();

    }

	/**
     * 启用
     * @param int $entity_id EntityID
     * @author liwenlong
     */
    private function enable($entity_id){
        $this->entity->updateStatus($entity_id, $this->entity->entity_status['ENABLED']);
    }

    /**
     * 禁用
     * @param int $entity_id EntityID
     * @author liwenlong
     */
    private function disable($entity_id){
        $this->entity->updateStatus($entity_id, $this->entity->entity_status['DISABLED']);
    }
}
