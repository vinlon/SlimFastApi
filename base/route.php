<?php  
/**
 * Route 
 * @author liwenlong
 */

namespace SlimFastAPI;

use Exception;

/**
 * 路由管理
 */
class Route extends Base{
    
    /**
     * 路由列表
     * @var array
     */
    private static $route_map = [];

    /**
     * 构造函数
     * @param string $controller 控制器类名称
     * @param string $model      Model类名称
     * @param string $group      Route所属组
     */
    public function __construct($controller, $model = '', $group = ''){
        $this->group = self::formatUri($group);
        $this->model = $model;
        $this->controller = $controller;
    }

    /**
     * 可处理的请求类型列表
     * @var [type]
     */
    public static $type_list = ['get', 'post', 'put', 'delete'];

    /**
     * @author liwenlong
     */
    
    /**
     * 添加路由
     * @param  string $type   请求类型
     * @param  string $uri    路由地址
     * @param  string $method 方法名称
     * @param  array $option 自定义选项
     * @author liwenlong
     */
    public function regist($type, $uri, $method, $option){
        $type = strtolower($type);
        if(!in_array($type, self::$type_list)){
            throw new Exception('Request type only support [' . join(self::$type_list, ',') . ']', GlobalError::E_ERROR);
        }
        $uri = $this->group . self::formatUri($uri);
        $controller = $this->controller;
        self::$route_map[$type][$uri] = [$controller, $method, $this->model, $option];
    }

    /**
     * 批量添加路由
     * @param  array $route_list   路由列表
     * @author liwenlong
     */
    public function batchRegist($route_list){
        if(!is_array($route_list)){
            throw new Exception('The route_list should be type of array', GlobalError::E_ERROR);
        }
        foreach ($route_list as $route) {
            if(!is_array($route) || count($route) < 3 || count($route) > 4){
                throw new Exception('The item of route_list should be [type, uri, method, [option]]', E_ERROR);
            }
            $type = $route[0];
            $uri = $route[1];
            $method = $route[2];
            $option = isset($route[3]) ? $route[3] : [];

            $this->regist($type, $uri, $method, $option);
        }
    }

    /**
     * 获取路由列表
     * @return array 路由列表
     * @author liwenlong
     */
    public static function getList(){
        return self::$route_map;
    }

    /**
     * 格式化地址
     * @param  string $uri      路由地址
     * @return string           格式化的地址
     * @author liwenlong
     */
    private static function formatUri($uri){
        //只在路径开始添加'/'
        $trim_uri = trim($uri, '/');
        $regular_uri = '/' . $trim_uri;
        //替换空格
        
        return $regular_uri;
    }

    
}


