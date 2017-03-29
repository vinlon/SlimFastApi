<?php  
/**
 * 全局基类
 * @author liwenlong
 */

namespace SlimFastAPI;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Exception;

/**
* Global Base
*/
class Base{
    
    /**
     * 获取标准日期字符串
     * @param  int|null $time 时间戳
     * @return string 标准日期字符串
     * @author liwenlong
     */
    public function isoDate($time = null){
        if(!$time){
            $time = time();
        }
        if(!is_numeric($time)){
            $time = strtotime($time);
        }
        return date('Y-m-d', $time);
    }

    /**
     * 获取标准时间字符串
     * @param  int|null $time 时间戳
     * @return string 标准时间字符串
     * @author liwenlong
     */
    public function isoDateTime($time = null){
        if(!$time){
            $time = time();
        }
        if(!is_numeric($time)){
            $time = strtotime($time);
        }
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * 使用Monolog生成Debug日志.
     * 自动生成日期，context转化为json格式，输出到logs/debug目录下
     * @param string $message 日志信息
     * @param array $context 日志上下文
     * @return bool 日志记录是否成功
     * @author liwenlong
     */
    public function addDebug($message, $context = array()){
        try {
            if(!defined('DEBUG') || DEBUG){
                // Create the logger
                $logger = new Logger('debug');
                // Now add some handlers
                $date = $this->isoDate();
                $file_path = __DIR__."/../logs/debug/$date.log";
                $output = "%datetime%\t" . $message . "\n%context%\n";
                $formatter = new LineFormatter($output);
                // Create a handler
                $stream = new StreamHandler($file_path, Logger::DEBUG);
                $stream->setFormatter($formatter);

                $logger->pushHandler($stream);
                // You can now use your logger
                if($context){
                    return $logger->addDebug($message, $context);
                }else{
                    return $logger->addDebug($message);
                }
            }  
        } catch (Exception $e) {
            throw new Exception($e->GetMessage(), E_ERROR);
        }
        
    }

    /**
     * 使用Monolog生成跟踪日志.
     * 无任何定义内容，直接将message按行记录，默认输出到logs/trace目录下，可通过folder参数指定其它文件夹
     * @param string $message 日志信息
     * @param string $folder 可指定logs目录下子文件夹名称
     * @return bool 日志记录是否成功
     * @author liwenlong
     */
    public function addInfo($message, $folder = 'trace'){
        try {
            // Create the logger
            $logger = new Logger('trace');
            // Now add some handlers
            $date = $this->isoDate();
            $file_path = __DIR__."/../logs/$folder/$date.log";

            $output = $message . "\n";
            $formatter = new LineFormatter($output);
            // Create a handler
            $stream = new StreamHandler($file_path, Logger::INFO);
            $stream->setFormatter($formatter);

            $logger->pushHandler($stream);
            // You can now use your logger
            return $logger->addInfo($message);
        } catch (Exception $e) {
            throw new Exception($e->GetMessage(), E_ERROR);
        }
    }

    /**
     * 从数组中选择指定的列,如果指定的列不存在将报错
     * @param array $target 目标数组
     * @param array $field_list 筛选字段
     * @return array 筛选后的数据
     * @author liwenlong
     */
    public function arraySelect($target, $field_list){
        foreach($field_list as $field){
            $return_list[$field] = $target[$field];
        }
        return $return_list;
    }

}

