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
     * MYSLQ服务器异常代码
     */
    const MYSQL_ERR_CODE = 500;

    /**
     * 页面未找到
     */
    const PAGE_NOT_FOUND = [404, '页面未找到', 'PAGE_NOT_FOUND'];

    /**
     * 请求方式不允许
     */
    const METHOD_NOT_ALLOWED = [405, '请求方法错误', 'METHOD_NOT_ALLOWED'];

    /**
     * 运行时错误
     */
    const E_ERROR = [20001, '运行时错误', 'E_ERROR'];

    /**
     * 运行时警告
     */
    const E_WARNING = [20002, '运行时警告', 'E_WARNING'];

    /**
     * 编译错误
     */
    const E_PARSE = [20004, '编译错误', 'E_PARSE'];

    /**
     * 运行时通知
     */
    const E_NOTICE = [20008, '运行时通知', 'E_NOTICE'];

    /**
     * 其它异常
     */
    const E_OTHER = [20010, '其它异常', 'E_OTHER'];

    /**
     * 身份认证失败
     */
    const AUTHENTICATION_FAIL = [20100, '身份认证失败', 'AUTH_FAILED'];

    /**
     * 参数错误
     */
    const PARAM_ERROR = [20200, '参数错误', 'PARAM_ERROR'];

    /**
     * REDIS错误
     */
    const REDIS_ERROR = [20300, 'REDIS错误', 'REDIS_ERROR'];

    /**
     * CURL错误
     */
    const CURL_ERROR = [20400, 'CURL错误', 'CURL_ERROR'];

    /**
     * MYSQL错误
     */
    const MYSQL_ERROR = [20500, 'MYSQL错误', 'MYSQL_ERROR'];
}

