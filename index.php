<?php
/* 
 * 支持的url 
 * 1.class/method/a/12 , 不支持class/method?a=11 
 * 2.index.php?a=11&b=12 /index/index?a=11
 */

// dir 最后带'/'
define ( 'PROJECT_DIR', dirname ( __FILE__ ) . '/' );
define ( 'CONTROLLER_DIR', PROJECT_DIR . 'cgi-bin/ifind/' );
define ( 'LIBRARIES_DIR', PROJECT_DIR . 'cgi-bin/libs/' );
define ( 'COMMON_DIR', PROJECT_DIR . 'cgi-bin/common/' );
define ( 'CONFIG_DIR', PROJECT_DIR . 'cgi-bin/common/config/' );
define ( 'PUBLIC_DIR', PROJECT_DIR . 'public/' );
define ( 'VIEW_DIR', PROJECT_DIR . 'view/' );
define ( 'LOG_DIR', PROJECT_DIR . 'sys/');
define ( 'BASE_URL','http://frame.ifind/');

//控制器基类
require 'ifind.php';
//公用函数
require COMMON_DIR . 'common.php';

/*
 * 此框架要求PHP版本不低于5.3.0
 */
if(isPhp('5.3.0') !== TRUE){
	show_error('此框架要求PHP版本不低于5.3.0','PHP version is not supported!');
}


//自定义错误句柄  生产模式下打开
//set_error_handler('_exception_handler');

/*
 * sort url,use web server
 * hide 'index.php
 * for seo
 * maybe can use apache's config replace this
*/
$request = strtolower($_SERVER['REQUEST_URI']);
if((strpos($request,'index.php') != FALSE)){
	header('location: '.str_replace('index.php', '', $request));
}

if((substr($request,-1,5) == '.html')){
	$request = strstr($request,'.html',TRUE);
}
/* URL直接执行
 * 这个判断的目的：直接显示url，不足：牺牲了public类image方法
* 当前只是为了兼容原先blog的附件
* TODO 需改进
*/
if((isset($request{21}) && strpos('public/old_uploadfile', $request) !== FALSE)){
	goto PUBLICIMAGE;
}

$dir = '';
//默认控制器
$class = 'IndexIfind';
//默认执行方法
$method = 'index';
//跳过url解析的情形
$noUrl = array('','/','/index','/index/index');
//系统预留的目录
//$sysDir = array('public','sys');

// 1,2情形
// / /index /index/index /dir/class/method
// 不应包含/index/index?a=11&b=12 /test/index?a=11&b=12,控制器中不能有index目录
//方法 类名，规律
if ( !in_array($request, $noUrl) && preg_match('/^\/\?.+/',$request) == 0) {
	//$url = parse_url ( $_SERVER ['REQUEST_URI'] );
	$url = trim ( $request, '/' );
	$url = explode ( '/', $url );
	// 考虑目录的情形
	if (is_dir ( CONTROLLER_DIR . $url [0] )) {
		$dir = array_shift ( $url ) . '/';
	}
	$tmp = '';
	foreach ( $url as $k => $v ) {
		if ($k === 0) {
			$class = ucwords (  $v ) . 'Ifind';
		} else if ($k === 1) {
			$method =  $v ;
		} else {
			if ($k % 2 === 0) {
				$tmp = $v;
			} else {
				$_GET ["$tmp"] = $v;
			}
		}
	}
	unset ( $tmp );
}
unset($request);

//这里进行数据过滤，处理。建议生产环境下启用
//$Input = loadStatic('Input',array('xss'=>TRUE,'csrf'=>FALSE));

//不考虑控制器单一入口的情形，而且控制器中也不该出现单一入口
include CONTROLLER_DIR . $dir . $class . '.php';
$instance = new $class ();
$instance->$method ();

/*上文goto的标记，附件的直接输出*/
PUBLICIMAGE:


//End index.php