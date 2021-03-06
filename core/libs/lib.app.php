<?php
namespace core\libs;

use console;

use Exception;
use debug;
use logger;
use core\libs\request;

class app {

	protected $config = [];

	protected $plugins = [];

	public $uri;
	public $uriBase;
	public $uriRelative;

	public $layout;

	public $page404;

	public function __construct($config) {
		$this->config = $config;
		$this->layout = $config['layout'] ?? 'main';
		$this->uriBase = trim($config['uriBase'] ?? '', '/').'/';
	}

	private function initParams($params) {
		[
			'path' => $this->path,
			'start_time' => $this->start_time
		] = $params;

		$this->daemon = $this->config['daemon'] ?? false;
		$this->debug = $this->config['debug'] ?? false;
		$this->base = $this->config['base'] ?? '/'.trimLeft(trimRight($this->path, '/').'/','/');
		// $this->uriBase = $this->config['uriBase'] ?? '/';
		// $this->layout = $this->config['layout'] ?? 'main.php';

		$this->page404 = $this->config['page404'] ?? [];

		if (!is_array($this->page404)) {
			$this->page404 = ['template' => $this->page404];
		}
	}

	public function getConfig() {
		return $this->config;
	}

	protected function getPluginsRoutes() {
		$plugins_path = $this->path.'/plugins/';
		$plugins_namespace = str_replace('/','\\',$plugins_path);

		$route_plugins = include($plugins_path.'plugins.php');
		$routes = [];

		foreach ($route_plugins as $plugin_name) {
			$plugin_path = $plugins_path.$plugin_name;
			$plugin_routes = include($plugin_path.'/routes.php');
			$route_plugin = $plugins_namespace.$plugin_name;
			/* foreach ($plugin_routes as $location => $route_method) {
				$routes[$location] = [$plugins_namespace.$plugin_name, $route_method];
			} */
			foreach ($plugin_routes as $controller_class => $controller_routes) {
				if ($controller_class == 'self') {
					$route_controller = null;
				} else {
					$route_controller = $route_plugin.'\\controllers\\'.$controller_class;
				}
				foreach ($controller_routes as $route_method => $location) {
					if (is_numeric($route_method)) {
						if (!isset($location[0]) && !isset($location[1])) {
							trigger_error(debug::_('APP_ROUTE_PLUGIN_ROUTE_METHOD_NOT_SET', $plugin_name, $controller_class), E_WARNING);
							continue;
						}
						$route_method = array_shift($location);
					}
					$params_post = [];
					$params_get = [];
					if (is_array($location)) {
						if (!isset($location[0])) {
							trigger_error(debug::_('APP_ROUTE_PLUGIN_ROUTE_LOCATION_NOT_SET', $plugin_name, $controller_class, $route_method), E_WARNING);
							continue;
						}
						$params_post = $location['post'] ?? [];
						$params_get = $location['get'] ?? [];
						$location = $location[0];
					}
					$routes[] = [$location, $params_post, $params_get, $route_plugin, $route_controller, $route_method];
				}
			}
		}

		$segments_cache = [];
		$getSegments = function ($route) use (&$segments_cache) {
			[$location, $params_post, $params_get] = $route;
			if (!isset($segments_cache[$location])) {
				$segments_cache[$location] = preg_replace_callback('#(/[^/]+)|/#', function($match) {
					$segment = ltrim($match[0],'/');
					return (($segment[0] ?? '') === '$') ? 'D' : 'Z';
				}, $location);
			}
			$params_weight = str_repeat('C', count($params_post)).str_repeat('A', count($params_get));
			return $segments_cache[$location].$params_weight;
		};

		usort($routes, function($route1, $route2) use ($getSegments) {
			return $getSegments($route2) <=> $getSegments($route1); // strlen($route2) <=> strlen($route1);
		});

		return $routes;
	}

