<?php
namespace _pluginNamespace_\_pluginName_\controllers;

use _pluginNamespace_\_pluginName_\models\_modelName_;

use core\libs\{controller, request};
use core\widgets\form;

class _controllerName_Controller extends controller {

	public function routeItems() {
		$_modelName_s = _modelName_::find()->all();

		return $this->render('items', ['_modelName_s' => $_modelName_s]);
	}


	public function routeItem($_modelName__id) {
		$_modelName_ = _modelName_::findOne(['id' => $_modelName__id]);

		return $this->render('item', ['_modelName_' => $_modelName_, 'uriBack' => '/_routeName_']);
	}

	public function routeAdd() {
		$_modelName_ = new _modelName_();

		if ($_modelName_->loadSave()) {
			$this->redirect('/_routeName_/'.$_modelName_->id);
		}

		$form = new form($_modelName_);
		return $this->render('form', ['form' => $form, 'uriBack' => '/_routeName_']);
	}

	public function routeUpdate($_modelName__id) {
		$_modelName_ = _modelName_::findOne(['id' => $_modelName__id]);

		if ($_modelName_->loadSave()) {
			$this->redirect('/_routeName_/'.$_modelName_->id);
		}

		$form = new form($_modelName_);
		return $this->render('form', ['form' => $form, 'uriBack' => '/_routeName_']);
	}

	public function routeDelete($_modelName__id) {
		$_modelName__id = request::post('id');
		if ($_modelName__id) {
			$_modelName_ = _modelName_::findOne(['id' => $_modelName__id]);
			if ($_modelName_) {
				$_modelName_->delete();
			}
		}
		$this->redirect('/_modelName_s');
	}

}