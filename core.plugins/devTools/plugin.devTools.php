<?php
namespace core\plugins;

use core\libs\file;
use core\libs\plugin;

use \debug;

class devTools extends plugin {

	// commandAppCreate

	// commandAppDelete

	// commandAppRename


	// commandCoreUpdate


	// commandPluginsInstall

	// commandPluginsUninstall

	// commandPluginsUpdate

	// enable
	// disable

	// commandPluginsRename

	// commandPluginsDelete

	private function createFromTemplate($templateName, $targetPath, $variables) {
		$templatePath = $this->path.'templates/'.$templateName;

		$targetPath = trimRight($targetPath,'/').'/';

		$replaces = [];
		$values = [];
		foreach ($variables as $key => $value) {
			$replaces[] = '_'.$key.'_';
			$values[] = $value;
		}

		$files = file::search($templatePath, file::FILE_TYPES_ALL, true);
		foreach ($files as $file) {
			$target = $targetPath.str_replace($replaces, $values, trimLeft($file, $templatePath.'/'));
			if (is_dir($file)) {
				file::createPath($target);
			} else {
				$content = str_replace($replaces, $values, file_get_contents($file));
				file_put_contents($target, $content);
			}
		}
	}

	public function commandPluginsCreate($pluginPath, $createMvc = false) {
		if (is_dir($pluginPath)) {
			trigger_error(debug::_('DEV_TOOLS_CREATE_PLUGIN_PATH_NOT_EMPTY', $pluginPath), E_USER_WARNING);
			return false;
		}

		file::createPath($pluginPath);

		// $pluginPath = 'apps/app/plugins/books'
		// $pluginNamespace = 'apps/app/plugins'
		// $pluginName = 'books'

		// $pluginPath = 'core.plugins/books'
		// $pluginNamespace = 'core/plugins'
		// $pluginName = 'books'

		$arr = explode('/', str_replace('core.plugins', 'core/plugins', trim($pluginPath, '/')));
		$pluginName = array_pop($arr);
		$pluginNamespace = implode('\\', $arr);

		if ($createMvc) {
			$itemName = trimRight($pluginName, 's');
			$controllerName = $itemName; // 'book'
			$modelName = $itemName; // 'book'
			$routeName = $pluginName; // 'books'

			/* if (endsWith($pluginName, 's')) { // books
				$itemName = trimRight($pluginName, 's');
				$itemsName = $pluginName;
				$controllerName = $itemName; // book
				$modelName = $itemName; // 'book'
				$routeName = $itemsName; // 'books'
			} else { // structure
				$itemName = $pluginName.'_item'; // 'structure_item'
				$itemsName = $pluginName.'_items'; // 'structure_items'
				$controllerName = $pluginName; // 'structure'
				$modelName = $pluginName.'Item'; // 'structureItem'
				$routeName = $pluginName; // 'structure'
			} */

			// todo: $modelName & $itemsName in templates

			[$res, $msg] = $this->createFromTemplate('pluginMvc', $pluginPath, [
				'pluginNamespace' => $pluginNamespace,
				'pluginName' => $pluginName,
				'controllerName' => $controllerName,
				'modelName' => $modelName,
				'routeName' => $routeName,
			]);
		} else {
			[$res, $msg] = self::createFromTemplate('plugin', $pluginPath, [
				'pluginNamespace' => $pluginNamespace,
				'pluginName' => $pluginName,
			]);
		}

		if (!$res) {
			debug::addLog([
				'type' => debug::E_CONSOLE,
				'typeLabel' => 'devTools',
				'message' => 'Plugin "'.$pluginPath.'" successfully created',
			]);
		} else {
			debug::addLog([
				'type' => E_USER_WARNING,
				'typeLabel' => 'devTools',
				'message' => 'Plugin create error: '.$msg,
			]);
		}

		return $res;
	}

}