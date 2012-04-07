<?php
if (! defined ( 'PROJECT_DIR' )) exit ( 'No direct script access allowed' );

class SecurityClass
{

	//url随机数
	protected $xssHash = '';

	//跨站点请求的随机数
	protected $csrfHash = '';
	
	// 跨站点请求的有效cookie时间，默认两个小时（秒）
	protected $csrfExpire = 7200;

	//跨站点请求中的标记
	protected $csrfTokenname = 'csrf_token_name';
	
	//跨站点请求中的cookie名
	protected $csrfCookiename = 'csrf_cookie_name';
	
	//csrf的配置文件
	protected $csrfConfig = array();
	
	//绝对过滤数据
	protected $neverAllowed = array (
			'document.cookie' => '[removed]',
			'document.write' => '[removed]',
			'.parentNode' => '[removed]',
			'.innerHTML' => '[removed]',
			'window.location' => '[removed]',
			'-moz-binding' => '[removed]',
			'<!--' => '&lt;!--',
			'-->' => '--&gt;',
			'<![CDATA[' => '&lt;![CDATA[',
			'<comment>' => '&lt;comment&gt;' 
	);
	
	//补充上面，正则过滤
	protected $neverAllowedRegex = array (
			'javascript\s*:' => '[removed]',
			'expression\s*(\(|&\#40;)' => '[removed]', // CSS and IE
			'vbscript\s*:' => '[removed]', // IE, surprise!
			'Redirect\s+302' => '[removed]' 
	);
	
