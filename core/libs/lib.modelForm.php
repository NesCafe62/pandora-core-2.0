<?php namespace core\libs;


class modelForm extends model {

	public $message = null;

	public function setMessage($type, $message) {
		$this->message = [$type, $message];
	}

	public function getMessage() {
		return $this->message;
	}
}