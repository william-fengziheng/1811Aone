<?php
namespace app\admin\controller;

class Index
{
    public function index()
    {
        return view();
    }

    public function SeleteCate(){
        $id = input('id',0);
        $cate = \app\admin\model\Category::where('pid',$id)->select();
        
        return json($cate);
    }
}
