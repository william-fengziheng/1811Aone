<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Validate;

class Login extends Controller
{
    public function login()
    {
        return view('/login');
    }
    public function login_do()
    {
        if(!$_GET){
            return json([
                'code' => "3",
				'msg' => "请求类型不正确",
				'data' => ""
            ]);
        }
        $arr['name'] =  input('name');
        $arr['pwd'] = input('pwd');

        $validate = Validate::make([
            'name' => 'require|max:25',
            'pwd' => 'require|max:12|min:6'
        ]);
        $data = [
            'name' => input('name'),
            'pwd' => input('pwd'),
        ];
        if (!$validate->check($data)) {
            dump($validate->getError());
            return  Json([
				'code' => "4",
				'msg' => "数据格式不正确",
				'data' => ""
			]);
        }
        


    }
}
