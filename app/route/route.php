<?php

require "r_entity.php";

//Middleware：在接口主体执行的前后添加验证和统计功能
$app->add(new Controller\Invoker());

// Default
$app->get("/", function ($request, $response, $args) {
    return $response->write(PROJECT_NAME . '(' . PROJECT_OWNER . ')');
});

// Hello World
$app->get("/hello/{name}", function ($request, $response, $args) {
    return $response->write("Hello " . $args['name']);
});

//Throw Exception
$app->get("/throw/exception",function($request,$response,$args) {
    throw new Exception("Throw Exception Test", 1);
});


?>
