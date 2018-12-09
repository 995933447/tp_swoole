<?php 
class Http {
	private $http;
	public function __construct($host = null, $port = null)
	{
		$config = require "../config/swoole.php";
		$host = $host ? $host : $config['host'];
		$port = $port ? $port : $config['port'];
		$this->http = new swoole_http_server($host, $port);
		$this->http->set([
			'worker_num' => 4,
			'reactor_num' => 2,
			'backlog' => 128,
			'document_root' => __DIR__.'/../template/',
			'enable_static_handler' => true,
			'task_worker_num' => 4
		]);
		$this->http->on('workerStart', [$this, 'workerStart']);
		$this->http->on('request', [$this, 'request']);
		$this->http->on('task', [$this, 'task']);
		$this->http->on('finish', [$this, 'finish']);
	}

	public function workerStart($server,$worker_id)
	{
		require __DIR__ . '/../thinkphp/base.php';
	}

	public function request($request, $responce)
	{
		if(isset($request->server) && $request->server) {
			foreach ($request->server as $key => $value) {
				$_SERVER[$key] = $value;
			}
		}
		$_GET = [];
		if(isset($request->get) && $request->get) {
		foreach ($request->get as $key => $value) {
				$_GET[$key] = $value;
			}	
		}
		$_POST = [];
		if(isset($request->post) && $request->post) {
			foreach ($request->post as $key => $value) {
				$_POST[$key] = $value;
			}
		}
		$_SERVER['http_swoole_server'] = $this->http;
		ob_start();
		try{
		   \think\Container::get('app')->run()->send();
		} catch(\Exception $e){
			echo $e;
		}
		$content = ob_get_contents();
		ob_end_clean();
		$responce->header('content-type', 'text/html;charset=utf-8');
		$responce->end($content);
	}

	public function task($server,$task_id,$worker_id,$data)
	{
		$class = $data['class'];
		$method = $data['method'];
		$result = $class->$method($data['data']);
		return $result;
	}

	public function finish($server,$task_id,$data)
	{
		if($data) {
			return $data['class']->$data['method']($data);
		}
	}

	public function run()
	{
		$this->http->start();
	}

}

(new Http())->run();

 ?>