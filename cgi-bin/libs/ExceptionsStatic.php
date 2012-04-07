<?php

if (! defined ( 'PROJECT_DIR' ))
	exit ( 'No direct script access allowed' );
	

class ExceptionsStatic
{
	protected $severity;//这四个属性暂时没用
	protected $message;
	protected $filename;
	protected $line;
	
	public $levels = array (
			E_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_PARSE => 'Parsing Error',
			E_NOTICE => 'Notice',
			E_CORE_ERROR => 'Core Error',
			E_CORE_WARNING => 'Core Warning',
			E_COMPILE_ERROR => 'Compile Error',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_ERROR => 'User Error',
			E_USER_WARNING => 'User Warning',
			E_USER_NOTICE => 'User Notice',
			E_STRICT => 'Runtime Notice' 
	);
	
	public function __construct(){}
	// --------------------------------------------------------------------
	
	/**
	 * Exception 记录器，自动调用
	 * 
	 */
	public function log_exception($severity, $message, $filepath, $line) {
		$severity = (! isset ( $this->levels [$severity] )) ? $severity : $this->levels [$severity];
		log_message ( 'error', 'Severity: ' . $severity . '  --> ' . $message . ' ' . $filepath . ' ' . $line );
		//TODO 只提示访问出错即可
		$this->show_404();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 404 请求
	 *
	 * @param $page 请求的页面
	 * @param $log_error 是否记录日志
	 */
	public function show_404($page = '', $log_error = TRUE) {
		$heading = "404 Page Not Found";
		$message = "您访问的页面出错，请仔细检查输入的URL！";
		
		//写入日志
		if ($log_error) {
			log_message ( 'error', '404 Page Not Found --> ' . $page );
		}
		//展现给用户的界面
		echo $this->show_error ( $heading, $message, 'error_404' );
		exit ();
	}
	
	public function show_error($heading, $message, $template = 'template') {
		//清理</p><p>标签
		$message = '<p>' . implode ( '</p><p>', (! is_array ( $message )) ? array (
				$message 
		) : $message ) . '</p>';
		
		ob_start ();
		include (VIEW_DIR . 'error/' . $template . '.php');
		$buffer = ob_get_contents ();
		ob_end_clean ();
		return $buffer;
	}
	
	
}
// END Exceptions Class