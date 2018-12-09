<?php 
swoole_timer_tick(2000,function($timer_id){
	is_dir('../runtime/log/server_monitor/') || mkdir('../runtime/log/server_monitor/');
	$res = shell_exec('netstat -anp 2>/dev/null | grep 9501 | grep LISTEN | wc -l');
	if(!intval($res)) {
		// echo 'fail';
		// file_put_contents('../runtime/log/server_monitor/' . date('Y_m_d_H_i_s').'.log', '[' .date('Y-m-d H:i:s') . '] SERVER STOP' . PHP_EOL);
		swoole_async_writefile('../runtime/log/server_monitor/' . date('Y_m_d').'.log', '[' .date('Y-m-d H:i:s') . '] SERVER STOP' . PHP_EOL, function(){

		}, FILE_APPEND);
	} else {
		// var_dump($res);
	}
});