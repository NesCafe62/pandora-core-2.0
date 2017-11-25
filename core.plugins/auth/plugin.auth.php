<?php
namespace core\plugins;

use core\libs\plugin;

class auth extends plugin {

	protected $userModelName = '';

	protected $user = false;

	protected function afterConstruct() {
		$this->userModelName = $this->config['user'];
	}

	public static function getUser() {
		$self = self::instance();
		if ($self->user === false) {
			$session = $self->session();
			$user_id = $session->user_id ?? 0;
			if ($user_id) {
				$self->user = $self->userModelName::findOne(['id' => $user_id]);
				if (!$self->user) {
					$self->user = null;
					unset($session->user_id);
				}
			} else {
				$self->user = null;
			}
		}
		return $self->user;
	}

	public static function logout() {
		$self = self::instance();
		$session = $self->session();
		unset($session->user_id);
	}

	public static function authoriseUser($user) {
		$self = self::instance();
		$self->user = $user;
		$session = $self->session(); // maybe clear old session before
		$session->user_id = $user->id;
		return true;
	}

	public static function authorise($user, $password) {
		if (!$user) {
			return false;
		}
		if ($user->checkPassword($password)) {
			/* $self = self::instance();
			$self->user = $user;
			$session = $self->session();
			$session->user_id = $user->id;
			return true; */
			return self::authoriseUser($user);
		}
		return false;
	}


}