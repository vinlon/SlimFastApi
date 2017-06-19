<?php  
/**
 * Global Error
 */

namespace SlimFastAPI;

/**
* 全局错误码定义
*/
class GlobalError{
    
    /**
     * 身份验证异常代码
     */
    const AUTH_ERR_CODE = 100;

    /**
     * 参数验证异常代码
     */
    const PARAM_ERR_CODE = 200;
    
    /**
     * REDIS服务器异常代码
     */
    const REDIS_ERR_CODE = 300;
    
    /**
     * CURL接口请求异常代码
     */
    const CURL_ERR_CODE = 400;
    
    /**
     * 页面未找到
     */
    const PAGE_NOT_FOUND = 404;
    
    /**
     * 请求方法错误
     */
    const METHOD_NOT_ALLOWED = 405;
    
    /**
     * MYSLQ服务器异常代码
     */
    const MYSQL_ERR_CODE = 500;


    /**
     * 获取错误详细信息
     * @param  integer $err_code            异常代码
     * @return array [HTTP返回码, 自定义错误信息]
     * @author liwenlong
     */
    public static function getCustomError($err_code){
        $http_response_code = 500;
        //根据不同的异常状态码及HTTP返回码定义错误信息
        $custom_msg = [];

        switch ($err_code) {
            //HTTP返回码和异常代码相同
            case self::PAGE_NOT_FOUND:
                $http_response_code = self::PAGE_NOT_FOUND;
                $custom_msg = ['页面未找到', 'PAGE_NOT_FOUND'];
                break;
            case self::METHOD_NOT_ALLOWED:
                $http_response_code = self::METHOD_NOT_ALLOWED;
                $custom_msg = ['请求方法错误', 'METHOD_NOT_ALLOWED'];
                break;

            //HTTP返回码使用200
            case self::AUTH_ERR_CODE:
                $http_response_code = 200;
                $custom_msg = ['身份认证失败', 'AUTH_FAILED'];
                break;

            //使用默认HTTP返回码
            case self::PARAM_ERR_CODE:
                $custom_msg = ['参数错误', 'PARAM_ERROR'];
                break;
            case E_ERROR:
                $custom_msg = ['运行时错误', 'E_ERROR'];
                break;
            case E_WARNING:
                $custom_msg = ['运行时警告', 'E_WARNING'];
                break;
            case E_PARSE:
                $custom_msg = ['编译错误', 'E_PARSE'];
                break;
            case E_NOTICE:
                $custom_msg = ['运行时通知', 'E_NOTICE'];
                break;
            case self::REDIS_ERR_CODE:
                $custom_msg = ['REDIS错误', 'REDIS_ERROR'];
                break;
            case self::CURL_ERR_CODE:
                $custom_msg = ['CURL错误', 'CURL_ERROR'];
                break;
            case self::MYSQL_ERR_CODE:
                $custom_msg = ['MYSQL错误', 'MYSQL_ERROR'];
                break;
            
            default:
                $custom_msg = ["其它异常{$err_code}", 'E_OTHER'];
                break;
        }

        //返回HTTP状态码和自定义错误信息
        array_unshift($custom_msg, self::getCustomCode($http_response_code, $err_code));
        return [
            'http_response_code' => $http_response_code,
            'custom_msg' => $custom_msg
        ];
    }

    /**
     * 获取自定义错误码
     * @author liwenlong
     */
    private static function getCustomCode($http_response_code, $err_code){
        switch ($err_code) {
            case self::PAGE_NOT_FOUND:
            case self::METHOD_NOT_ALLOWED:
                return $err_code;
                break;
            default:
                return $http_response_code * 100 + $err_code;
                break;
        }
    }
}

