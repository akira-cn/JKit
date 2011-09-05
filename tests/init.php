<?php defined('SYSPATH') or die('No direct script access.');

Route::set('jkit_tests', 'tests(/<action>(/<id>))')
	->defaults(array(
		'controller' => 'tests',
		'action'     => 'index',
	));