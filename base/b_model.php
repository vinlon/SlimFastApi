<?php
/**
 * Base Model
 */

namespace SlimFastAPI;

use Exception;

/**
 * Model层基类
 */
class BaseModel extends Base{

    /**
     * 添加的规则
     * @var array
     */
    protected $rules = [];

    /**
     * 检查字节长度，并将数据转化成字符串
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @param  int $minLength       最小字节长度
     * @param  int $maxLength       最大字节长度
     * @return string               符合条件的字符串
     * @link http://www.jb51.net/article/42116.htm
     * @author liwenlong
     */
    protected function checkByteLength($key, $value, $minLength, $maxLength){
        $value = strval($value);
        $len = (strlen($value) + mb_strlen($value, 'UTF8')) / 2;
        if($len < $minLength || $len > $maxLength){
            throw new Exception("Byte length of [$key] must be within $minLength and $maxLength", GlobalError::PARAM_ERR_CODE);
        }
        return $value;
    }

    /**
     * 检查字符串长度，并将数据转化成字符串
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @param  int $minLength       最小字符串长度
     * @param  int $maxLength       最大字符串长度
     * @return string               符合条件的字符串
     * @author liwenlong
     */
    protected function checkStringLength($key, $value, $minLength, $maxLength){
        $value = strval($value);
        $len = mb_strlen($value);
        if($len < $minLength || $len > $maxLength){
            throw new Exception("String length of [$key] must be within $minLength and $maxLength", GlobalError::PARAM_ERR_CODE);
        }
        return $value;
    }

    /**
     * 检查数据是否为字符串
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @param  string $reg_exp      字符串需要匹配的正则表达式
     * @return string               符合条件的字符串
     * @author liwenlong
     */
    protected function checkString($key, $value, $reg_exp = null){
        if(!is_string($value)){
            throw new Exception("$key must be string value", GlobalError::PARAM_ERR_CODE);
        }
        if($reg_exp !== null && !preg_match($reg_exp, $value)){
            throw new Exception("$key must be string value match the expression [$reg_exp]", GlobalError::PARAM_ERR_CODE);
            
        }
        return $value;
    }

	/**
     * 检查数据是否为Int
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return string               符合条件的Int类型数据
     * @author liwenlong
     */
    protected function checkInt($key, $value){
        $int_val = intval($value);
        if(strval($int_val) != $value){
            throw new Exception("$key must be int value", GlobalError::PARAM_ERR_CODE);
        }
        return $int_val;
    }

    /**
     * 检查数据是否为小数
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return int                  小数的精度
     * @return string               符合条件的float类型数据
     * @author liwenlong
     */
    protected function checkDecimal($key, $value, $precision = null){
        $float_val = floatval($value);
        if($precision === null){
            if(strval($float_val) != $value){
                throw new Exception("$key must be decimal value", GlobalError::PARAM_ERR_CODE);
            }
            return floatval($value);   
        }else{
            if( strval($float_val) != $value
                || strval(round($float_val, $precision)) != $value){
                throw new Exception("$key must be decimal value with precision $precision", GlobalError::PARAM_ERR_CODE);
            }
            return round($value, $precision);   
        }
            
    }

    /**
     * 检查数据是否为手机号
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return string               符合条件的手机号
     * @author liwenlong
     */
    protected function checkMobile($key, $value){
        $pattern='/^1[3-8][0-9]{9}$/';    //并没有也不需要限制的太严格
        if(!preg_match($pattern, strval($value))){
            throw new Exception("$key must be valid mobile number", GlobalError::PARAM_ERR_CODE);
        }
        return strval($value);
    }

    /**
     * 检查数据是否为邮箱
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return string               符合条件的邮箱地址
     * @author liwenlong
     */
    protected function checkEmail($key, $value){
        $pattern='/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/';
        if(!preg_match($pattern, strval($value))){
            throw new Exception("$key must be valid email address", GlobalError::PARAM_ERR_CODE);
        }
        return strval($value);
    }

    /**
     * 检查数据是否为URL
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return string               符合条件的URL地址
     * @author liwenlong
     */
    protected function checkUrl($key, $value){
        $pattern = '/^(http:\/\/|https:\/\/).*$/';
        if(!preg_match($pattern, strval($value))){
            throw new Exception("$key must be valid http(s) address", GlobalError::PARAM_ERR_CODE);
        }
        return strval($value);
    }

