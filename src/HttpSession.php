<?php namespace LaravelReactPHP;

class HttpSession {

	/**
	 * @var string
	 */
	protected $host;

	/**
	 * @var int
	 */
	protected $port;

	/**
	 * @var string
	 */
	protected $request_body;

	/**
	 * @var array
	 */
	protected $post_params;

	/**
	 *
	 *
	 * @param string $host binding host
	 * @param int $port binding port
	 */
	public function __construct($host, $port)
	{
		$this->host = $host;
		$this->port = $port;
	}

	protected function getRequestUri(array $headers, $path)
	{
		$protocol = "http://";
		if (isset($headers['HTTPS'])) {
			$protocol = "https://";
		}
		$http_host = $protocol.$this->host;
		if (isset($headers['Host'])) {
			$http_host = $protocol.$headers['Host'];
		}

		return $http_host.$path;
	}

	protected function getCookies(array $headers)
	{
		$cookies = [];
		if (isset($headers['Cookie'])) {
			if (function_exists('http_parse_cookie')) {
				$cookie_data = http_parse_cookie($headers['Cookie']);
				if ($cookie_data) {
					$cookies = $cookie_data->cookies;
				}
			} else if (class_exists("\Guzzle\Parser\Cookie\CookieParser")) {
				$cookies = array_get(with(new \Guzzle\Parser\Cookie\CookieParser())->parseCookie($headers['Cookie']), 'cookies', []);
			} else if (class_exists("\GuzzleHttp\Cookie\SetCookie")) {
				foreach(\GuzzleHttp\Cookie\SetCookie::fromString($headers['Cookie'])->toArray() as $data) {
					$cookies[$data['Name']] = $data['Value'];
				}
			}
		}

		return $cookies;
	}

	protected function buildCookies(array $cookies)
	{
		$headers = [];
		foreach ($cookies as $cookie) {
			if (!isset($headers['Set-Cookie'])) {
				$headers['Set-Cookie'] = [];
			}
			$cookie_value = sprintf("%s=%s", rawurlencode($cookie->getName()), rawurlencode($cookie->getValue()));
			if ($cookie->getDomain()) {
				$cookie_value .= sprintf("; Domain=%s", $cookie->getDomain());
			}
			if ($cookie->getExpiresTime()) {
				$cookie_value .= sprintf("; Max-Age=%s", $cookie->getExpiresTime());
			}
			if ($cookie->getPath()) {
				$cookie_value .= sprintf("; Path=%s", $cookie->getPath());
			}
			if ($cookie->isSecure()) {
				$cookie_value .= "; Secure";
			}
			if ($cookie->isHttpOnly()) {
				$cookie_value .= "; HttpOnly";
			}
			$headers['Set-Cookie'][] = $cookie_value;
		}

		return $headers;
	}

	protected function handleRequest(\React\Http\Request $request, \React\Http\Response $response)
	{
		$kernel = \App::make('Illuminate\Contracts\Http\Kernel');
		$laravel_request = \Request::create(
			$this->getRequestUri($request->getHeaders(), $request->getPath()),
			$request->getMethod(),
			array_merge($request->getQuery(), $this->post_params),
			$this->getCookies($request->getHeaders()),
			[],
			[],
			$this->request_body
		);
		$laravel_response = $kernel->handle($laravel_request);
		$headers = array_merge($laravel_response->headers->allPreserveCase(), $this->buildCookies($laravel_response->headers->getCookies()));
		$response->writeHead($laravel_response->getStatusCode(), $headers);
		$response->end($laravel_response->getContent());

		$kernel->terminate($laravel_request, $laravel_response);
	}

	public function handle(\React\Http\Request $request, \React\Http\Response $response)
	{
		$this->post_params = [];
		$request->on('data', function($body) use($request, $response) {
			$this->request_body = $body;
			parse_str($body, $this->post_params);

			$this->handleRequest($request, $response);
		});
	}
}
