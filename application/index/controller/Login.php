<?php
namespace app\index\controller;
use think\Controller;
use think\Validate;
use extend\lib\Unity;
use extend\lib\Sms;

class Login extends Controller
{
    public function index()
    {
    	$rule = [
    		'phone_num' => [
    			'require',
    			'regex' => '/^1[34578]\d{9}$/'
    		],
    	];
    	$message = [
    		'phone_num.require' => '请输入手机号码',
    		'phone_num.regex' => '手机格式不正确'
    	];
    	$validate = new Validate($rule,$message);
    	if(!$validate->check(request()->post())) {
    		return Unity::error($validate->getError());
    	}
    	$code = rand(1000, 9999);
    	$data = [
    		'class' => new Sms(),
    		'method' => 'send',
    		'data' => [
    			'phone_num' => request()->post('phone_num'),
    			'code' => $code
    		]
    	];
    	$_SERVER['http_swoole_server']->task($data);
	    $redis = new \swoole_redis();
	    $redis->connect(config('redis.host'), config('redis.port'), function($redis, $result) use($code) {
	    	if($result) {
	    		$redis->set(Sms::smskey(request()->post('phone_num')), $code, function(){
	    		});
                $redis->close();
	    	}
	    });
    	return Unity::success($code);
    }

    public function login()
    {
        $rule = [
            'phone_num' => 'require',
            'code' => 'require'
        ];
        $message = [
            'phone_num.require' => '手机号码不能为空',
            'code.require' => '请输入手机验证码'
        ]; 
        $validate = new Validate();
        if(!$validate->check(request()->post())) {
            return Unity::error($validate->getError());
        }
        $redis = new \Redis();
        $redis->connect(config('redis.host'), config('redis.port'));
        $code = $redis->get(Sms::smskey(request()->post('phone_num')));
        $redis->close();
        if(!$code || strcmp($code, request()->post('code')) !== 0) {
            return Unity::error('验证码不正确');
        }
        $token = md5(uniqid('', true));
        $redis = new \swoole_redis();
        $redis->connect(config('redis.host'), config('redis.port'), function($redis, $result) use($token) {
            if($result) {
              $redis->set(Unity::tokenKey(request()->post('phone_num')), $token,function(){
                
              });            
            }
        });
        return Unity::success($token);     
    }

    
}
