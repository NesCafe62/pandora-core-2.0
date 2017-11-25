<?php
namespace _pluginNamespace_\_pluginName_\models;

use core\libs\model;

class _modelName_ extends model {

	protected static $table = '_modelName_';

	public static function getFields() {
		return [
			// please specify fields

			// 'title',
			// 'count',
		];
	}


	// uncomment this after you will specify create fields:

	// protected static $autoCreate = true;

	public static function getCreateFields() {
		return [
			// please specify create fields

			// 'id' => ['int', 11, 'autoinc' => true],
			// 'title' => ['varchar', 1024],
			// 'count' => ['int', 11],
		];
	}


	public static function rules() {
		return [
			// please specify rules

			// 'title' => ['required','trim'],
			// 'count' => ['required','int'],
		];
	}

}