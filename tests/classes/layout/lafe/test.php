<?php defined('SYSPATH') or die('No direct script access.');

class Layout_Lafe_Test extends Layout{
	function render($default_view_file){
		return $this->a->fetch($default_view_file);
	}

	protected function layout_a($data){
		$this->{"Header test"}=array('test'=>1);
		$this->{"Body test"}=array('test'=>2);
		$this->{"Body/test"}=array('test'=>3);
		$this->{"//a.Footer/test"}=array('test'=>4);

		$this->b();

		$this->{"//a.Body b#2.Left b.Left test"}=array('test'=>8);  //another b.Left，不和上面那个b.Left合在一起，所以加一个id

		$this->_la_layout_xmap["a b#2"] += array(
												"myclass" => "test",
												"css" => "color:red",
											);
	}

	private function b(){
		$this->with("a.Body b");
			$this->{"Left test"}=array('test'=>5);
			$this->{"Right test"}=array('test'=>6);
			$this->{"Right test"}=array('test'=>7);
		$this->endwith();
	}
}