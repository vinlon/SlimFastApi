<?php  
/**
 * Route文件自动加载
 * @author liwenlong
 */

$files = glob(__DIR__ . '/r_*.php');
if ($files === false) {
    throw new RuntimeException("Route files not found");
}
foreach ($files as $file) {
    require_once $file;
}
unset($file);
unset($files);