	public function __construct() {
		// CSRF的三个配置参数
		$this->csrfConfig = &getConfig('csrf');
		$this->csrfExpire = $this->csrfConfig['csrfExpire'];
		$this->csrfTokenname = $this->csrfConfig['csrfTokenname'];
		$this->csrfCookiename = $this->csrfConfig['csrfCookiename'];
		
		// Set the CSRF hash
		$this->csrfSetHash ();
		
		log_message ( 'debug', "Security Class Initialized" );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 验证跨站请求
	 *
	 * @return object
	 */
	public function csrfVerify() {
		// 不存在POST数据，则设置cookie
		if (count ( $_POST ) == 0) {
			return $this->csrfSetCookie ();
		}
		
		// POST中的标记和COOKIE中的cookie名要一致
		if (! isset ( $_POST [$this->csrfTokenname] ) or ! isset ( $_COOKIE [$this->csrfCookiename] )) {
			$this->csrfShowError ();
		}
		if ($_POST [$this->csrfTokenname] != $_COOKIE [$this->csrfCookiename]) {
			$this->csrfShowError ();
		}
		
		//当验证合法后，删除tokenname,cookiename，重新设置
		unset ( $_POST [$this->csrfTokenname] );
		unset ( $_COOKIE [$this->csrfCookiename] );

		$this->csrfSetHash ();
		$this->csrfSetCookie ();
		
		log_message ( 'debug', "CSRF token verified " );
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 跨站请求中cookie独立设置 
	 * csrf.php中自行配置
	 * 
	 * @return object
	 */
	public function csrfSetCookie() {
		$expire = time () + $this->csrfExpire;
		$cookieSecure = ($this->csrfConfig['cookieSecure'] === TRUE) ? 1 : 0;
		
		if ($cookieSecure) {
			$req = isset ( $_SERVER ['HTTPS'] ) ? $_SERVER ['HTTPS'] : FALSE;
			
			if (! $req or $req == 'off') {
				return FALSE;
			}
		}
		
		setcookie ( $this->csrfCookiename, $this->csrfHash, $expire, $this->csrfConfig['cookiePath'], $this->csrfConfig['cookieDomain'], $cookieSecure );
		
		log_message ( 'debug', "CRSF cookie Set" );
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * csrf错误
	 *
	 * @return void
	 */
	public function csrfShowError() {
		show_error ( '你请求的操作不允许！' );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * csrfHash
	 *
	 * @return string
	 */
	public function getCsrfHash() {
		return $this->csrfHash;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * token标记，post中的
	 *
	 * @return string
	 */
	public function getCsrfTokenname() {
		return $this->csrfTokenname;
	}
	
	/**
	 * XSS Clean
	 *
	 * Sanitizes data so that Cross Site Scripting Hacks can be
	 * prevented. This function does a fair amount of work but
	 * it is extremely thorough, designed to prevent even the
	 * most obscure XSS attempts. Nothing is ever 100% foolproof,
	 * of course, but I haven't been able to get anything passed
	 * the filter.
	 *
	 * Note: This function should only be used to deal with data
	 * upon submission. It's not something that should
	 * be used for general runtime processing.
	 *
	 * This function was based in part on some code and ideas I
	 * got from Bitflux: http://channel.bitflux.ch/wiki/XSS_Prevention
	 *
	 * To help develop this script I used this great list of
	 * vulnerabilities along with a few other hacks I've
	 * harvested from examining vulnerabilities in other programs:
	 * http://ha.ckers.org/xss.html
	 *
	 *
	 * @return string
	 */
	public function xssClean($str, $isImage = FALSE) {
		
		//递归调用本方法
		if (is_array ( $str )) {
			while ( list ( $key ) = each ( $str ) ) {
				$str [$key] = $this->xssClean ( $str [$key] );
			}
			
			return $str;
		}
		
		$str = removeInvalid ( $str );
		
		// url中的实体合法化
		$str = $this->validateEntities ( $str );
		
		//预防类似这种url:http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D
		$str = rawurldecode ( $str );
		
		//有=赋值的地方，将一些特殊字符转换为实体
		$str = preg_replace_callback ( '/[a-z]+=([\'\"]).*?\\1/si', array (
				$this,
				'convertAttribute' 
		), $str );
		//自行编写html_entity_decode类似的行为
		//将实体转换为ascii
		$str = preg_replace_callback ( '/<\w+.*?(?=>|<|$)/si', array (
				$this,
				'decodeEntity' 
		), $str );
		
		$str = removeInvalid ( $str );
		
		//防止 ja	vascript这种数据		
		if (strpos ( $str, "\t" ) !== FALSE) {
			$str = str_replace ( "\t", ' ', $str );
		}
		
		//暂时保留已转换的字符串
		$convertedString = $str;
		
		// 过滤neverAllowed中的数据
		$str = $this->doNeverAllowed ( $str );
		
		//Note: XML标签也被替换
		if ($isImage === TRUE) {
			// Images have a tendency to have the PHP short opening and
			// closing tags every so often so we skip those and only
			// do the long opening tags.
			$str = preg_replace ( '/<\?(php)/i', "&lt;?\\1", $str );
		} else {
			$str = str_replace ( array (
					'<?',
					'?' . '>' 
			), array (
					'&lt;?',
					'?&gt;' 
			), $str );
		}
		
		//纠正j a v a s c r i p t这种字符串
		$words = array (
				'javascript',
				'expression',
				'vbscript',
				'script',
				'applet',
				'alert',
				'document',
				'write',
				'cookie',
				'window' 
		);
		foreach ( $words as $word ) {
			$temp = '';
			//j\s* \s*a\s* \s*...
			for($i = 0, $wordlen = strlen ( $word ); $i < $wordlen; $i ++) {
				$temp .= substr ( $word, $i, 1 ) . '\s*';
			}
			
			//存在这种字符则进行过滤 关键字用空格隔开的字符
			$str = preg_replace_callback ( '#(' . substr ( $temp, 0, - 3 ) . ')(\W)#is', array (
					$this,
					'compactExplodedWords' 
			), $str );
		}
		
		/*
		 * Remove disallowed Javascript in links or img tags We used to do some
		 * version comparisons and use of stripos for PHP5, but it is dog slow
		 * compared to these simplified non-capturing preg_match(), especially
		 * if the pattern exists in the string
		 */
		do {
			$original = $str;
			
			if (preg_match ( "/<a/i", $str )) {
				$str = preg_replace_callback ( '#<a\s+([^>]*?)(>|$)#si', array (
						$this,
						'_js_link_removal' 
				), $str );
			}
			
			if (preg_match ( "/<img/i", $str )) {
				$str = preg_replace_callback ( '#<img\s+([^>]*?)(\s?/?>|$)#si', array (
						$this,
						'_js_img_removal' 
				), $str );
			}
			
			if (preg_match ( "/script/i", $str ) or preg_match ( "/xss/i", $str )) {
				$str = preg_replace ( '#<(/*)(script|xss)(.*?)\>#si', '[removed]', $str );
			}
		} while ( $original != $str );
		
		unset ( $original );
		
		// Remove evil attributes such as style, onclick and xmlns
		$str = $this->removeEvilAttributes ( $str, $isImage );
		
		/*
		 * Sanitize naughty HTML elements If a tag containing any of the words
		 * in the list below is found, the tag gets converted to entities. So
		 * this: <blink> Becomes: &lt;blink&gt;
		 */
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback ( '#<(/*\s*)(' . $naughty . ')([^><]*)([><]*)#is', array (
				$this,
				'sanitizeNaughtyHtml' 
		), $str );
		
		/*
		 * Sanitize naughty scripting elements Similar to above, only instead of
		 * looking for tags it looks for PHP and JavaScript commands that are
		 * disallowed. Rather than removing the code, it simply converts the
		 * parenthesis to entities rendering the code un-executable. For
		 * example:	eval('some code') Becomes:		eval&#40;'some code'&#41;
		 */
		$str = preg_replace ( '#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str );
		
		// Final clean up
		// This adds a bit of extra precaution in case
		// something got through the above filters
		$str = $this->doNeverAllowed ( $str );
		
		/*
		 * Images are Handled in a Special Way - Essentially, we want to know
		 * that after all of the character conversion is done whether any
		 * unwanted, likely XSS, code was found. If not, we return TRUE, as the
		 * image is clean. However, if the string post-conversion does not
		 * matched the string post-removal of XSS, then it fails, as there was
		 * unwanted XSS code found and removed/changed during processing.
		 */
		
		if ($isImage === TRUE) {
			return ($str == $convertedString) ? TRUE : FALSE;
		}
		
		log_message ( 'debug', "XSS Filtering completed" );
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 生成xss的一个随机数
	 *
	 * @return string
	 */
	public function xssHash() {
		if ($this->xssHash == '') {
			mt_srand ();
			$this->xssHash = md5 ( time () + mt_rand ( 0, 1999999999 ) );
		}
		
		return $this->xssHash;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * HTML Entities Decode
	 *
	 * This function is a replacement for html_entity_decode()
	 *
	 * The reason we are not using html_entity_decode() by itself is because
	 * while it is not technically correct to leave out the semicolon
	 * at the end of an entity most browsers will still interpret the entity
	 * correctly. html_entity_decode() does not convert entities without
	 * semicolons, so we are left with our own little solution here. Bummer.
	 *
	 * @return string
	 */
	public function entityDecode($str, $charset = 'UTF-8') {
		if (stristr ( $str, '&' ) === FALSE) {
			return $str;
		}
		
		$str = html_entity_decode ( $str, ENT_COMPAT, $charset );
		$str = preg_replace ( '~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str );
		return preg_replace ( '~&#([0-9]{2,4})~e', 'chr(\\1)', $str );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 文件名过滤
	 *
	 * @return string
	 */
	public function sanitizeFilename($str, $relative_path = FALSE) {
		$bad = array (
				"../",
				"<!--",
				"-->",
				"<",
				">",
				"'",
				'"',
				'&',
				'$',
				'#',
				'{',
				'}',
				'[',
				']',
				'=',
				';',
				'?',
				"%20",
				"%22",
				"%3c", // <
				"%253c", // <
				"%3e", // >
				"%0e", // >
				"%28", // (
				"%29", // )
				"%2528", // (
				"%26", // &
				"%24", // $
				"%3f", // ?
				"%3b", // ;
				"%3d"  // =
		);
		
		if (! $relative_path) {
			$bad [] = './';
			$bad [] = '/';
		}
		
		$str = removeInvalid ( $str, FALSE );
		return stripslashes ( str_replace ( $bad, '', $str ) );
	}
	
	// ----------------------------------------------------------------
	
	/**
	 *
	 * xssClean中的一回调函数
	 * 纠正类似这样的字符 j a v a s c r i p t
	 *
	 * @return type
	 */
	protected function compactExplodedWords($matches) {
		return preg_replace ( '/\s+/s', '', $matches [1] ) . $matches [2];
	}
	
	// --------------------------------------------------------------------
	
	/*
	 * Remove Evil HTML Attributes (like evenhandlers and style) It removes the
	 * evil attribute and either: - Everything up until a space For example,
	 * everything between the pipes: <a
	 * |style=document.write('hello');alert('world');| class=link> - Everything
	 * inside the quotes For example, everything between the pipes: <a
	 * |style="document.write('hello'); alert('world');"| class="link"> @param
	 * string $str The string to check @param boolean $is_image TRUE if this is
	 * an image @return string The string with the evil attributes removed
	 */
	protected function removeEvilAttributes($str, $isImage) {
		// js事件相应函数，style,xmlns
		//用户将不能使用这几个关键字符
		$evilAttributes = array (
				'on\w*',
				'style',
				'xmlns',
				'formaction' 
		);
		
		if ($isImage === TRUE) {
			/* Note:个人木有接触到过
			 * Adobe Photoshop puts XML metadata into JFIF images, including
			 * namespacing, so we have to allow this for images.
			 */
			unset ( $evilAttributes [array_search ( 'xmlns', $evilAttributes )] );
		}
		
		do {
			$count = 0;
			$attribs = array ();
			
			//事件中 不带引号的非法属性
			preg_match_all ( '/(' . implode ( '|', $evilAttributes ) . ')\s*=\s*([^\s]*)/is', $str, $matches, PREG_SET_ORDER );
			foreach ( $matches as $attr ) {
				$attribs [] = preg_quote ( $attr [0], '/' );
			}
			
			//(\\1 $evilAttributes)(\\2 ' ")
			preg_match_all ( '/(' . implode ( '|', $evilAttributes ) . ')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER );
			foreach ( $matches as $attr ) {
				$attribs [] = preg_quote ( $attr [0], '/' );
			}
			
			//html中， 将$evilAttributes中的几个字符去掉
			if (count ( $attribs ) > 0) {
				$str = preg_replace ( '/<(\/?[^><]+?)([^A-Za-z\-])(' . implode ( '|', $attribs ) . ')([\s><])([><]*)/i', '<$1$2$4$5', $str, - 1, $count );
			}
		
		} while ( $count );
		
		return $str;
	}
	
	
	/**
	 * 替换 < > 标签为实体字符
	 *
	 *
	 * @return string
	 */
	protected function sanitizeNaughtyHtml($matches) {
		// encode opening brace
		$str = '&lt;' . $matches [1] . $matches [2] . $matches [3];
		
		//转换为实体标签
		$str .= str_replace ( array (
				'>',
				'<' 
		), array (
				'&gt;',
				'&lt;' 
		), $matches [4] );
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * JS Link Removal
	 * 主要针对href属性
	 * 
	 * Callback function for xss_clean() to sanitize links
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on link-heavy strings
	 *
	 * @return string
	 */
	protected function _js_link_removal($match) {
		$attributes = $this->filterAttributes ( str_replace ( array (
				'<',
				'>' 
		), '', $match [1] ) );
		
		return str_replace ( $match [1], preg_replace ( '#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', '', $attributes ), $match [0] );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * JS Image Removal
	 * 主要针对image的src属性
	 * 
	 * Callback function for xss_clean() to sanitize image tags
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on image tag heavy strings
	 *
	 * @return string
	 */
	protected function _js_img_removal($match) {
		$attributes = $this->filterAttributes ( str_replace ( array (
				'<',
				'>' 
		), '', $match [1] ) );
		
		return str_replace ( $match [1], preg_replace ( '#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', '', $attributes ), $match [0] );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 字符替换
	 * xssClean callback调用
	 * 
	 * @return string
	 */
	protected function convertAttribute($match) {
		return str_replace ( array (
				'>',
				'<',
				'\\' 
		), array (
				'&gt;',
				'&lt;',
				'\\\\' 
		), $match [0] );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 字符过滤
	 *
	 * Filters tag attributes for consistency and safety
	 *
	 * @return string
	 */
	protected function filterAttributes($str) {
		$out = '';
		//\042 \047的意思？
		if (preg_match_all ( '#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches )) {
			foreach ( $matches [0] as $match ) {
				$out .= preg_replace ( '#/\*.*?\*/#s', '', $match );
			}
		}
		
		return $out;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * HTML Entity Decode Callback
	 *
	 * Used as a callback for XSS Clean
	 *
	 * @return string
	 */
	protected function decodeEntity($match) {
		return $this->entityDecode ( $match [0], 'UTF-8' );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 检查url实体的合法性，正则替换
	 *
	 * 在xssClean()中被调用
	 *
	 * @return string
	 */
	protected function validateEntities($str) {
		// 901119URL5918AMP18930PROTECT8198
		//url中用xssHash替换&连接符
		$str = preg_replace ( '|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xssHash () . "\\1=\\2", $str );
		
		/*
		 * Validate standard character entities Add a semicolon if missing. We
		 * do this to enable the conversion of entities to ASCII later.
		 */
		$str = preg_replace ( '#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str );
		
		/*
		 * Validate UTF16 two byte encoding (x00) Just as above, adds a
		 * semicolon if missing.
		 */
		$str = preg_replace ( '#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str );
		
		//& 替换之前的xssHash
		$str = str_replace ( $this->xssHash (), '&', $str );
		
		return $str;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * 进行neverAllowed中字符的替换
	 *
	 * @return string
	 */
	protected function doNeverAllowed($str) {
		foreach ( $this->neverAllowed as $key => $val ) {
			$str = str_replace ( $key, $val, $str );
		}
		
		foreach ( $this->neverAllowedRegex as $key => $val ) {
			$str = preg_replace ( "#" . $key . "#i", $val, $str );
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 当前cookie存在的话，则直接使用作为hash值
	 * 否则随机生成一个
	 * 
	 * @return string
	 */
	protected function csrfSetHash() {
		if ($this->csrfHash == '') {
			if (isset ( $_COOKIE [$this->csrfCookiename] ) && $_COOKIE [$this->csrfCookiename] != '') {
				return $this->csrfHash = $_COOKIE [$this->csrfCookiename];
			}
			
			return $this->csrfHash = md5 ( uniqid ( rand (), TRUE ) );
		}
		
		return $this->csrfHash;
	}

}
// END Security Class