	protected function route($uri) {
		$routes = $this->getPluginsRoutes();

		// console::log($routes);

		foreach ($routes as $route) {
			[$location, $params_post, $params_get, $route_plugin, $route_controller, $route_method] = $route;

			$args_names = [];
			$arg_index = 1;
			$pattern = preg_replace_callback('#\$([^/]+)#', function($matches) use (&$args_names, &$arg_index) {
				$args_names[$matches[1]] = $arg_index;
				$arg_index++;
				return '([^/]+)';
			}, $location);

			$pattern = '#^'.str_replace(['-','*'], ['\-','.+'], $pattern).'$#'; // << $ if strict match

			// console::log($pattern);

			if (!preg_match($pattern, $uri, $matches)) {
				continue;
			}

			$params_matched = true;
			foreach ($params_post as $param_name => $params_value) {
				if (is_numeric($param_name)) {
					$param_name = $params_value;
					$request_value = $this->request->post($param_name);
					$params_matched &= ($request_value !== null);
				} else {
					$request_value = $this->request->post($param_name);
					if (is_array($params_value)) {
						$params_matched = false;
					} else {
						$params_matched &= ($request_value === (string) $params_value);
					}
				}
				if (!$params_matched) {
					break;
				}
			}

			if (!$params_matched) {
				continue;
			}

			foreach ($params_get as $param_name => $params_value) {
				if (is_numeric($param_name)) {
					$param_name = $params_value;
					$request_value = $this->request->get($param_name);
					$params_matched &= ($request_value !== null);
				} else {
					$request_value = $this->request->get($param_name);
					if (is_array($params_value)) {
						$params_matched = false;
					} else {
						$params_matched &= ($request_value === (string) $params_value);
					}
				}
				if (!$params_matched) {
					break;
				}
			}

			if ($params_matched) {
				$arguments = [];
				foreach ($args_names as $arg_name => $arg_index) {
					// $arguments[$arg_name] = $matches[$arg_index];
					$arguments[] = $matches[$arg_index];
				}

				// console::log([$route_class, $route_method, $arguments]);

				return [$route_plugin, $route_controller, $route_method, $arguments];
			}
		}

		return ['', '', '', []];
	}

	public $db = null;

	protected $path;
	protected $start_time;

	protected $daemon;
	protected $debug;
	protected $base;

	public $request;

	private $logger;

	protected $jsParams = [];
	protected $pageScripts = [];
	protected $includedScripts = [];

	protected $pageStyles = [];

	public $content = '';

	public function jsParam($variable, $value) {
		$this->jsParams[$variable] = $value;
	}

	protected function getJsParams() {
		if (!$this->jsParams) {
			return '';
		}
		return 'var jsParams = '.json_encode($this->jsParams, JSON_UNESCAPED_UNICODE).';';
	}

	public function head() {
		$headHtml = $this->pageStyles();
		$jsParams = $this->getJsParams();
		if ($jsParams) {
			$headHtml .= '<script>'.$jsParams.'</script>';
		}
		return $headHtml;
	}

	// todo: move this to lib.header
	private $httpStatus;
	private $httpProtocol;
	private $httpHeaders = [];

	public static $httpStatusTexts = [
		'200' => 'OK',
		'301' => 'Moved Permanently',
		'303' => 'See Other',
		'304' => 'Not Modified',
		'305' => 'Use Proxy',
		'306' => 'Switch Proxy',
		'307' => 'Temporary Redirect',
		'308' => 'Permanent Redirect',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'408' => 'Request Timeout',
		'429' => 'Too Many Requests',
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'502' => 'Bad Gateway',
		'503' => 'Service Unavailable',
		'504' => 'Gateway Timeout',
		'520' => 'Unknown Error',
		'521' => 'Web Server Is Down',
		'522' => 'Connection Timed Out',
	];

	public static function httpStatusText($status) {
		return self::$httpStatusTexts[$status] ?? '';
	}

	public function httpHead() {
		$status = $this->httpStatus ?? 200;
		$protocol = $this->httpProtocol ?? 'HTTP/1.1';
		$statusText = self::httpStatusText($status);
		header($protocol.' '.$status.' '.$statusText, true, $status);
		foreach ($this->httpHeaders as $key => $value) {
			header($key.': '.$value);
		}
	}

	public function httpProtocol($protocol) {
		$this->httpProtocol = $protocol;
	}

	public function httpStatus($status) {
		$this->httpStatus = $status;
	}

