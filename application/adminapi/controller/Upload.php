<?php

namespace app\adminapi\controller;

use app\adminapi\controller\BaseApi;


class Upload extends BaseApi
{
    //单图片上传
    public function logo()
    {
        $type = input("type");
        dump($type);die;
        if (empty($type)) {
            $this->fail('缺少参数');
        }

        //获取文件
        $file = request()->file('logo');
        if (empty($file)) {
            $this->fail('必须上传文件');
        }

        //DS	使用PHP自带DIRECTORY_SEPARATOR
        //ROOT_PATH   	Env::get('root_path')  引用think\facade\Env

        //图片移动/public/uploads/category
        $info = $file->validate(['size' => 10*1024*1024, 'ext' => 'jpg,jpeg,png,gif'])->move( ROOT_PATH."public".DS."uploads".DS.$type);

        if($info){
            $logo = DS."uploads".DS.$type.DS.$info->getSaveName();
            $this->ok($logo);
        }else{
            $msg = $file->getError();
            $this->fail($msg);
        }

    }
    public function images(){
        //接收type参数 图片分组
        $type = input('type','goods');

        //获取上传的文件（数组）

        $files = request()->file('images');

        //遍历数组逐个上传文件
        $data = ['success'=>[],'error'=>[] ];
        foreach($files as $file){
            //移动文件到指定目录下 /public/uploads/goods/目录下

            $dir = ROOT_PATH .'public' . DS .'uploads' .DS .$type;
            if(!is_dir($dir)){
                mkdir($dir);
            }
            $info = $file->validate(['size' => 10*1024*1024,'ext'=>'jpg,jpeg,png,gif'])->move($dir);
            if($info){
                //成功  拼接图片路径
                $path  =DS .'uploads'.DS.$type.DS.$info->getSaveName();
                $data['success'][] = $path;
            }else{
                //失败获取错误信息  getInfo() 获取文件原始信息  getError()获取错误信息
                $data['error'][]=[
                    'name' => $file->getInfo('name'),
                    'msg'=>$file->getError()
                ];
            }
        }
        $this->ok($data);
    }
}