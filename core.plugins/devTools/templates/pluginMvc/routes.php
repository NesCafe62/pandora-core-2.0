<?php

return [
	'_controllerName_Controller' => [
		'routeItems' => '/_routeName_',
		'routeItem' => '/_routeName_/$_modelName__id',

		'routeAdd' => '/_routeName_/add',
		'routeUpdate' => '/_routeName_/$_modelName__id/update',
		'routeDelete' => ['/_routeName_', 'post' => ['action' => 'delete'] ],
	]
];
