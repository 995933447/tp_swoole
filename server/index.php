<?php 
class Http {
	private $server;
	public function __construct($host = null, $port = null)
	{
		header('Access-Control-Allow-Origin:*');
		$config = require "../config/swoole.php";
		$host = $host ? $host : $config['host'];
		$port = $port ? $port : $config['port'];
		$this->server = new swoole_websocket_server($host, $port);
		$this->server->listen($host, $config['chat_port'], SWOOLE_SOCK_TCP);
		$this->server->set([
			'worker_num' => 4,
			'reactor_num' => 2,
			'backlog' => 128,
			'document_root' => __DIR__.'/../template/',
			'enable_static_handler' => true,
			'task_worker_num' => 4
		]);
		$this->server->on('workerStart', [$this, 'workerStart']);
		$this->server->on('open', [$this, 'open']);
		$this->server->on('message', [$this, 'message']);	
		$this->server->on('request', [$this, 'request']);
		$this->server->on('task', [$this, 'task']);
		$this->server->on('finish', [$this, 'finish']);
		$this->server->on('close', [$this, 'close']);
		$this->server->on('start', [$this, 'start']);
	}

	public function workerStart($server,$worker_id)
	{
		require __DIR__ . '/../thinkphp/base.php';
	}

	public function open($server, $request)
	{
	   \extend\lib\Goredis::instance()->sadd($request->server['server_port'], $request->fd);
	}

	public function close($server, $fd, $reactor)
	{
		\extend\lib\Goredis::instance()->srem('9501', $fd);
	}

	public function message()
	{

	}

	public function request($request, $responce)
	{
		if(isset($request->server) && $request->server) {
			foreach ($request->server as $key => $value) {
				$_SERVER[strtoupper($key)] = $value;
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
		$_FILES = [];
		if(isset($request->files) && $request->files) {
			foreach ($request->files as $key => $value) {
				$_FILES[$key] = $value;
			}
		}
		$_SERVER['http_swoole_server'] = $this->server;
		$logdata = [
			'time' => date('Y-m-d H:i:s'),
			'$_GET' => $_GET,
			'$_POST' => $_POST,
			'$_SERVER' => $_SERVER,
			'$_FILES' => $_FILES
		];		
		// $logdata = json_encode($logdata) . PHP_EOL;
		$logdata = print_r($logdata, true);
		$logdata ="{$logdata}";
		$dir = '../runtime/log/request_log/' . date('Y-m') . '/' . date('d');
		is_dir($dir) || mkdir($dir,0777,true);
		swoole_async_writefile($dir . '/' . $request->fd . '.log', $logdata, function() use($logdata) {
			// echo "ok!";
			//var_dump($logdata);
		}, FILE_APPEND);
		ob_start();
		try{
		   \think\Container::get('app')->run()->send();
		} catch(\Exception $e){
			echo $e;
		}
		$content = ob_get_contents();
		if($content) {
			ob_end_clean();
		}
		$responce->header('content-type', 'text/html;charset=utf-8');
		$responce->end($content);
	}

	public function task($server,$task_id,$worker_id,$data)
	{
		$class = $data['class'];
		$method = $data['method'];
		$result = $class->$method($data['data'], $server);
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
		$this->server->start();
	}

	public function start() 
	{
		swoole_set_process_name("swoole_server");
	}

}

(new Http())->run();

 ?>
