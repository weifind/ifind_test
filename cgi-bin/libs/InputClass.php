<?php
if (! defined ( 'PROJECT_DIR' )) exit ( 'No direct script access allowed' );

class InputClass
{
	
	// 代理浏览器
	protected $userAgent = FALSE;
	
	//TRUE 将所有的换行符转换为 \n
	protected $_standardize_newlines = TRUE;
	
	// 对get,post,cookie数据的XSS过滤
	protected $enableXss = FALSE;

	// 验证CSRF的cookie与post
	protected $enableCsrf = FALSE;
	
	//请求头
	protected $headers = array ();
	
	/**
	 * 配置以及生成一个Security实例
	 *
	 * 
	 */
	public function __construct($enable = array()) {
		//enableXss enableCsrf数据来源,目前采用传参
		
		if($enable != array() || !empty($enable)){
			$this->enableXss = $enable['xss'];
			$this->enableCsrf = $enable['csrf'];
		}
		if($this->enableXss === TRUE){
			$this->security = &loadClass('Security');
		}
		$this->sanitizeGlobal();
	}

	// --------------------------------------------------------------------
	/**
	 * 全局性的过滤，实例化的时候执行
	 */
	private function sanitizeGlobal(){
		// get数据清理
		if (is_array ( $_GET ) && count ( $_GET ) > 0) {
			foreach ( $_GET as $key => $val ) {
				$_GET [$this->cleanInputKey ( $key )] = $this->cleanInput ( $val );
			}
		}
		
		// post数据清理
		if (is_array ( $_POST ) && count ( $_POST ) > 0) {
			foreach ( $_POST as $key => $val ) {
				$_POST [$this->cleanInputKey ( $key )] = $this->cleanInput ( $val );
			}
		}
		
		// cookie数据清理
		if (is_array ( $_COOKIE ) && count ( $_COOKIE ) > 0) {
			// 这三个引用的说明：http://www.ietf.org/rfc/rfc2109.txt
			unset ( $_COOKIE ['$Version'] );
			unset ( $_COOKIE ['$Path'] );
			unset ( $_COOKIE ['$Domain'] );
		
			foreach ( $_COOKIE as $key => $val ) {
				$_COOKIE [$this->cleanInputKey ( $key )] = $this->cleanInput( $val );
			}
		}
		
		// 过滤PHP_SELF
		$_SERVER ['PHP_SELF'] = strip_tags ( $_SERVER ['PHP_SELF'] );
		
		// 检查跨站请求
		if ($this->enableCsrf === TRUE) {
			$this->security->csrfVerify ();
		}
	}
	
