<?php
namespace PHPPM;

use PHPPM\Bridges\BridgeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ubiquity\utils\http\foundation\Psr7;
use Ubiquity\utils\http\foundation\ReactHttp;

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
		$dir = dirname(__DIR__, 6);
		$this->root = ($dir == DIRECTORY_SEPARATOR) ? '' : $dir;
		$this->httpInstance = new ReactHttp();
	}

	public function handle(ServerRequestInterface $request): ResponseInterface {
		$_GET['c'] = '';
		$uri = ltrim(urldecode(parse_url($request->getUri()->getPath(), PHP_URL_PATH)), '/');
		if ($uri == null || ! file_exists($this->root . \DS . $uri)) {
			$_GET['c'] = $uri;
		} else {
			$headers = $request->getHeaders();
			$headers['Content-Type'] = current($headers['Accept']);
			return new \React\Http\Response($this->httpInstance->getResponseCode(), $headers, file_get_contents($this->root . \DS . $uri));
		}

		$headers = $request->getHeaders();
		$headers['Content-Type'] = current($headers['Accept']);
		$this->httpInstance->setRequest($request);
		Psr7::requestToGlobal($request);

		\ob_start();
		\Ubiquity\controllers\Startup::setHttpInstance($this->httpInstance);
		\Ubiquity\controllers\Startup::run($this->config);
		$content = ob_get_clean();
		return new \React\Http\Response($this->httpInstance->getResponseCode(), $this->httpInstance->getAllHeaders(), $content);
	}

	public function bootstrap($appBootstrap, $appenv, $debug) {
		if (! defined('DS')) {
			define('DS', DIRECTORY_SEPARATOR);
			define('ROOT', $this->root . \DS . 'app' . \DS);
		}
		$config = include ROOT . 'config/config.php';
		$sConfig = include $this->root . \DS . '.ubiquity' . \DS . 'react-config.php';
		$config["sessionName"] = $sConfig["sessionName"];
		$address = $sConfig['host'] . ':' . $sConfig['port'];
		$config["siteUrl"] = 'http://' . $address;
		require $this->root . '/vendor/autoload.php';
		require ROOT . 'config/services.php';
		$this->config = $config;
	}
}
