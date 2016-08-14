<?php
/**
 * Custom Error
 */

/**
* 自定义错误提示
*/
class CustomError{
    const AUTH_ERR_CODE = 100;
    const PARAM_ERR_CODE = 200;
    const REDIS_ERR_CODE = 300;
    const CURL_ERR_CODE = 400;
    const MYSQL_ERR_CODE = 900;

    const ENTITY_ID_NOT_FOUND = [ 1, 'entity id not found'];
    const STATUS_INVALID = [ 2, 'entity status is invalid'];

}

?>
