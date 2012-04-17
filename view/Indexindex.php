<html>
<head>
<title>welcome to ifind framework!</title>
<link rel="stylesheet" href="/public/css/rightmenu2.css" type="text/css" />
</head>
<body>
	<div id="header">
		<h1>ifind framework</h1>
	</div>
	<div class="colmask">
		<div class="collleft">
			<!-- main menu -->
			<div class="col1">
				<p>
					this is simple framework of PHP.<br /> the sae address:<a
						href="http://weifind.sinaapp.com" target="_blank">sae_weifind</a><br />
					the git address:<a href="https://github.com/weifind/ifind_test"
						target="_blank">git_weifind</a><br /> for the code,there are some
					differences between in sae and git,if you have used sae,you should
					know that.<br /> for example,<br />
				</p>
				<ul>
					<li>there is no IO:
						<ul>
							<li>the html template storage in memcache.<br /></li>
							<li>avatar/upload file/other Persistent storaged file,saved in
								storage.<br />
							</li>
							<li>session also can saved in many place,like
								database/memcache,but not harddisk.<br />
							</li>
							<li>log is important,we can use sae_debug() and
								sae_set_display_errors().<br />
							</li>
						</ul>
					</li>
					<li>continued...</li>
				</ul>
				for now,you can testing by:<br />
				<ul>
					<li><a href="http://weifind.sinaapp.com/index/testsecurity/xss/{$xss}"
						target="_blank">testSecurity</a>
					<code>http://weifind.sinaapp.com/index/testsecurity/xss/< script >alert('xss')< /script ></code></li>
					<li><a href="http://weifind.sinaapp.com/index/testcalendar"
						target="_blank">testCalendar</a><code>http://weifind.sinaapp.com/index/testcalendar</code></li>
					<li><a href="http://weifind.sinaapp.com/index/testcaptcha"
						target="_blank">testCaptcha</a><code>http://weifind.sinaapp.com/index/testcaptcha</code></li>
					<li>more...</li>
				</ul>
				if you are interested in it(small framework),you can read the <a
					href="https://github.com/weifind/ifind_test" target="_blank">source
					code</a>. <br />
			</div>
			<!-- right menu -->
			<div class="col2">
				<div class="rightmenu">app for testing</div>
				
				</p>
			</div>
		</div>
	</div>
	<div id="footer">&copy; 2012&nbsp;supported by <a href="http://ifind.cc/">ifind</a></div>
</body>
</html>