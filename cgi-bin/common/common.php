<?php
/*
 * 用户自己定义的类库或者一次性调用的类
 *  
 * @param $class 需要加载的类名，不带Class 
 * @param $param 只支持数组形式
 */
function &loadClass($class, $param = array()) {
	$_class = '';
	$classname = $class . 'Class';
	require LIBRARIES_DIR . $classname . '.php';
	if ($param === array () || empty ( $param )) {
		$_class = new $classname ();
	} else {
		$_class = new $classname ( $param );
	}
	unset ( $classname );
	return $_class;
}

/* 使用此方法：
 * __construct可有可无，不传参
 * 整个执行过程中需多次实例化的
 * 
 * 
 * @param $class 不带Static后缀
 */
function &loadStatic($class, $param = array()) {
	static $_statics = array ();
	if (isset ( $_statics [$class] )) {
		return $_statics [$class];
	}
	//Static Class后缀都接收
	$classname = $class . 'Static';
	if(!is_file(LIBRARIES_DIR . $classname . '.php')){
		$classname = $class . 'Class';	
	}
	require LIBRARIES_DIR . $classname . '.php';
	if ($param === array () || empty ( $param )) {
		$_statics ["$class"] = new $classname(  );
	} else {
		$_statics ["$class"] = new $classname( $param );
	}
	unset ( $classname );
	
	return $_statics ["$class"];
}

/*
 * 获取配置文件,config.php
 */
function &getConfig($which = 'default') {
	static $_config = array();
	
	if (isset ( $_config[$which] ) && ! empty ( $_config[$which] )) {
		return $_config[$which];
	}
	include CONFIG_DIR . $which . '.php';
	$_config[$which] = $config;//一维数组配置
	unset($config);
	
	return $_config[$which];
}

/*
 * 404错误
 */
function show_404($page = '', $log_error = TRUE) {
	static $_error;
	$_error = & loadStatic ( 'Exceptions' );
	$_error->show_404 ( $page, $log_error );
	exit ();
}

/*
 * 除404之外其他的错误
 */
function show_error($message, $heading = 'An Error Was Encountered') {
	
	$_error = & loadStatic ( 'Exceptions' );
	echo $_error->show_error ( $heading, $message, 'template' );
	exit ();
}

/*
 * 日志记录
 */
function log_message($level = 'error', $message) {
	$_log = & loadStatic ( 'Log' );
	$_log->writeLog ( $level, $message );
}

/*
 * exception错误句柄
 */
function _exception_handler($severity, $message, $filepath, $line) {
	// 不建议记录E_STRICT的错误
	if ($severity == E_STRICT) {
		return;
	}
	
	$_error = & loadStatic ( 'Exceptions' );
	$config = & getConfig ();
	// 是否记录错误
	if ($config ['logEnable'] === FALSE) {
		return;
	}
	
	$_error->log_exception ( $severity, $message, $filepath, $line );
}

/*
 * 移除url中非法字符
 */
function removeInvalid($str, $urlEncoded = TRUE) {
	$nonDisplayables = array ();
	
	if ($urlEncoded) {
		$non_displayables [] = '/%0[0-8bcef]/'; // 00-08, 11, 12, 14, 15
		$non_displayables [] = '/%1[0-9a-f]/'; // 16-31
	}
	
	$nonDisplayables [] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11,
	                                                              // 12, 14-31, 127
	
	do {
		$str = preg_replace ( $nonDisplayables, '', $str, - 1, $count );
	} while ( $count );
	
	return $str;
}

/**
 * 获取IP
 */
function getIp() {
	if (getenv ( 'HTTP_CLIENT_IP' ) && strcasecmp ( getenv ( 'HTTP_CLIENT_IP' ), 'unknown' )) {
		$ip = getenv ( 'HTTP_CLIENT_IP' );
	} elseif (getenv ( 'HTTP_X_FORWARDED_FOR' ) && strcasecmp ( getenv ( 'HTTP_X_FORWARDED_FOR' ), 'unknown' )) {
		$ip = getenv ( 'HTTP_X_FORWARDED_FOR' );
	} elseif (getenv ( 'REMOTE_ADDR' ) && strcasecmp ( getenv ( 'REMOTE_ADDR' ), 'unknown' )) {
		$ip = getenv ( 'REMOTE_ADDR' );
	} elseif (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], 'unknown' )) {
		$ip = $_SERVER ['REMOTE_ADDR'];
	}
	return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
}

/**
 * 文件大小单位换算
 * 
 * @param
 *        	filesieze 文件大小单位为：字节
 */
function sizeConvert($filesize) {
	if ($filesize >= 1073741824) {
		$filesize = round ( $filesize / 1073741824 * 100 ) / 100 . ' GB';
	} elseif ($filesize >= 1048576) {
		$filesize = round ( $filesize / 1048576 * 100 ) / 100 . ' MB';
	} elseif ($filesize >= 1024) {
		$filesize = round ( $filesize / 1024 * 100 ) / 100 . ' KB';
	} else {
		$filesize = $filesize . ' Bytes';
	}
	return $filesize;
}

/**
 * php版本验证
 */
function isPhp($version = '5.3.0') {
	static $_isPhp;
	$version = ( string ) $version;
	
	if (! isset ( $_isPhp [$version] )) {
		$_isPhp [$version] = (version_compare ( PHP_VERSION, $version ) > 0) ? TRUE : FALSE;
	}
	
	return $_isPhp [$version];
}

