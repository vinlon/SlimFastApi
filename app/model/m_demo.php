<?php
/**
 * Model Entity
 */

namespace Model;

class Demo extends AppModel{
    public function __construct($option = []){
        //调用父类构造函数
        parent::__construct($option);

        //初始化验证规则
        $this->batchAddRule([
            'entity_id' => function($key, $value){
                return $this->checkInt($key, $value);
            },
            'entity_name' => function($key, $value){
                return $this->checkByteLength($key, $value, 10, 32);
            },
            'entity_desc' => function($key, $value){
                return $this->checkByteLength($key, $value, 10, 64);
            },
            'entity_list' => function($key, $value){
                $list = $this->checkArray($key, $value);
                $result = [];
                foreach ($list as $item) {
                    $result[] = $this->load($item);
                }
                return $result;
            },
            'status' => function($key, $value){
                return $this->checkString($key, $value);
            },
            'ref_id' => function($key, $value){
                return $this->checkInt($key, $value);
            },
            'skip' => function($key, $value){
                return $this->checkInt($key, $value);
            },
            'size' =>  function($key, $value){
                $value = $this->checkInt($key, $value);
                $this->checkRange($key, $value, 0, 1000);
                return $value;
            },
            'query' => function($key, $value){
                return $this->checkString($key, $value);
            },
            'id_list' => function($key, $value){
                return $this->checkIntArray($key, $value);
            },
            'preg_test' => function($key, $value){
                return $this->checkString($key, $value, "/^\d*$/");
            },
            'decimal_test' => function($key, $value){
                return $this->checkDecimal($key, $value, 2);
            },
            'mobile_test' => function($key, $value){
                return $this->checkMobile($key, $value);
            },
            'email_test' => function($key, $value){
                return $this->checkEmail($key, $value);
            },
            'url_test' => function($key, $value){
                return $this->checkUrl($key, $value);
            },
            'string_array_test' => function($key, $value){
                return $this->checkStringArray($key, $value);
            },
            'charactor_range_test' => function($key, $value){
                return $this->checkRange($key, $value, 'A', 'Z');
            },
            'create_date' => function($key, $value){
                $value = $this->checkIsoDate($key, $value);
                $this->checkRange($key, $value, '1970-01-01 00:00:00', '2020-12-31 23:59:59');
                return $value;
            },
            'begin_update_time' => function($key, $value){
                return $this->checkIsoDateTime($key, $value);
            },
            'end_update_time' => function($key, $value){
                return $this->checkIsoDateTime($key, $value);
            }
        ]);
    }
}

