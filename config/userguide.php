<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	// Leave this alone
	'modules' => array(
		// This should be the path to this modules userguide pages, without the 'guide/'. Ex: '/guide/modulename/' would be 'modulename'
		'jkit' => array(

			// Whether this modules userguide pages should be shown
			'enabled' => TRUE,
			
			// The name that should show up on the userguide index page
			'name' => 'JKit',

			// A short description of this module, shown on the index page
			'description' => 'JKit 是基于 [Kohana](kohana) 基础上再开发的一个框架',
			
			// Copyright message, shown in the footer for this module
			'copyright' => '&copy; 2011 WED Team',
		),
	)
);