	public function httpHeader($key, $value) {
		$this->httpHeaders[$key] = $value;
	}

	public function content() {
		return $this->content;
	}

	public function bodyBegin() {
		return '';
	}

	public function bodyEnd() {
		ob_start();
		if ($this->debug) {
			// event
			$profilerName = $this->config['profiler'] ?? '';
			if ($profilerName) {
				try {
					$profiler = $profilerName::instance(); // this is unsafe better do plugin::getInstance($profilerName);
					if (!method_exists($profiler, 'renderProfiler')) {
						throw new Exception(debug::_('APP_BODY_END_PROFILER_METHOD_NOT_EXIST', $profilerName.'::renderProfiler'), E_WARNING);
					}
					$profiler->renderProfiler();
				} catch (Exception $e) {
					trigger_error($e->getMessage(), E_USER_WARNING);
					debug::dumpLog();
				}
			} else {
				debug::dumpLog();
			}
		}
		return ob_get_clean();
	}

	public function style($src) {
		if (!in_array($src, $this->pageStyles)) {
			$this->pageStyles[] = $src;
		}
	}

	// $priority - if false priority=0. The lower priority, the higher script will be inserted
	public function script($src, $priority = 10) {
		if (!isset($this->includedScripts[$src])) {
			if ($priority === true) {
				$priority = 0;
			}

			$insertIndex = 0;
			foreach ($this->pageScripts as $index => $script) {
				$scriptPriority = $script[1];
				if ($scriptPriority > $priority) {
					break;
				}
				$insertIndex++;
			}
			array_splice($this->pageScripts, $insertIndex, 0, [[$src, $priority]]);
			$this->includedScripts[$src] = true;
		}
	}

	public function pageStyles() {
		if (!$this->pageStyles) {
			return '';
		}
		$styles_html = '';
		foreach ($this->pageStyles as $style) {
			$styles_html .= '<link rel="stylesheet" href="'.$style.'"/>';
		}
		return $styles_html;
	}

	public function pageScripts() {
		if (!$this->pageScripts) {
			return '';
		}
		$scripts_html = '';
		// echo html::dump($this->pageScripts);
		foreach ($this->pageScripts as $script) {
			[$src] = $script;
			$scripts_html .= '<script src="'.$src.'"></script>';
		}
		return $scripts_html;
	}

	public function getElapsedTime() {
		return microtime(true) - $this->start_time;
	}

	private function initProfiler() {
		if ($this->debug) {
			$profilerName = $this->config['profiler'] ?? '';
			if ($profilerName) {
				$profiler = $profilerName::instance(); // this is unsafe better do plugin::getInstance($profilerName);
				if (method_exists($profiler, 'beforeAppRender')) {
					$profiler->beforeAppRender();
				}
			}
		}
	}

