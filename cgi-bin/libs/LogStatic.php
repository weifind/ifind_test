<?php  
if (! defined ( 'PROJECT_DIR' ))
	exit ( 'No direct script access allowed' );


class LogStatic {

	//日志路径
	protected $logPath;
	//日志日期格式
	protected $dateFmt	= 'Y-m-d H:i:s';
	//是否开启日志记录
	protected $enabled	= TRUE;
	//错误类别
	protected $levels	= array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');
	
	public function __construct()
	{
		$config = & getConfig('default');
		if($config['logEnable'] === FALSE){
			$this->enabled = FALSE;
		} else {
			$this->logPath = ($config['logPath'] != '') ? $config['logPath'] : PUBLIC_DIR .'logs/';
		}
		unset($config);
	}

	// --------------------------------------------------------------------

	/**
	 *
	 * @param	$level	错误类别
	 * @param	$msg	错误信息
	 * @return	bool
	 */
	public function writeLog($level = 'error', $msg)
	{
		if ($this->enabled === FALSE)
		{
			return FALSE;
		}
		
		$level = strtoupper($level);

		if ( ! isset($this->levels[$level]))
		{
			return FALSE;
		}
		
		//日志路径
		$filepath = $this->logPath.'log-'.date('Y-m-d').'.php';
		$message  = '';

		if ( ! file_exists($filepath))
		{
			$message .= "<"."?php  if (! defined ( 'PROJECT_DIR' )) exit ( 'No direct script access allowed' );?".">\n\n";
		}

		if ( ! $fp = @fopen($filepath, 'a+'))
		{
			return FALSE;
		}

		$message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($this->dateFmt). ' --> '.$msg."\n";

		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($filepath, 0766);
		return TRUE;
	}

}
// END Log Class