    /**
     * 检查数据是否为Array（不能为空）
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return array                符合条件的Array类型数据
     * @author liwenlong
     */
    protected function checkArray($key, $value){
        if(!is_array($value) || count($value) === 0){
            throw new Exception("$key must be non-empty array", GlobalError::PARAM_ERR_CODE);
        }
        return $value;
    }

    /**
     * 检查数据是否为Int Array
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return array                符合条件的Int Array类型数据
     * @author liwenlong
     */
    protected function checkIntArray($key, $value){
        $arr = [];
        if(!is_array($value)){
            throw new Exception("$key must be array", GlobalError::PARAM_ERR_CODE);
        }
        foreach ($value as $item) {
            if(strval(intval($item)) != $item){
                throw new Exception("item in $key must be int value", GlobalError::PARAM_ERR_CODE);
            }else{
                $arr[] = intval($item);
            }
        }
        return $arr;
    }

    /**
     * 检查数据是否为String Array
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return array                符合条件的String Array类型数据
     * @author liwenlong
     */
    protected function checkStringArray($key, $value){
        $arr = [];
        if(!is_array($value)){
            throw new Exception("$key must be array", GlobalError::PARAM_ERR_CODE);
        }
        foreach ($value as $item) {
            if(!is_string($item)){
                throw new Exception("item in $key must be string value", GlobalError::PARAM_ERR_CODE);
            }else{
                $arr[] = strval($item);
            }
        }
        return $arr;
    }


    /**
     * 检查数据是否为标准时间字符串
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return string               符合条件的标准时间字符串
     * @author liwenlong
     */
    protected function checkIsoDateTime($key, $value){
        if($this->isoDateTime(strtotime($value)) != $value){
            throw new Exception("$key must be IsoDateTime", GlobalError::PARAM_ERR_CODE);
        }
        return $value;
    }
	
    /**
     * 检查数据是否为标准日期字符串
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @return string               符合条件的标准日期字符串
     * @author liwenlong
     */
    protected function checkIsoDate($key, $value){
        if($this->isoDate(strtotime($value)) != $value){
            throw new Exception("$key must be IsoDate", GlobalError::PARAM_ERR_CODE);
        }
        return $value;
    }

    /**
     * 检查数据是否位于指定区间
     * @param  string $key          参数的键
     * @param  string $value        参数的值
     * @param  int $minLength       最小值
     * @param  int $maxLength       最大值
     * @return string               符合条件的字符串
     * @author liwenlong
     */
    protected function checkRange($key, $value, $min, $max){
        if($value < $min || $value > $max){
            throw new Exception("$key must be within $min and $max", GlobalError::PARAM_ERR_CODE);
        }
        return $value;
    }

    /**
     * 添加属性的验证规则
     * @param  string $key              属性的键
     * @param  callable $callback       验证方法
     * @author liwenlong
     */
    protected function addRule($key, $callback){
        if(!is_string($key)){
            throw new Exception('The rule key must be of type string', GlobalError::PARAM_ERR_CODE);
        }
        if(!is_callable($callback)){
            throw new Exception('The rule must be callable', GlobalError::PARAM_ERR_CODE);
        }

        $this->rules[$key] = $callback;
    }

    /**
     * 批量添加验证规则
     * @param  array $rules             规则列表  
     * @author liwenlong
     */ 
    protected function batchAddRule($rules){
        if(!is_array($rules)){
            throw new Exception('The param for batchAddRull must be type of array', GlobalError::PARAM_ERR_CODE);
        }
        foreach ($rules as $key => $rule) {
            if(!is_string($key) || !is_callable($rule)){
                throw new Exception('The pattern of rule item must be [string => callable]', 1);
            }
            $this->addRule($key, $rule);
        }
    }

     /**
     * 抛出参数错误的异常
     * 在业务层不能使用全局错误码PARAM_ERR_CODE，故通过此方法抛出异常
     * @param  string $message 错误信息
     * @author liwenlong
     */
    protected function paramError($message){
        throw new Exception($message, GlobalError::PARAM_ERR_CODE);
    }

    /**
     * 从数组中自动加载数据
     * @param array $arr 接收到的参数列表
     * @author liwenlong
     */
    public function load($arr){
        $datas = [];

        if(empty($arr)){
            return [];
        }
        
        foreach ($arr as $key => $value) {
            if(isset($this->rules[$key])){
                //执行验证方法
                $fixed_value = $this->rules[$key]($key, $value);
                //如果验证验证方法有返回值
                if($fixed_value){
                    $value = $fixed_value;
                }
            }
            $datas[$key] = $value;
        }
        return $datas;
    }
}

