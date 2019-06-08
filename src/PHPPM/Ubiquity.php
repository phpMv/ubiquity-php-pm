<?php
namespace PHPPM;

use PHPPM\Bridges\BridgeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ubiquity\utils\http\foundation\Psr7;
use Ubiquity\utils\http\foundation\ReactHttp;
use React\Http\Response;

/**
 * PHP-PM Http bootstrap for Ubiquity.
 * PHPPM$Ubiquity
 * This class is part of Ubiquity
 *
 * @author jcheron <myaddressmail@gmail.com>
 * @version 1.0.0
 *         
 */
class Ubiquity implements BridgeInterface {

	/**
	 *
	 * @var string root path
	 */
	protected $root;

	protected $config;

	/**
	 *
	 * @var ReactHttp
	 */
	protected $httpInstance;

	public function __construct() {
		$this->root = dirname(__DIR__, 5);
		$this->httpInstance = new ReactHttp();
	}

	public function handle(ServerRequestInterface $request): ResponseInterface {
		gc_collect_cycles();
		$uri = ltrim(urldecode(parse_url($request->getUri()->getPath(), PHP_URL_PATH)), '/');
		if ($uri == null || ! file_exists($this->root . \DS . $uri)) {
			$_GET['c'] = $uri;
		} else {
			$_GET['c'] = '';
			$headers = $request->getHeaders();
			$headers['Content-Type'] = current($headers['Accept']);
			return new \React\Http\Response(200, $headers, file_get_contents($this->root . \DS . $uri));
		}
		$headers = $request->getHeaders();
		$response = new Response(200, [
			'Content-Type' => current($headers['Accept'])
		]);
		$this->httpInstance->setRequest($request, $response);
		Psr7::requestToGlobal($request);

		\ob_start();
		\Ubiquity\controllers\Startup::setHttpInstance($this->httpInstance);
		\Ubiquity\controllers\Startup::run($this->config);
		$content = ob_get_clean();
		return $response->withBody($content);
	}

	public function bootstrap($appBootstrap, $appenv, $debug) {
		if (! defined('DS')) {
			define('DS', DIRECTORY_SEPARATOR);
			define('ROOT', $this->root . \DS . 'app' . \DS);
		}
		$config = include ROOT . 'config/config.php';
		$sConfig = include $this->root . \DS . '.ubiquity' . \DS . 'react-config.php';
		$config["sessionName"] = $sConfig["sessionName"] ?? null;
		$address = ($sConfig['host'] ?? '127.0.0.1') . ':' . ($sConfig['port'] ?? 8080);
		$config["siteUrl"] = 'http://' . $address;
		require $this->root . '/vendor/autoload.php';
		require ROOT . 'config/services.php';
		$this->config = $config;
	}
}
