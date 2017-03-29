<?php  
/**
 * Custom Error
 */

namespace Controller;

/**
* 自定义业务错误代码
*/
class CustomError
{
    const ENTITY_ID_NOT_FOUND = [ 1, 'entity id not found'];
    const STATUS_INVALID = [ 2, 'entity status is invalid'];
    const INVALID_IP_LOCATION_SOURCE = [3, 'source can only be baidu or taobao'];
}


