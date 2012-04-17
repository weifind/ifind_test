<?php
if (! defined ( 'PROJECT_DIR' ))
	exit ( 'No direct script access allowed' );

class Ifind
{
	// 全局配置文件
	protected $config = array ();
	// 输出内容
	protected $output = '';
	// 默认html缓存设置,display(,1)的参数能覆盖此设置
	//protected $cacheHtml = 0;
	// 缓存时间，单位：小时
	protected $cacheExpiration = 1;
	// 缓存路径
	protected $cachePath = 'html/cache';
	// assign传递的参数
	protected $params = array ();
	
	public function __construct() {
		$this->config = & getConfig ();
		$this->cacheExpiration = $this->config ['cacheExpiration'];
		$this->cachePath = $this->config['cachePath'];
	}
	
	protected function assign($k, $v) {
		$this->params ["$k"] = $v;
	}
	
	/**
	 * 输出内容，页面静态化的选择
	 * 注意，数据更新比较频繁的页面建议设置$cache=0
	 *
	 * 
	 * @param string $view        	
	 * @param $cache 1缓存，0不缓存，默认为0        	
	 */
	public function display($view = '',$cache = 0){
		echo $this->_display($view,$cache);
		exit;
	}
	protected function _display($view = '', $cache = 0) {
		$viewname = VIEW_DIR . $view . '.php';
		$cachename = $this->cachePath . $view . '.html';
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
					return $this->str_replace ( '<!-- \(([0-9]*)\) -->', '', $output );
				}
			} 
		}
		$this->output = file_get_contents ( $viewname );
		$this->parseContent ();
		if ($cache === 1) {
			$this->writeCache ( $view );
		} 
		return $this->output;
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
		$path = $this->cachePath . $view . '.html';
		$cacheTime = '<!-- (' . time () . ') -->';
		//file_put_contents代替常规fopen
		//第三个参数自行选择，是否独占锁定
		file_put_contents ( $path, $cacheTime . $this->output );
	}

	/**
	 * parseContent
	 * $a = 'a'
	 * 目前只是简单的解析，如需使用常规框架的语法，自行引入parse.php文件即可
	 * 大量的正则影响性能
	 */
	private function parseContent() {
		foreach ( $this->params as $k => $v ) {
			$this->output = str_replace ( '{$' . $k . '}', $v, $this->output );
		}
		// str_replace($search, $replace, $subject)
	}
	
	/**
	 * 
	 */
	// ------------------------------------------------------------------------
	
}