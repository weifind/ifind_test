<?php

if (! defined ( 'PROJECT_DIR' )) exit ( 'No direct script access allowed' );

class CalendarClass
{
	
	protected $localTime;
	private $temp = '';
	public $template = '';
	public $startDay = 'sunday';
	public $monthType = 'long';
	public $dayType = 'short';
	public $showNextPrev = FALSE;
	public $nextPrevUrl = '';
	
	/**
	 * Constructor
	 *
	 * Loads the calendar language file and sets the default time reference
	 */
	public function __construct($config = array()) {
		
		$this->localTime = time ();
		
		if (count($config) > 0) {
			$this->initialize ( $config );
		}
		
		//log_message ( 'debug', "Calendar Class Initialized" );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 日历格式参数初始化
	 * 
	 * @return void
	 */
	function initialize($config = array()) {
		foreach ( $config as $key => $val ) {
			if (isset ( $this->$key )) {
				$this->$key = $val;
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 获取日历
	 *
	 * @access public
	 * @return string
	 */
	function generate($year = '', $month = '', $data = array()) {
		// Set and validate the supplied month/year
		if ($year == '') $year = date ( "Y", $this->localTime );
		
		if ($month == '') $month = date ( "m", $this->localTime );
		
		if (strlen ( $year ) == 1) $year = '200' . $year;
		
		if (strlen ( $year ) == 2) $year = '20' . $year;
		
		if (strlen ( $month ) == 1) $month = '0' . $month;
		
		$adjustedDate = $this->adjustDate ( $month, $year );
		
		$month = $adjustedDate ['month'];
		$year = $adjustedDate ['year'];
		
		// 当前月份的天数
		$totalDays = $this->getTotalDays ( $month, $year );
		
		// 星期顺序表
		$startDays = array (
				'sunday' => 0,
				'monday' => 1,
				'tuesday' => 2,
				'wednesday' => 3,
				'thursday' => 4,
				'friday' => 5,
				'saturday' => 6 
		);
		$startDay = (! isset ( $startDays [$this->startDay] )) ? 0 : $startDays [$this->startDay];
		
		// Set the starting day number
		$localDate = mktime ( 12, 0, 0, $month, 1, $year );
		$date = getdate ( $localDate );
		$day = $startDay + 1 - $date ["wday"];
		
		while ( $day > 1 ) {
			$day -= 7;
		}
		
		// 设置 年/月/日
		// 当前所在的时间、日期
		$curYear = date ( "Y", $this->localTime );
		$curMonth = date ( "m", $this->localTime );
		$curDay = date ( "j", $this->localTime );
		
		$is_current_month = ($curYear == $year and $curMonth == $month) ? TRUE : FALSE;
		
		// 日历显示样式 $this->temp
		$this->parseTemplate ();
		
		// $this->temp隐式定义
		$out = $this->temp ['table_open'];
		$out .= "\n";
		
		$out .= "\n";
		$out .= $this->temp ['heading_row_start'];
		$out .= "\n";
		
		// 前一月的链接
		if ($this->showNextPrev == TRUE) {
			// url中增加一个反斜杠 /
			$this->nextPrevUrl = preg_replace ( '/(.+?)\/*$/', "\\1/", $this->nextPrevUrl );
			
			$adjustedDate = $this->adjustDate ( $month - 1, $year );
			$out .= str_replace ( '{previous_url}', $this->nextPrevUrl . $adjustedDate ['year'] . '/' . $adjustedDate ['month'], $this->temp ['heading_previous_cell'] );
			$out .= "\n";
		}
		
		// Heading containing the month/year
		$colspan = ($this->showNextPrev == TRUE) ? 5 : 7;
		
		$this->temp ['heading_title_cell'] = str_replace ( '{colspan}', $colspan, $this->temp ['heading_title_cell'] );
		$this->temp ['heading_title_cell'] = str_replace ( '{heading}', $this->getMonthName ( $month ) . "&nbsp;" . $year, $this->temp ['heading_title_cell'] );
		
		$out .= $this->temp ['heading_title_cell'];
		$out .= "\n";
		
		// 下一个月的链接
		if ($this->showNextPrev == TRUE) {
			$adjustedDate = $this->adjustDate ( $month + 1, $year );
			$out .= str_replace ( '{next_url}', $this->nextPrevUrl . $adjustedDate ['year'] . '/' . $adjustedDate ['month'], $this->temp ['heading_next_cell'] );
		}
		
		$out .= "\n";
		$out .= $this->temp ['heading_row_end'];
		$out .= "\n";
		
		// Write the cells containing the days of the week
		$out .= "\n";
		$out .= $this->temp ['week_row_start'];
		$out .= "\n";
		
		$dayNames = $this->getDayNames ();
		
		for($i = 0; $i < 7; $i ++) {
			$out .= str_replace ( '{week_day}', $dayNames [($startDay + $i) % 7], $this->temp ['week_day_cell'] );
		}
		
		$out .= "\n";
		$out .= $this->temp ['week_row_end'];
		$out .= "\n";
		
		// 日历主体，即那个30多个数字
		while ( $day <= $totalDays ) {
			$out .= "\n";
			$out .= $this->temp ['cal_row_start'];
			$out .= "\n";
			
			for($i = 0; $i < 7; $i ++) {
				$out .= ($is_current_month == TRUE and $day == $curDay) ? $this->temp ['cal_cell_start_today'] : $this->temp ['cal_cell_start'];
				
				if ($day > 0 and $day <= $totalDays) {
					if (isset ( $data [$day] )) {
						// Cells with content
						$temp = ($is_current_month == TRUE and $day == $curDay) ? $this->temp ['cal_cell_content_today'] : $this->temp ['cal_cell_content'];
						$out .= str_replace ( '{day}', $day, str_replace ( '{content}', $data [$day], $temp ) );
					} else {
						// Cells with no content
						$temp = ($is_current_month == TRUE and $day == $curDay) ? $this->temp ['cal_cell_no_content_today'] : $this->temp ['cal_cell_no_content'];
						$out .= str_replace ( '{day}', $day, $temp );
					}
				} else {
					// Blank cells
					$out .= $this->temp ['cal_cell_blank'];
				}
				
				$out .= ($is_current_month == TRUE and $day == $curDay) ? $this->temp ['cal_cell_end_today'] : $this->temp ['cal_cell_end'];
				$day ++;
			}
			
			$out .= "\n";
			$out .= $this->temp ['cal_row_end'];
			$out .= "\n";
		}
		
		$out .= "\n";
		$out .= $this->temp ['table_close'];
		
		return $out;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 几月份
	 *
	 * @return string
	 */
	function getMonthName($month) {
		if ($this->monthType == 'short') {
			$monthNames = array (
					'01' => 'cal_jan',
					'02' => 'cal_feb',
					'03' => 'cal_mar',
					'04' => 'cal_apr',
					'05' => 'cal_may',
					'06' => 'cal_jun',
					'07' => 'cal_jul',
					'08' => 'cal_aug',
					'09' => 'cal_sep',
					'10' => 'cal_oct',
					'11' => 'cal_nov',
					'12' => 'cal_dec' 
			);
		} else {
			$monthNames = array (
					'01' => 'cal_january',
					'02' => 'cal_february',
					'03' => 'cal_march',
					'04' => 'cal_april',
					'05' => 'cal_mayl',
					'06' => 'cal_june',
					'07' => 'cal_july',
					'08' => 'cal_august',
					'09' => 'cal_september',
					'10' => 'cal_october',
					'11' => 'cal_november',
					'12' => 'cal_december' 
			);
		}
		
		$month = $monthNames [$month];
		
		return ucfirst ( str_replace ( 'cal_', '', $month ) );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 星期几
	 *
	 *
	 * @return array
	 */
	function getDayNames($dayType = '') {
		if ($dayType != '') $this->dayType = $dayType;
		
		if ($this->dayType == 'long') {
			$dayNames = array (
					'sunday',
					'monday',
					'tuesday',
					'wednesday',
					'thursday',
					'friday',
					'saturday' 
			);
		} elseif ($this->dayType == 'short') {
			$dayNames = array (
					'sun',
					'mon',
					'tue',
					'wed',
					'thu',
					'fri',
					'sat' 
			);
		} else {
			$dayNames = array (
					'su',
					'mo',
					'tu',
					'we',
					'th',
					'fr',
					'sa' 
			);
		}
		
		$days = array ();
		foreach ( $dayNames as $val ) {
			$days [] = ucfirst ( $val );
		}
		
		return $days;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 年/月 格式化
	 * 
	 * @return array (month,year)
	 */
	function adjustDate($month, $year) {
		$date = array ();
		
		$date ['month'] = $month;
		$date ['year'] = $year;
		
		while ( $date ['month'] > 12 ) {
			$date ['month'] -= 12;
			$date ['year'] ++;
		}
		
		while ( $date ['month'] <= 0 ) {
			$date ['month'] += 12;
			$date ['year'] --;
		}
		
		if (strlen ( $date ['month'] ) == 1) {
			$date ['month'] = '0' . $date ['month'];
		}
		
		return $date;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 当前月份的天数
	 *
	 * @return integer
	 */
	function getTotalDays($month, $year) {
		$daysInMonth = array (
				31,
				28,
				31,
				30,
				31,
				30,
				31,
				31,
				30,
				31,
				30,
				31 
		);
		
		if ($month < 1 or $month > 12) {
			return 0;
		}
		
		// 判断下是否为润年
		if ($month == 2) {
			if ($year % 400 == 0 or ($year % 4 == 0 and $year % 100 != 0)) {
				return 29;
			}
		}
		
		return $daysInMonth [$month - 1];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 日历默认模板格式
	 *
	 * @access public
	 * @return array
	 */
	function defaultTemplate() {
		return array (
				'table_open' => '<table border="0" cellpadding="4" cellspacing="0">',
				'heading_row_start' => '<tr>',
				'heading_previous_cell' => '<th><a href="{previous_url}">&lt;&lt;</a></th>',
				'heading_title_cell' => '<th colspan="{colspan}">{heading}</th>',
				'heading_next_cell' => '<th><a href="{next_url}">&gt;&gt;</a></th>',
				'heading_row_end' => '</tr>',
				'week_row_start' => '<tr>',
				'week_day_cell' => '<td>{week_day}</td>',
				'week_row_end' => '</tr>',
				'cal_row_start' => '<tr>',
				'cal_cell_start' => '<td>',
				'cal_cell_start_today' => '<td>',
				'cal_cell_content' => '<a href="{content}">{day}</a>',
				'cal_cell_content_today' => '<a href="{content}"><strong>{day}</strong></a>',
				'cal_cell_no_content' => '{day}',
				'cal_cell_no_content_today' => '<span style="color:#ff0000;"><strong>{day}</strong></span>',
				'cal_cell_blank' => '&nbsp;',
				'cal_cell_end' => '</td>',
				'cal_cell_end_today' => '</td>',
				'cal_row_end' => '</tr>',
				'table_close' => '</table>' 
		);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * 解析日历前台显示样式
	 * 
	 * @access public
	 * @return void
	 */
	function parseTemplate() {
		$this->temp = $this->defaultTemplate ();
		
		if ($this->template == '') {
			return;
		}
		
		$today = array (
				'cal_cell_start_today',
				'cal_cell_content_today',
				'cal_cell_no_content_today',
				'cal_cell_end_today' 
		);
		
		foreach ( array (
				'table_open',
				'table_close',
				'heading_row_start',
				'heading_previous_cell',
				'heading_title_cell',
				'heading_next_cell',
				'heading_row_end',
				'week_row_start',
				'week_day_cell',
				'week_row_end',
				'cal_row_start',
				'cal_cell_start',
				'cal_cell_content',
				'cal_cell_no_content',
				'cal_cell_blank',
				'cal_cell_end',
				'cal_row_end',
				'cal_cell_start_today',
				'cal_cell_content_today',
				'cal_cell_no_content_today',
				'cal_cell_end_today' 
		) as $val ) {
			if (preg_match ( '/\{'. $val . '\}(.*?)\{\/' . $val . '\}/si', $this->template, $match )) {
				$this->temp [$val] = $match ['1'];
			} else {
				if (in_array ( $val, $today, TRUE )) {
					$this->temp [$val] = $this->temp [str_replace ( '_today', '', $val )];
				}
			}
		}
	}

}

/* End of file CalendarClass.php */