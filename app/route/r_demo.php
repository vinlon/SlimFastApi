<?php  
/**
 * Demo Route
 */

use SlimFastAPI\Route;

//初始化Route实例
$route = new Route('\Controller\DemoController', '\Model\Demo' , 'demo');

//批量添加配置
$route->batchRegist([
    ['POST', 'create', 'create'],
    ['GET', 'info', 'getInfo', ['skip_auth' => true]],    //跳过身份验证
    ['POST', 'list', 'getList'],
    ['POST', 'update', 'update'],
    ['POST', 'manageStatus', 'manageStatus'],
    ['POST', 'delete', 'delete'],

    ['POST', 'batchCreate', 'batchCreate'],
    ['POST', 'updateByName', 'updateByName'],
    ['GET', 'infoByName', 'getInfoByName'],
    ['GET', 'listByRef', 'getListByRef'],
    ['POST', 'clear', 'clear'],

    ['GET', 'getIpLocation', 'getIpLocation'],

    ['POST', 'uploadFile', 'uploadFile']
]);



//Slim原生方法定义路由
$slim->group("/demo/", function(){
    $model = new \Model\Demo();

    $this->get("custom_route", function($request, $response) use ($model){
        //controller只能定义在路由方法中，否则构造函数中的getTicket方法会失败，因为ticket的处理放在Slim Middleware中
        $controller = new \Controller\DemoController(['skip_auth' => true]);

        $param = $_GET;
        $valid_param = $model->load($param);
        $response->withJson($valid_param, 200, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    });
});

