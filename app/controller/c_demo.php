<?php
/**
 *  Demo Controller
 */

namespace Controller;

use Service\IpService;
use DAO\EntityDAO;

/**
 * Demo管理
 */
class DemoController extends AppController
{
    /**
     * 构造函数
     */
    public function __construct($option = []){
        //调用父类构造函数
        parent::__construct($option);
        
        //统一创建实例
        $this->entity_dao = new EntityDAO();
        $this->redis0 = $this->getRedis(0);
    }

    /**
     * 添加entity
     * @author liwenlong
     */
    public function create($param){
        //检查参数
        $this->checkParam(['entity_name', 'entity_desc', 'ref_id'], $param);
        $entity_id = $this->entity_dao->insert($param);
        return $this->success(['entity_id' => $entity_id]);
    }

    /**
     * 批量添加entity
     * @author liwenlong
     */
    public function batchCreate($param){
        //检查参数
        $this->checkParam(['entity_list'], $param);
        $entity_list = $param['entity_list'];
        foreach ($entity_list as $item) {
            $this->checkParam(['entity_name', 'entity_desc', 'ref_id'], $item);
        }

        $id_list = $this->entity_dao->batchInsert($entity_list);

        return $this->success(['id_list' => $id_list]);
    }

    /**
     * 修改entity
     * @author liwenlong
     */
    public function update($param){
        //检查参数
        $this->checkParam(['entity_id'], $param);

        $entity_id = $param['entity_id'];

        //检查entity
        $entity = $this->entity_dao->load($entity_id);
        if(empty($entity)){
            return $this->error(CustomError::ENTITY_ID_NOT_FOUND);
        }

        //修改entity
        $this->entity_dao->update($entity_id, $param);

        //清空缓存
        $redis_entity_cache_key = 'test_entity_' . $entity_id;
        $this->redis0->del($redis_entity_cache_key);

        return $this->success();
    }

    /**
     * 根据名称修改entity
     * @author liwenlong
     */
    public function updateByName($param){
        //检查参数
        $this->checkParam(['entity_name', 'entity_desc'], $param);

        //修改entity
        $count = $this->entity_dao->udpateByName($param['entity_name'], $param);

        return $this->success(['update_count' => $count]);
    }

    /**
     * 查询entity信息
     * @author liwenlong
     */
    public function getInfo($param){
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
            $entity = $this->entity_dao->load($entity_id);
            if(empty($entity)){
                return $this->error(CustomError::ENTITY_ID_NOT_FOUND);
            }

            $result = $this->parseEntity($entity);

            //缓存数据（有效期15分钟）
            $cache = $result;
            $cache['_cached'] = true;
            $this->redis0->setex($redis_entity_cache_key, 15*60, json_encode($cache));
        }
        return $this->success($result);
    }


    /**
     * 根据名称查询Entity,只返回第一条记录
     * @author liwenlong
     */
    public function getInfoByName($param){
        //检查参数
        $this->checkParam(['entity_name'], $param);

        $entity = $this->entity_dao->selectByName($param['entity_name']);

        return $this->success($entity);
    }

    /**
     * 查询entity列表
     * @author liwenlong
     */
    public function getList($param){
        //检查参数
        $this->checkParam(['skip', 'size'], $param);

        //查询列表
        $skip = $param['skip'];
        $size = $param['size'];

        $entity_list = $this->entity_dao->paging($skip, $size, $param);

        foreach ($entity_list['list'] as &$entity) {
            $entity = $this->parseEntity($entity);
        }
        return $this->success($entity_list);
    }

    /**
     * 根据RefId查询列表
     * @author liwenlong
     */
    public function getListByRef($param){
        //检查参数
        $this->checkParam(['skip', 'size', 'ref_id'], $param);

        $entity_list = $this->entity_dao->getListByRefId($param['skip'], $param['size'], $param['ref_id']);

        return $this->success($entity_list);
    }


    /**
     * 数据转换
     * @author liwenlong
     */
    private function parseEntity($entity){
        //返回的字段列表
        $field_list = ['id', 'entity_name', 'entity_desc', 'ref_id', 'status', 'create_time', 'update_time'];
        $result = $this->arraySelect($entity,$field_list);

        //状态转码
        $result['status'] = array_search($result['status'], $this->entity_dao->entity_status);
        return $result;
    }

    /**
     * entity状态管理
     * @author liwenlong
     */
    public function manageStatus($param){
        //检查参数
        $this->checkParam(['entity_id', 'status'], $param);

        $entity_id = $param['entity_id'];
        $status = $param['status'];

        //查询entity信息
        $entity = $this->entity_dao->load($entity_id);
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

        //清空缓存
        $redis_entity_cache_key = 'test_entity_' . $entity_id;
        $this->redis0->del($redis_entity_cache_key);

        return $this->success();

    }

    /**
     * 启用
     * @author liwenlong
     */
    private function enable($entity_id){
        $this->entity_dao->updateStatus($entity_id, $this->entity_dao->entity_status['ENABLED']);
    }

    /**
     * 禁用
     * @author liwenlong
     */
    private function disable($entity_id){
        $this->entity_dao->updateStatus($entity_id, $this->entity_dao->entity_status['DISABLED']);
    }


    /**
     * entity删除
     * @author liwenlong
     */
    public function delete($param){
        //检查参数
        $this->checkParam(['entity_id'], $param);

        $entity_id = $param['entity_id'];

        //判断entity是否存在
        $entity = $this->entity_dao->load($entity_id);
        if(empty($entity)){
            return $this->error(CustomError::ENTITY_ID_NOT_FOUND);
        }

        //删除BeaconTask
        $this->entity_dao->delete($entity_id);

        //清空缓存
        $redis_entity_cache_key = 'test_entity_' . $entity_id;
        $this->redis0->del($redis_entity_cache_key);

        return $this->success();
    }

    /**
     * 清除已删除的数据
     * @author liwenlong
     */
    public function clear($param){
        $count = $this->entity_dao->clear();
        return $this->success(['clear_count' => $count]);
    }

    /**
     * 获取IP对应的地址
     * @author liwenlong
     */
    public function getIpLocation($param){
        $ip = isset($param['ip']) ? $param['ip'] : $this->getClientIp();
        $source = isset($param['source']) ? strtolower($param['source']) : '';

        $valid_source = ['baidu', 'taobao'];

        //检查source有效性
        if(!in_array($source, $valid_source)){
            return $this->error(CustomError::INVALID_IP_LOCATION_SOURCE);
        }

        $ip_service = new IpService();

        $location = $ip_service->getLocation($ip, $source);

        $result = [
            'ip' => $ip,
            'source' => $source,
            'location' => $location
        ];

        return $this->success($result);
    }

    /**
     * 文件上传
     * @author liwenlong
     */
    public function uploadFile($param, $files){
        return $this->success([
            'files' => $files,
            'param' => $param
        ]);
    }
}
