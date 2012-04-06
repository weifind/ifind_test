<?php
if (! defined ( 'PROJECT_DIR' ))
	exit ( 'No direct script access allowed' );

class Ifind
{
	// 全局配置文件
	protected $config = array ();
	// 输出内容
	protected $output = '';
	// 缓存时间，单位：小时
	protected $cacheExpiration = 1;
	// assign传递的参数
	protected $params = array ();
	
	public function __construct() {
		$this->config = & getConfig ();
		$this->cacheExpiration = $this->config ['cacheExpiration'];
	}
	
	protected function assign($k, $v) {
		$this->params ["$k"] = $v;
	}
	
	/**
	 * 注意，数据更新比较频繁的页面建议设置$cache=0
	 *
	 * 
	 * @param string $view        	
	 * @param $cache 1缓存，0不缓存，默认为1        	
	 */
	protected function display($view = '', $cache = 1) {
		$viewname = VIEW_DIR . $view . '.php';
		$cachename = CACHE_DIR . $view . '.html';
		// 缓存文件输出,缺少数据更新这一条件
		// 什么时候更新？？？ 目前由用户控制是否缓存
		if (is_file ( $cachename ) && $cache === 1) {
			$output = file_get_contents ( $cachename );
			$match = 0;
			if (preg_match ( '/\<!-- \(([0-9]*)\) --\>/', $output, $match )) {
				// save 30 days
				if (time () - $match [1] > $this->cacheExpiration * 3600 || filemtime ( $cachename ) - $match [1] > 1) {
					chmod ( $cachename, '0777' );
					unlink ( $cachename );
				} else {
					$this->displayCache ( $cachename );
				}
			} else {
				$this->displayCache ( $cachename );
			}
		}
		$this->output = file_get_contents ( $viewname );
		$this->parseContent ();
		if ($cache === 1) {
			$this->writeCache ( $view );
		} else if (is_file ( $cachename )) {
			unlink ( $cachename );
		}
		echo $this->output;
		exit ();
		/*
		 * 引入正则解析功能后使用
		 * 如何解析？？？ 除了正则替换之外的方法？ {foreach b ob_start(); echo $this->params;
		 * require VIEW_DIR.$view.'.php'; $this->output = ob_get_contents();
		 * $this->writeCache($view); ob_end_flush();
		 */
	}
	
	/**
	 * 
	 * @param html文件内容 $view
	 */
	private function writeCache($view = '') {
		$path = CACHE_DIR . $view . '.html';
		$cacheTime = '<!-- (' . time () . ') -->';
		file_put_contents ( $path, $cacheTime . $this->output );
	}
	
	/**
	 * 默认缓存
	 * 
	 */
	private function displayCache($file) {
		$content = file_get_contents ( $file );
		echo str_replace ( '<!-- \(([0-9]*)\) -->', '', $content );
		exit ();
	}
	
	/**
	 * parseContent
	 * $a = 'a'
	 * 目前只是简单的解析，如需使用常规框架的语法，自行引入parse.php文件即可
	 * 大量的正则影响性能
	 */
	private function parseContent() {
		foreach ( $this->params as $k => $v ) {
			$this->output = str_replace ( '{{$' . $k . '}}', $v, $this->output );
		}
		// str_replace($search, $replace, $subject)
	}
	
	/**
	 * 
	 */
	// ------------------------------------------------------------------------
	
}