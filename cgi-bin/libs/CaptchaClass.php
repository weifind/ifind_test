<?php

class CaptchaClass {
	//验证码字符长度
	private $wordLength = 4;
	
	//验证码值
	private $word = '';
	
	//图片长度
	private $imgWidth = 80;
	
	//图片宽度
	private $imgHeight = 20;
	
	//创建时间 
	private $createTime = 0;
	//过期时间 秒
	private $expiration = 600;
	
	//字体路径 默认使用GD库的字体
	public $fontPath = '';
	
	/**
	 * 
	 */
	public function __construct(){	}
	
	/**
	 * 随机生成字符串值
	 * 
	 */
	public function randWord(){
		
	}
	/**
	 * 获取验证码
	 * 
	 *
	 * 使用：如利用数据库，建议包含字段word,time,ip
	 */
	
	public function createCaptcha($config = array()) {
		//读取一些配置参数
		if($config != array() && !empty($config)){
			$this->imgHeight = $config['height'];
			$this->wordLength = $config['length'];
			$this->imgWidth = $config['width'];
			$this->expiration = $config['expiration'];
		}
		if (! extension_loaded ( 'gd' )) {
			return FALSE;
		}
	
		//验证字符组合
		if ($this->word == '') {
			$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	
			$str = '';
			for($i = 0; $i < $this->wordLength; $i ++) {
				$str .= substr ( $pool, mt_rand ( 0, strlen ( $pool ) - 1 ), 1 );
			}
	
			$this->word = $str;
		}
	
		//字符长度以及字符在图片中坐标
		$angle = ($this->wordLength >= 6) ? rand ( - ($this->wordLength - 6), ($this->wordLength - 6) ) : 0;
		$x_axis = rand ( 6, (100 / $this->wordLength) );
		$y_axis = ($angle >= 0) ? rand ( $this->imgHeight, $this->imgWidth ) : rand ( 6, $this->imgHeight );
	
		// 创建图片
		// imagecreatetruecolor()不适用gif
		if (function_exists ( 'imagecreatetruecolor' )) {
			$im = imagecreatetruecolor ( $this->imgWidth, $this->imgHeight );
		} else {
			$im = imagecreate ( $this->imgWidth, $this->imgHeight );
		}
	
		//着色
		$bg_color = imagecolorallocate ( $im, 255, 240, 245 );
		$border_color = imagecolorallocate ( $im, 153, 102, 102 );
		$text_color = imagecolorallocate ( $im, 205, 55, 0 );
		$grid_color = imagecolorallocate ( $im, 255, 182, 182 );
		$shadow_color = imagecolorallocate ( $im, 255, 240, 240 );
	
		// -----------------------------------
		// Create the rectangle
		// -----------------------------------
		
		ImageFilledRectangle ( $im, 0, 0, $this->imgWidth, $this->imgHeight, $bg_color );
	
		// -----------------------------------
		// Create the spiral pattern
		// -----------------------------------
	
		$theta = 1;
		$thetac = 7;
		$radius = 16;
		$circles = 20;
		$points = 32;
	
		for($i = 0; $i < ($circles * $points) - 1; $i ++) {
			$theta = $theta + $thetac;
			$rad = $radius * ($i / $points);
			$x = ($rad * cos ( $theta )) + $x_axis;
			$y = ($rad * sin ( $theta )) + $y_axis;
			$theta = $theta + $thetac;
			$rad1 = $radius * (($i + 1) / $points);
			$x1 = ($rad1 * cos ( $theta )) + $x_axis;
			$y1 = ($rad1 * sin ( $theta )) + $y_axis;
			imageline ( $im, $x, $y, $x1, $y1, $grid_color );
			$theta = $theta - $thetac;
		}
	
		// -----------------------------------
		// Write the text
		// -----------------------------------
	
		$use_font = ($this->fontPath != '' and file_exists ( $this->fontPath ) and function_exists ( 'imagettftext' )) ? TRUE : FALSE;
	
		if ($use_font == FALSE) {
			$font_size = 5;
			$x = rand ( 0, $this->imgWidth / ($this->wordLength / 3) );
			$y = 0;
		} else {
			$font_size = 16;
			$x = rand ( 0, $this->imgWidth / ($this->wordLength / 1.5) );
			$y = $font_size + 2;
		}
	
		for($i = 0; $i < $this->wordLength; $i ++) {
			if ($use_font == FALSE) {
				$y = rand ( 0, $this->imgHeight / 2 );
				imagestring ( $im, $font_size, $x, $y, substr ( $this->word, $i, 1 ), $text_color );
				$x += ($font_size * 2);
			} else {
				$y = rand ( $this->imgHeight / 2, $this->imgHeight - 3 );
				imagettftext ( $im, $font_size, $angle, $x, $y, $text_color, $this->fontPath, substr ( $this->word, $i, 1 ) );
				$x += $font_size;
			}
		}
	
		// -----------------------------------
		// Create the border
		// -----------------------------------
	
		imagerectangle ( $im, 0, 0, $this->imgWidth - 1, $this->imgHeight - 1, $border_color );
	
		// -----------------------------------
		// Generate the image
		// -----------------------------------
		header('Content-Type:image/jpeg');
		ImageJPEG ( $im );
		ImageDestroy ( $im );
		/*
		print_r(array (
				'word' => $this->word,
				'time' => microtime(),
				'expiration' => 600
		));*/
	}
	
	/* TODO
	 * 其他方法的扩展，视具体情况而定
	 * 
	 */
}