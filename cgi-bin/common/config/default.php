<?php
/*命名规范
 类名与文件名一致，类名严格控制为$class.Ifind,$class只有第一个为大写字母
类名：驼峰法
对应类的文件：驼峰法
sys类命名方法：$class
libs类命名方法：$class.Class
方法名：第一个单词小写，其他单词首字母大写
视图名：$class.$method.'.php'，$class首字母大写
缓存文件名：$class.$method.'.html',$class首字母大写
*/

$config = array();
//注意：类库函数中想获取本文件配置内容，需重新包含common.php的get_congig


//cache是否开启，在display中传递
//已定义全局变量
$config['cachePath'] = CACHE_DIR;
//单位:小时
$config['cacheExpiration'] = 720;


//日志 log
$config['logEnable'] = TRUE;
$config['logPath'] = PUBLIC_DIR . 'logs/';