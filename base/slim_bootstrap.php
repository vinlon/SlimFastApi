<?php 
/**
 * Slim启动
 */

namespace SlimFastAPI;

use Slim;
use Exception;

$slim = new Slim\App([
    'settings' => [
        // Only set this if you need access to route within middleware
        'determineRouteBeforeAppMiddleware' => true
    ]
]);
//添加中间件
$slim->add(new Invoker());

//异常处理
$c = $slim->getContainer();

//禁用Slim的异常处理
unset($c['errorHandler']);

//页面未找到
$c['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        $controller = new BaseController();
        $result = $result = $controller->returnArray(GlobalError::PAGE_NOT_FOUND);
        return $c['response']
            ->withJson($result,404);
    };
};
//请求类型不正确
$c['notAllowedHandler'] = function ($c) {
    return function ($request, $response, $methods) use ($c) {
        $controller = new BaseController();
        $result = $controller->returnArray(GlobalError::METHOD_NOT_ALLOWED);
        return $c['response']
            ->withHeader('Allow', implode(', ', $methods))
            ->withJson($result, 405);
    };
};

//定义默认路由
//# home page
$slim->get('/', function ($request, $response, $args) {
    $project_name = defined('PROJECT_NAME') ? PROJECT_NAME : 'UNDEFINED';
    $project_owner = defined('PROJECT_OWNER') ? PROJECT_OWNER : 'UNDEFINED';
    $error_prefix = defined('ERROR_PREFIX') ? ERROR_PREFIX : 'UNDEFINED';
    
    $controller = new BaseController();

    return $response->withJson([
        'ERROR_PREFIX' => $error_prefix,
        'PROJECT_NAME' => $project_name,
        'PROJECT_OWNER' => $project_owner,
        'client_ip' => $controller->getClientIp(),
        'server_ip' => $controller->getServerIp()
    ], 200);  
});

//# Hello World
$slim->get('/hello/{name}', function ($request, $response, $args) {
    return $response->write('<pre>HELLO ' . strtoupper($args['name']) . '</pre>');
});

//# Throw Exception
$slim->get('/throw/{exception}',function($request,$response,$args) {
    throw new Exception('Throw Exception Test', intval($args['exception']));
});

//引入Route文件, 默认使用项目根目录下的route.php文件
require(ROUTE_PATH);

//注册业务路由
$route_list = Route::getList();
foreach (Route::$type_list as $type) {
    $type = strtolower($type);
    if(!isset($route_list[$type])){
        continue;
    }

    foreach ($route_list[$type] as $route => $callable) {
        $slim->$type($route, function ($request, $response, $args) use ($type, $callable) {
            if($type == 'get'){
                $param = $_GET;
            }else{
                $param = $request->getParsedBody();
            }

            $controller_name = $callable[0];
            $method_name = $callable[1];
            $model = $callable[2];
            $option = $callable[3];
            //如果定义了Model，通过Model统一加载并验证数据
            if(!empty($model)){
                $entity = new $model($option);
                $param = $entity->load($param);
            }

            $controller_instance = new $controller_name($option);

            /**
             * 处理上传的文件数据
             */
            $files = $_FILES;
            $result = call_user_func_array([$controller_instance, $method_name], [$param, $files]);

            //如果数据不为NULL则返回
            if($result !== NULL){
                $response->withJson($result, 200, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        });
    }
}


$slim->run();

