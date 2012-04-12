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
	
	/*
	 * 捕捉url中脚本攻击,例如
	 * $_GET['a'] = <script>alert('a')</script>
	 */
	public function testSecurity(){
		echo $_GET['a'];
	}
	
	//测试calendar
	public function testCalendar(){
		$calendar = & loadClass('Calendar');
		echo $calendar->generate();
	}
	
	//测试验证码
	public function testCaptcha(){
		//如果自行配置参数，此四个参数必须
		$capt = array(
				'height' => 25,
				'width' => 80,
				'length' => 5,
				'expiration' => 60,
				);
		$captcha = & loadClass('Captcha');
		$captcha->createCaptcha($capt);
		
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