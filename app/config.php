<?php
/**
 * 环境配置，自动识别环境并加载配置信息
 */

if(isset($_SERVER['HTTP_HOST'])){
    //去掉HTTP_HOST中的PORT
    $host = preg_replace("/:\d+/", '', $_SERVER['HTTP_HOST']);
    if(in_array($host, ['s.aikaka.com.cn'])){
        $mode = 'production';
    }else if(in_array($host, ['192.168.2.211', '192.168.2.212', '192.168.2.213'])){
        $mode = 'beta';
    }else if(in_array($host, ['192.168.2.2'])){
        $mode = 'develop';
    }
}else{
    $mode = $_SERVER['mode'];
}

if(!empty($mode)){
    require('config_' . $mode . '.php');
}else{
    throw new Exception("未知的运行环境", E_ERROR);
}

/**
 * Route文件路径 
 */
define('ROUTE_PATH', 'app/route/route.php');

/**
 * 项目名称
 */
define('PROJECT_NAME','SlimFastAPI');

/**
 * 版本号
 */
define("PROJECT_VERSION", '2.0');

/**
 * 项目负责人邮箱
 */
define('PROJECT_OWNER','liwenlong@aikaka.com.cn');

/**
 * 自定义错误前缀
 */
define('ERROR_PREFIX','x200'); //x：项目编号

