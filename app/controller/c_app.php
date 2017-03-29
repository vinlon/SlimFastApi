<?php
/**
 *  APP Controller
 */

namespace Controller;

use SlimFastAPI\BaseController;

/**
 * APP层Controller基类
 */
class AppController extends BaseController
{
    public function __construct($option = []){
        //调用父类构造函数
        parent::__construct();
        //构造函数中统一进行身份认证
        $this->authenticate($option);
    }

    /**
     * 身份认证实现
     * @author liwenlong
     */
    public function authenticate($option){
        if(isset($option['skip_auth']) && $option['skip_auth'] === true){
            return;
        }
        
        $ticket = $this->getTicket();
        //ticket解析和验证
        if($ticket !== 'aikaka'){
            $this->authError('authenticate failed');
        }
    }
}
