<?php
namespace app\index\controller;
use think\Controller;
use think\Validate;
use extend\lib\Unity;

class Admin extends Controller
{
    public function upload()
    {
        $file = request()->file("file");
        is_dir('../template/uploads') || mkdir('../template/uploads');
        $dir = '../template/uploads';
        if($file){
                $info = $file->move($dir);
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    // echo $info->getExtension();
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    // echo $info->getSaveName();
                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    // echo $info->getFilename(); 
                    $path = 'uploads/' . $info->getSaveName();
                    return Unity::success($path);
                }else{
                    // 上传失败获取错误信息
                    return Unity::error($file->getError());
                }
        }  
    }

    public function live()
    {
        $fds = \extend\lib\Redis::instance()->smembers($_SERVER['SERVER_PORT']);
        $data = request()->post();
        $data['time'] = date('Y-m-d H:i:s');
        if($fds) {
            $task = [
                'class' => new Unity(),
                'method' => 'pushLive',
                'data' => [
                    'fds' => $fds,
                    'data' => json_encode($data),
                ]
            ];
            $_SERVER['http_swoole_server']->task($task);
        }
        $db = new \swoole_mysql();
        $server = array(
            'host' => config('database.hostname'),
            'port' => config('database.hostport'),
            'user' => config('database.username'),
            'password' => config('database.password'),
            'database' => config('database.database'),
            'charset' => config('database.charset'), //指定字符集
            'timeout' => 2,  // 可选：连接超时时间（非查询超时时间），默认为SW_MYSQL_CONNECT_TIMEOUT（1.0）
        );

        $db->connect($server, function ($db, $r) {
            if ($r) {
                $sql = "insert into live_outs (type,team_id,content,image) values('"  . request()->post('type') . "','"  . request()->post('team_id') . "','"  . request()->post('content') . "','"  . json_encode(request()->post('imgs')) . "')"; 
                $db->query($sql, function(\swoole_mysql $db, $r) {
                    if($r) {
                        $db->close();    
                    } else {
                        var_dump($db->error, $db->errno);                    }
                });
            }  else {
               var_dump($db->error, $db->errno);
            }     
        });
        return Unity::success();
    }



    
}
