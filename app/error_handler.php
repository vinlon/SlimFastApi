<?php
/**
 * Error Handler
 */

use Service\ErrorHandler;

error_reporting(E_ALL); //显示所有错误信息

//通过php的全局error_handler和exception_handler以及Slim的自定义错误处理，尽可能捕获到所有的异常
set_error_handler(function($error_code, $error_message, $error_file, $error_line){
    handleError(
        $error_code,
        $error_message,
        $error_file,
        $error_line,
        "ERROR_HANDLER");
});
set_exception_handler(function($exception){
    handleError(
        $exception->getCode(),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        "EXCEPTION_HANDLER");
});

if(isset($app)){
    //For Slim Framework
    $c = $app->getContainer();
    //异常捕获
    $c['errorHandler'] = function ($c){
        return function ($request, $response, $exception){
            handleError(
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                "SLIM_CUSTOM_HANDLER"
            );
        };
    };

    //页面未找到
    $c['notFoundHandler'] = function ($c) {
        return function ($request, $response) use ($c) {
            $error_handler = new ErrorHandler();
            $result = $result = $error_handler->returnArray(404,"Page Not Found");
            return $c['response']
                ->withJson($result,404);
        };
    };
    //请求类型不正确
    $c['notAllowedHandler'] = function ($c) {
        return function ($request, $response, $methods) use ($c) {
            $error_handler = new ErrorHandler();
            $result = $error_handler->returnArray(405, "Method must be one of: " . implode(", ", $methods));
            return $c['response']
                ->withHeader('Allow', implode(', ', $methods))
                ->withJson($result, 405);
        };
    };
}

/**
 * 错误处理
 * @param int $error_code 错误码
 * @param string $error_message 错误信息
 * @param string $error_file 错误码
 * @param string $error_code 错误文件路径
 * @param string $error_line 错误发生的行数
 * @param string $source 错误来源
 */
function handleError($error_code, $error_message, $error_file, $error_line, $source){
    //返回码以500开头
    $return_code = 50000 + $error_code;
    $response_code = 500;

    //Error Code转义
    switch($error_code)
    {
        case CustomError::AUTH_ERR_CODE :
            $custom_code = "AUTH_ERROR";
            $return_code = 20001;
            $response_code = 200;
            break;
        case CustomError::PARAM_ERR_CODE :
            $custom_code = "PARAM_ERROR";
            $return_code = 20002;
            $response_code = 200;
            break;

        case E_ERROR: $custom_code = "ERROR"; break;
        case E_WARNING: $custom_code = "WARNING"; break;
        case E_PARSE: $custom_code = "PARSE"; break;
        case E_NOTICE: $custom_code = "NOTICE"; break;
        case CustomError::REDIS_ERR_CODE : $custom_code = "REDIS_ERROR"; break;
        case CustomError::CURL_ERR_CODE : $custom_code = "CURL_ERROR"; break;
        case CustomError::MYSQL_ERR_CODE : $custom_code = "MYSQL_ERROR"; break;

        default :
        	$custom_code = "OTHER[{$error_code}]";
            $return_code = 50010;
        	break;
    }

    $error_handler = new ErrorHandler();
    if($response_code === 500){
        if(DEBUG){
            $result = $error_handler->returnArray($return_code, "server error", [
                'custom_code' => $custom_code,
                'error_message' => $error_message,
                'error_file' => $error_file,
                'error_line' => $error_line,
                'source' => $source
            ]);
            //调试模式下将错误信息输出到日志
            $error_handler->addDebug('error_handler', $result);
        }else{
            //非调试模式下调用错误处理接口通知维护人员
            $json = $error_handler->handle($custom_code, $error_message, $error_file, $error_line, $source);
            $result = $error_handler->returnArray($return_code, "server error");
        }
    }else{
        $result = $error_handler->returnArray($return_code, $error_message);
    }

    header("HTTP/1.1 {$response_code}");
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

?>
