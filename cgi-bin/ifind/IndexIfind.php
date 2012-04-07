<?php
if (! defined ( 'PROJECT_DIR' ))
	exit ( 'No direct script access allowed' );
//ci yii zend framework
class IndexIfind extends Ifind
{
	//测试基本功能+cache功能
	public function index(){
		$arr = '';
		foreach($_GET as $v){
			$arr .= '<option value="'.$v.'">'.$v.'</option>';
		}
		$this->assign('arr', $arr);
		//display中自己控制是否缓存
		$this->display('Indexindex',0);
	}	
	/*
	 * 
	 */
	public function testAll(){
		print_r($_SERVER);
		echo '<br />~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br />';
		print_r(getenv('CONTENT-TYPE'));
		//echo getenv('date');
	}
	
	//捕捉url中脚本攻击
	public function testSecurity(){
		echo $_GET['a'];
	}
	
	//测试正则
	public function testPreg(){
	}
	
	/*
	 *mysql操作 
	 */
	public function testMysql(){
		$mysql = & loadClass('Mysql');
		$mysql->dbSelect();
		echo '<pre>';
		print_r($mysql->getConnId());
		echo '</pre>';
	}
	
	/*
	 * cookie操作
	 */
	
	
}