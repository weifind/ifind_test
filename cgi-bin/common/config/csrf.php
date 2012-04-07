<?php
/*
 * 本配置针对跨域操作
 */
$config = array();

//securityClass.php
// 跨站点请求的有效cookie时间，默认两个小时（秒）
$config['csrfExpire'] = 7200;
$config['csrfTokenname'] = 'tokenc';
$config['csrfCookiename'] = 'cookiec';
//跨站cookie
$config['cookiePrefix'] = 'csrfc';
$config['cookieSecure'] = FALSE;
$config['cookiePath'] = PUBLIC_DIR . 'session/csrf/';
$config['cookieDomain'] = 'frame.ifind';