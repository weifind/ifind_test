<?php
$config = array();
//数据库服务器数
$config['maxServers'] = 3;
//mysql database
$config['hostname1'] = 'test.ifind';
$config['port1'] = '3306';
$config['username1'] = 'root';
$config['password1'] = 'jgg';
$config['database1'] = 'test';
$config['charset1'] = 'utf8';
$config['dbcollat1'] = 'utf8_general_ci';
$config['dbprefix1'] = '';
$config['pconnect1'] = FALSE;

/*
 * 第二个数据库配置，可以这样
 *
$config['hostname2'] = 'frame.ifind';
$config['port2'] = '3307';
$config['username2'] = 'root';
$config['password2'] = 'jgg';
$config['database2'] = 'test';
$config['charset2'] = 'utf8';
$config['dbcollat2'] = 'utf8_general_ci';
$config['dbprefix2'] = '';
$config['pconnect2'] = FALSE;
*/