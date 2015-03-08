<?php namespace LaravelReactPHP;

class Server {

	/**
	 * @var string
	 */
	protected $host;

	/**
	 * @var int
	 */
	protected $port;

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

	/**
	 * Running HTTP Server
	 */
	public function run()
	{
		$loop = new \React\EventLoop\StreamSelectLoop();
		$socket = new \React\Socket\Server($loop);
		$http = new \React\Http\Server($socket, $loop);
		$http->on('request', function ($request, $response) {
			with(new HttpSession($this->host, $this->port))->handle($request, $response);
		});
		$socket->listen($this->port, $this->host);
		$loop->run();
	}
}