	public function init($params) {
		$this->initParams($params);

		$connection_params = $this->config['db'] ?? null;
		if ($connection_params) {
			$this->db = new db($connection_params);
			$this->db->connect();
		}

		// $this->uriBase = '/test';
		// $this->uriBase = '/';
		if ($this->daemon) {
			$this->logger = new logger(false); // this must do debugger plugin
		} else {
			session::start();

			// request::init();
			$this->request = request::init(); // new httpRequest();

			$uri = trimRight(parse_url(environment::get('REQUEST_URI'), PHP_URL_PATH), '/');
			$uri_base = trimLeft($this->uriBase, '/');
			if ($uri_base) {
				$uri_relative = '/'.preg_replace('#^/'.preg_quote($uri_base).'\b/?#', '', $uri);
			} else {
				$uri_relative = '/'.trimLeft($uri, '/');
			}

			$this->uri = $uri;
			$this->uriRelative = $uri_relative;

			[$route_plugin, $route_controller, $route_method, $arguments] = $this->route($uri_relative);
			if ($route_plugin) { // $route_class) {
				$plugin = $route_plugin::instance(); // plugin

				if ($route_controller === null) {
					$route_class = $route_plugin;
					$route_object = $plugin;
				} else if (is_string($route_controller)) {
					$route_class = $route_controller;
					$route_object = new $route_controller($plugin);
				} else {
					$route_class = get_class($route_controller);
					$route_object = $route_controller;
				}

				/* if (is_string($route_class)) {
					$route_object = $route_class::instance();
				} else {
					$route_object = $route_class;
				} */

				if (!method_exists($route_object, $route_method)) {
					throw new Exception(debug::_('APP_ROUTE_METHOD_NOT_EXIST', $route_class, $route_method), E_WARNING);
				}
				// $route_object->$route_method($arguments);

				$this->initProfiler();
				// event beforeAppRender

				$this->content = call_user_func_array([$route_object, $route_method], $arguments);
				if ($this->content === null) {
					trigger_error(debug::_('APP_ROUTE_METHOD_RETURN_MISSING', $route_class, $route_method), E_USER_WARNING);
					$this->content = '';
				}
				// $this-> = [$route_object, $route_method, $arguments];
			} else {
				// throw new Exception(debug::_('APP_INIT_ROUTE_PAGE_NOT_FOUND', $uri), E_WARNING);

				$page404_layout = $this->page404['layout'] ?? '';
				$template404 = $this->path.'/layouts/'.($this->page404['template'] ?? 'page404').'.php';

				if (!is_file($template404)) {
					$template404 = 'core/layouts/page404.php';
					$page404_layout = '';
				}

				if ($page404_layout) {
					$this->initProfiler();
					// event beforeAppRender
				}

				ob_start();
				include($template404);
				$this->content = ob_get_clean();

				$this->layout = $page404_layout;
			}
		}
	}

	// todo: move this func to app daemon
	public function dispatch($socket, $msg) {
		$msg = json_decode($msg);
		// $socket;

		// $msg->channel;
		// $msg->event;
		// $data = $msg->data;

		// session::setId($msg->sessionId);

		$this->logger->start();

		$userSession = session::getInstance($msg->sessionId);

		if ($msg->channel === 'test') {
			$session = $userSession->channel('global');
			if ($msg->event === 'store.session') {
				// $_SESSION['a'] = $msg->data;
				$session->a = $msg->data;
			} else if ($msg->event === 'get.session') {
				// $msg->data;
				$socket->send(json_encode([
					'channel' => $msg->channel,
					'event' => 'updates.session',
					'data' => [
						'session' => $session->a, // $_SESSION,
						'session_id' => $userSession->getId() // session_id()
					]
				], JSON_UNESCAPED_UNICODE));
			}
		}

		ob_start();
		$this->logger->dumpLog();
		$log = ob_get_clean();

		$this->logger->stop();

		$socket->send(json_encode([
			'channel' => 'debug',
			'event' => 'updates.messages',
			'data' => $log
		], JSON_UNESCAPED_UNICODE));


		/* $data = $msg->data;
		$data->text = 'hello '.$data->text;
		$connection->send(json_encode([
			'channel' => $msg->channel,
			'event' => $msg->event,
			'data' => $data
		], JSON_UNESCAPED_UNICODE)); */
	}

	public function redirect($uri) {
		/* if (is_ajax) {
			trigger_error(debug::_('APP_REDIRECT_NOT_ALLOWED_IN_AJAX'), E_WARNING);
			return;
		} */
		if ($this->debug) {
			debug::addLog([
				'type' => 'console',
				'typeLabel' => 'redirect',
				'message' => 'redirecting to "'.$uri.'"',
				'file' => '',
				'line' => ''
			]);
			debug::saveLog();
		}
		header('Location: '.$uri); // todo: header status for redirect
		exit;
	}

	public function run($params) {
		$this->init($params);
		if (!$this->daemon) {
			echo $this->render();
		}
	}

	public function render() {
		if (!$this->layout) {
			$this->httpHead();
			return $this->content();
		}

		// event beforeLayoutRender
		$layout_path = $this->path.'/layouts/'.$this->layout.'.php';
		if (!is_file($layout_path)) {
			throw new Exception( debug::_('APP_RENDER_LAYOUT_FILE_NOT_FOUND', $layout_path), E_WARNING);
		}
		ob_start();
		$this->httpHead();
		include($layout_path);
		return ob_get_clean();
	}

}