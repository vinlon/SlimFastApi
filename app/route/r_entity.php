<?php
/**
 * Route Entity CRUD Demo
 */
use Controller\EntityController;

$app->group("/entity/", function(){
    $entity = new EntityController();

    //实体创建
    $this->post("create", function($request, $response) use ($entity){
        $param = $request->getParsedBody();
        $result = $entity->create($param);
        return $response->withJson($result, 200, JSON_NUMERIC_CHECK);
    });

    //实体修改
    $this->post("update", function($request, $response) use ($entity){
        $param = $request->getParsedBody();
        $result = $entity->update($param);
        return $response->withJson($result, 200, JSON_NUMERIC_CHECK);
    });

    //实体查询
    $this->get("info", function($request, $response) use ($entity){
        $param = $_GET;
        $result =  $entity->getInfo($param);
        return $response->withJson($result, 200, JSON_NUMERIC_CHECK);
    });

    //实体列表查询
    $this->post("list", function($request, $response) use ($entity){
        $param = $request->getParsedBody();
        $result = $entity->getList($param);
        return $response->withJson($result, 200, JSON_NUMERIC_CHECK);
    });

    //实体状态管理
    $this->post("manageStatus", function($request, $response) use ($entity){
        $param = $request->getParsedBody();
        $result = $entity->manageStatus($param);
        return $response->withJson($result, 200, JSON_NUMERIC_CHECK);
    });

	//实体删除
    $this->post("delete", function($request, $response) use ($entity){
        $param = $request->getParsedBody();
        $result = $entity->delete($param);
        return $response->withJson($result, 200, JSON_NUMERIC_CHECK);
    });
});



?>
