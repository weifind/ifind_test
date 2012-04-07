<?php
if (! defined ( 'PROJECT_DIR' ))
	exit ( 'No direct script access allowed' );

class AdminIfind extends Ifind
{
	public function test(){
		$this->display('admin/Admintest');
	}
}