	/**
	 * 处理全局数组的 公用方法
	 *
	 * @return string
	 */
	private function _fetch_from_array(&$array, $index = '', $xss_clean = FALSE) {
		if (! isset ( $array [$index] )) {
			return FALSE;
		}
		
		if ($xss_clean === TRUE) {
			return $this->security->xssClean ( $array [$index] );
		}
		
		return $array [$index];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 获取get数据
	 *
	 * @return mixed
	 */
	public function get($index = NULL, $xss_clean = FALSE) {
		if ($index === NULL) {
			if ($xss_clean === FALSE) {
				return $_GET [$index];
			}
			
			if (! empty ( $_GET )) {
				$get = array ();
				
				foreach ( array_keys ( $_GET ) as $key ) {
					$get [$key] = $this->_fetch_from_array ( $_GET, $key, $xss_clean );
				}
				return $get;
			} else {
				return '';
			}
		}
		
		return $this->_fetch_from_array ( $_GET, $index, $xss_clean );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 获取post数据
	 * 
	 * @return mixed
	 */
	public function post($index = NULL, $xss_clean = FALSE) {
		if ($index === NULL) {
			if ($xss_clean === FALSE) {
				return $_POST [$index];
			}
			
			if (! empty ( $_POST )) {
				$post = array ();
				
				foreach ( array_keys ( $_POST ) as $key ) {
					$post [$key] = $this->_fetch_from_array ( $_POST, $key, $xss_clean );
				}
				return $post;
			} else {
				return '';
			}
		}
		
		return $this->_fetch_from_array ( $_POST, $index, $xss_clean );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * get或者post数据，post优先
	 *
	 * @param $index 键值        	
	 * @param $xss_clean 是否XSS过滤        	
	 * @return string
	 */
	public function get_post($index = '', $xss_clean = FALSE) {
		if (! isset ( $_POST [$index] )) {
			return $this->get ( $index, $xss_clean );
		} else {
			return $this->post ( $index, $xss_clean );
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * $_COOKIE数据
	 *
	 * @return string
	 */
	function cookie($index = '', $xss_clean = FALSE) {
		return $this->_fetch_from_array ( $_COOKIE, $index, $xss_clean );
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * $_SERVER数据
	 *
	 * @return string
	 */
	public function server($index = '', $xss_clean = FALSE) {
		return $this->_fetch_from_array ( $_SERVER, $index, $xss_clean );
	}
	
	/**
	 * 用户代理浏览器
	 *
	 * @return string
	 */
	public function userAgent() {
		if ($this->userAgent !== FALSE) {
			return $this->userAgent;
		}
		
		$this->userAgent = (! isset ( $_SERVER ['HTTP_USER_AGENT'] )) ? FALSE : $_SERVER ['HTTP_USER_AGENT'];
		
		return $this->userAgent;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 执行清理过程，主要面向$_REQUEST
	 *
	 *
	 * @param $str 数组或字符串都可
	 * @return string
	 */
	private function cleanInput($str) {
		if (is_array ( $str )) {
			$new_array = array ();
			foreach ( $str as $key => $val ) {
				$new_array [$this->cleanInputKey ( $key )] = $this->cleanInput ( $val );
			}
			return $new_array;
		}
		
		// 考虑下php5.4之前的版本
		if (! isPhp ( '5.4' ) && get_magic_quotes_gpc ()) {
			$str = stripslashes ( $str );
		}
		
		// 移除非法字符
		$str = removeInvalid ( $str );
		
		// 是否进行xss过滤
		if ($this->enableXss === TRUE) {
			$str = $this->security->xssClean ( $str );
		}
		
		// 换行符标准化
		if ($this->_standardize_newlines == TRUE) {
			if (strpos ( $str, "\r" ) !== FALSE) {
				$str = str_replace ( array (
						"\r\n",
						"\r",
						"\r\n\n" 
				), PHP_EOL, $str );
			}
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 字段过滤
	 *
	 * @return string
	 */
	private function cleanInputKey($str) {
		if (! preg_match ( '/^[a-z0-9:_\/-]+$/i', $str )) {
			exit ( 'Disallowed Key Characters.' );
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 请求头
	 *
	 * @return array
	 */
	public function requestHeaders($xss_clean = FALSE) {
		
		if (function_exists ( 'apache_request_headers' )) {
			$headers = apache_request_headers ();
		} else {
			$headers ['Content-Type'] = (isset ( $_SERVER ['CONTENT_TYPE'] )) ? $_SERVER ['CONTENT_TYPE'] : @getenv ( 'CONTENT_TYPE' );
			
			foreach ( $_SERVER as $key => $val ) {
				if (strncmp ( $key, 'HTTP_', 5 ) === 0) {
					$headers [substr ( $key, 5 )] = $this->_fetch_from_array ( $_SERVER, $key, $xss_clean );
				}
			}
		}
		
		//进行一些替换
		foreach ( $headers as $key => $val ) {
			$key = str_replace ( '_', ' ', strtolower ( $key ) );
			$key = str_replace ( ' ', '-', ucwords ( $key ) );
			
			$this->headers [$key] = $val;
		}
		
		return $this->headers;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * http请求头
	 * 
	 * @param $index 索引条目
	 * @return mixed
	 */
	public function getRequestHeader($index, $xss_clean = FALSE) {
		if (empty ( $this->headers )) {
			$this->requestHeaders ();
		}
		
		if (! isset ( $this->headers [$index] )) {
			return FALSE;
		}
		
		if ($xss_clean === TRUE) {
			return $this->security->xssClean ( $this->headers [$index] );
		}
		
		return $this->headers [$index];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 依据HTTP_X_REQUESTED_WITH判断是否ajax请求
	 *
	 * @return boolean
	 */
	public function isAjaxRequest() {
		return ($this->server ( 'HTTP_X_REQUESTED_WITH' ) === 'XMLHttpRequest');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 是否命令行请求
	 *
	 * @return boolean
	 */
	public function isCliRequest() {
		return (php_sapi_name () == 'cli') or defined ( 'STDIN' );
	}

}

/* End of file InputStatic.php */