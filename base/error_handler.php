<?php
/**
 * Error Handler
 */

namespace SlimFastAPI;

use Curl\Curl;

error_reporting(0); //禁用php自身的错误信息显示

//通过php的全局error_handler和exception_handler以及register_shutdown_function，尽可能捕获到所有的异常

set_exception_handler(function($exception){
    $trace = getTrace($exception);

    handleError(
        $exception->getCode(),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $trace,
        'EXCEPTION_HANDLER');
});
set_error_handler(function($error_code, $error_message, $error_file, $error_line, $error_context){
    $trace = getTrace();

    handleError(
        $error_code,
        $error_message,
        $error_file,
        $error_line,
        $trace,
        'ERROR_HANDLER');
});

//捕获Fetal Error
register_shutdown_function(function(){
    $error = error_get_last();
    if(isset($error['type']) && !empty($error['type'])){
        handleError(
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line'],
            [],
            'REGISTER_SHUTDOWN_FUNCTION');  
    }
}); 
 

/**
 * 错误处理
 * @param string $error_code 错误码
 * @param string $error_message 错误信息
 * @param string $error_file 错误码
 * @param string $error_code 错误文件路径
 * @param string $error_line 错误发生的行数
 * @param string $trace 方法调用栈
 * @param string $source 错误来源
 * @author liwenlong
 */
function handleError($error_code, $error_message, $error_file, $error_line, $trace, $source){
    $controller = new BaseController();
    //注：如果Base, GlobalError, BaseController或BaseService出现错误，则该方法无法正常使用，该方法内出现的异常无法进行捕获和处理
    //HTTP状态码使用500
    $response_code = 500;
    
    //Error Code转义
    switch($error_code)
    {
        case GlobalError::AUTH_ERR_CODE :
            $custom_error = GlobalError::AUTHENTICATION_FAIL;
            break;
        case GlobalError::PARAM_ERR_CODE :
            $custom_error = GlobalError::PARAM_ERROR;
            break;

        case E_ERROR: 
            $custom_error = GlobalError::E_ERROR;
            break;
        case E_WARNING: 
            $custom_error = GlobalError::E_WARNING;
            break;
        case E_PARSE: 
            $custom_error = GlobalError::E_PARSE;
            break;
        case E_NOTICE: 
            $custom_error = GlobalError::E_WARNING;
            break;

        case GlobalError::REDIS_ERR_CODE : 
            $custom_error = GlobalError::REDIS_ERROR;
            break;
        case GlobalError::CURL_ERR_CODE :
            $custom_error = GlobalError::CURL_ERROR;
            break;
        case GlobalError::MYSQL_ERR_CODE : 
            $custom_error = GlobalError::MYSQL_ERROR;
            break;

        default :
            $custom_error = GlobalError::E_OTHER;
            break;
    }
    
    if($response_code === 500){
        if(!defined('DEBUG') || DEBUG){
            $result = $controller->returnArray($custom_error, [
                'custom_code' => $error_code,
                'error_message' => $error_message,
                'error_file' => $error_file,
                'error_line' => $error_line,
                'source' => $source,
                'client_ip' => $controller->getClientIp(),
                'server_ip' => $controller->getServerIp(),
                'trace' => $trace
            ]);
        }else{
            //非调试模式下调用错误处理接口
            $error = [
                'project_name' => defined('PROJECT_NAME') ? PROJECT_NAME : 'UNDEFINED',
                'project_version' => defined('PROJECT_VERSION') ? PROJECT_VERSION : 'UNDEFINED',
                'project_owner' => defined('PROJECT_OWNER') ? PROJECT_OWNER : 'UNDEFINED',
                'error_type' => $custom_error[2],
                'error_message' => $error_message,
                'error_file' => $error_file,
                'error_line' => $error_line,
                'error_source' => $source,
                'client_ip' => $controller->getClientIp(),
                'server_ip' => $controller->getServerIp(),
                'trace' => json_encode($trace)
            ];

            $error_code = errorLog($error);

            $result = $controller->returnArray($custom_error, ['error_code' => $error_code]);
        }
    }else{
        $result = $controller->returnArray($return_code, $error_message);
    }
    http_response_code($response_code);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * 获取方法调用栈
 * @param Exception | null $exception 捕获的异常
 * @author liwenlong
 */
function getTrace($exception = null){
    if(is_null($exception)){
        $raw_trace = debug_backtrace();
        //调用栈中忽略当前方法的调用
        array_shift($raw_trace);
        array_shift($raw_trace);
    }else{
        $raw_trace = $exception->getTrace();
    }

    $trace_list = [];
    foreach($raw_trace as $i => $e){
        $trace_item['class'] = isset($e['class']) ? $e['class'] : '';
        $trace_item['type'] = isset($e['type']) ? $e['type'] : '';
        $trace_item['function'] = isset($e['function']) ? $e['function'] : '';
        $trace_item['args'] = isset($e['args']) ? $e['args'] : '';
        $trace_item['file'] = isset($e['file']) ? $e['file'] : '';
        $trace_item['line'] = isset($e['line']) ? $e['line'] : '';

        //筛选掉一些框架层的调用栈,使结果更简洁
        if($trace_item['class'] === '' 
            || $trace_item['class'] === 'Closure'
            || $trace_item['class'] === 'SlimFastAPI\\Invoker'
            || strpos($trace_item['class'], 'Slim\\') === 0
            || strpos($trace_item['function'], 'call_user_func') === 0){
            continue;
        }

        $trace_list[] = $trace_item; 
    }
    return $trace_list;
}


/**
 * 记录错误日志
 * @author liwenlong
 */
function errorLog($error){
    $curl = new Curl();

    //设置请求头
    $curl->setHeader('ticket', 'aikaka');
    $curl->setHeader('content-type', 'application/json');

    $curl->post(ERROR_HANDLER_API, json_encode($error));
    $curl->close();
    if(!$curl->error){
        $response = json_decode($curl->response, true);
        if(isset($response['data']['error_code'])){
            return $response['data']['error_code'];
        }
    }
    return 'ERROR_LOG_FAILED';
}


