<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Request;

class BaseApi extends Controller
{
    /**
     * 通用的响应
     * @param int $code 错误码
     * @param string $msg 错误信息
     * @param array $data 返回数据
     */
    protected function response($code=200, $msg='success', $data=[])
    {
        $res = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
        //原生php写法
        echo json_encode($res, JSON_UNESCAPED_UNICODE);die;
        //框架写法
        //json($res)->send();

    }
    /**
     * 成功的响应
     * @param array $data 返回数据
     * @param int $code 错误码
     * @param string $msg 错误信息
     */
    protected function ok($data=[], $code=200, $msg='success')
    {
        $this->response($code, $msg, $data);
    }

    /**
     * 失败的响应
     * @param $msg 错误信息
     * @param int $code 错误码
     * @param array $data 返回数据
     */
    protected function fail($msg, $code=500, $data=[])
    {
        $this->response($code, $msg, $data);
    }
  
}
