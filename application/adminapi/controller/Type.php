<?php

namespace app\adminapi\controller;

use app\adminapi\controller\BaseApi;
use think\Db;
use think\Request;

class Type extends BaseApi
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $list = \app\common\model\Type::select();
        $this->ok($list);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //接收参数
        $params = input();

        //参数检测
        $validate = $this->validate($params, [
            "type_name|模型名称" => "require|max:20",
            'spec|规格' => "require|array",
            'attr|属性' => "require|array",
        ]);

        if ($validate !== true) {
            $this->fail($validate);
        }

        //开启事务
        \think\Db::startTrans();
        try {
            $type = \app\common\model\Type::create($params, true);
            //添加商品规格名
            //去除空的规格值  去除没有值的规格名
            //参数数组参考：
            /* $params = [
                 'type_name' => '手机',
                 'spec' => [
                     ['name' => '颜色', 'sort' => 50, 'value'=>['黑色', '白色', '金色']],
                     ['name' => '内存', 'sort' => 50, 'value'=>['64G', '128G', '256G']],
                 ],
                 'attr' => [
                     ['name' => '毛重', 'sort'=>50, 'value' => []],
                     ['name' => '产地', 'sort'=>50, 'value' => ['进口', '国产']],
                 ]
             ];*/
            //外层便利规格名

            foreach ($params['spec'] as $i => &$spec) {
                if (trim($spec['name']) == "") {
                    unset($params['spec'][$i]);
                } else {
                    //内存 遍历规格值
                    foreach ($spec['value'] as $k => $value) {
                        //$value 是一个规格值去除空的值
                        if (trim($value) == "") {
                            unset($params['spec'][$i]['value'][$k]);
                        }
                    }
                    //内层foreach结束，判断当前的规格名的规则值是不是空数组
                    if (empty($params['spec'][$i]['value'])) {
                        unset($params['spec'][$i]);
                    }
                    //遍历组装，数据表需要的数据
                    $spec = [];
                    foreach ($params['spec'] as $spec) {
                        $row = [
                            'type_id' => $type['id'],
                            'spec_name' => $spec['name'],
                            'sort' => $spec['sort'],
                        ];
                        $specs[] = $row;
                    }
                    //批量添加规格名称
                    $spec_model = new \app\common\model\common\Spec();
                    //saveAll如果要过滤非数据表字段，需要哦调用allowField方法
                    $spec_data = $spec_model->allowField(true)->saveAll($specs);

                    //添加商品规格值
                    $spec_values = [];

                    //外层遍历规格名称
                    foreach ($params['spec'] as $i => $spec) {
                        //内层遍历规格值
                        foreach ($spec['value'] as $value) {
                            $row = [
                                'spec_id' => $spec_data[$i]['id'],
                                'spec_value' => $value,
                                'type_id' => $type['id']
                            ];
                            $spec_values[] = $row;
                        }
                    }
                    //批量添加规格值
                    $spec_value_model = new \app\common\model\common\SpecValue();
                    $spec_value_model->saveAll($spec_values);
                    //添加商品属性

                    //去除空的属性名和空的属性值
                    //外层遍历属性名
                    foreach ($params['attr'] as $i => &$attr) {
                        if (trim($attr['name']) == "") {
                            unset($params['attr'][$i]);
                        } else {
                            //内层遍历属性值
                            foreach ($attr['value'] as $k => $value) {
                                if (trim($value) == "") {
                                    unset($params ['attr'][$i]['value'][$k]);
                                }
                            }
                        }
                    }
                    unset($attr);
                    //批量添加属性名称属性值
                    $attrs = [];
                    foreach ($params['attr'] as $attr) {
                        $row = [
                            "attr_name" => $attr['name'],
                            'attr_values' => implode(",", $attr['value']),
                            'sort' => $attr['sort'],
                            'type_id' => $type['id'],
                        ];
                        $attrs[] = $row;
                    }

                    //批量添加
                    $attr_model = new \app\common\model\Attribute();
                    $attr_model->saveAll($attrs);

                    //提交事务
                    Db::commit();

                    //返回数据
                    $type = \app\common\model\Type::find($type['id']);
                    $this->ok($type);
                }
            }
            //提交事务
            Db::commit();

            //返回数据
            $this->ok();
        } catch (\Exception $e) {

            // 回滚事务
            Db::rollback();
            $this->fail('添加失败');
        }
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    
    public function read($id)
    {
        $info = \app\common\model\Type::with('specs,specs.spec_values', 'arrts')->find($id);
        $this->ok($info);
    }


    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */

    public function update(Request $request, $id)
    {
        $params = input();
        $validate = $this->validate($params, [
            "type_name|模型名称" => 'require|max:20',
            "spec|规格" => "require|array",
            "attr|属性" => "require|array"
        ]);
        if ($validate != true) {
            $this->fail($validate);
        }

        \think\Db::startTrans();

        Db::startTrans();
        try {
            \app\common\model\Type::update(['type_name' => $params['type_name']], ['id' => $id], true);
            // 提交事务

            //外层遍历规格名称
            foreach ($params['spec'] as $i => $spec) {
                if (trim($spec['name']) == "") {
                    unset($params['spec'][$i]);
                    continue;
                } else {
                    //内存遍历规格值
                    foreach ($spec['value'] as $k => $value) {
                        //$value 就是一个规格值
                        if (trim($value) == '') {
                            unset ($params['spec'][$i]['value'][$k]);
                        }
                    }

                    //判断规格值数组，是否为空数组
                    if (empty($params['spec'][$i]['value'])) {
                        unset($params['spec'][$i]);
                    }

                    //批量删除原来的规格名  删除条件 类型 type_Id
                    \app\common\model\common\Spec::destroy(['type_id' => $id]);
                    $specs = [];
                    foreach ($params['spec'] as $i => $spec) {
                        $row = [
                            'spec_name' => $spec['name'],
                            'sort' => $spec['sort'],
                            'type+id' => $id
                        ];
                        $specs[] = $row;
                    }
                    $spec_model = new \app\common\model\Spec();
                    $spec_data = $spec_model->saveAll($specs);

                    //批量删除原来的规格值
                    \app\common\model\SpecValue::destroy(['type_id' => $id]);
                    $spec_values = [];
                    foreach ($params['spec'] as $i => $spec) {
                        foreach ($spec['value'] as $value) {
                            $row = [
                                'spec_id' => $spec_data[$i]['id'],
                                'type_id' => $id,
                                'spec_value' => $value
                            ];
                            $spec_values[] = $row;
                        }
                    }
                    $spec_value_model = new \app\common\model\SpecValue();
                    $spec_value_model->saveAll($spec_values);

                    //去除空的属性值
                    foreach ($params['attr'] as $i => $attr) {
                        if (trim($attr['name']) == "") {
                            unset($params['attr'][$i]);
                            continue;
                        } else {
                            foreach ($attr['value'] as $k => $value) {
                                if (trim($value) == "") {
                                    unset($params['attr'][$i]['value'][$k]);
                                }

                            }
                        }
                    }

                    //批量删除原来的属性
                    \app\common\model\Attribute::destroy(['type_id' => $id]);
                    //批量添加新的属性
                    $attrs = [];
                    foreach ($params['attr'] as $i => $attr) {
                        $row = [
                            'type_id' => $id,
                            'attr_name' => $attr['name'],
                            'attr_values' => implode(",", $attr['value']),
                            'sort' => $attr['sort']
                        ];
                        $attrs[] = $row;
                    }
                    $attr_model = new \app\common\model\Attribute();
                    $attr_model->saveAll($attrs);
                    //提交事务
                    Db::commit();
                    //返回数据
                    $this->fail('操作失败');
                }
            }
            Db::commit();
            $this->ok();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->fail('操作失败');
        }

    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    //普通删除
    // public function delete($id)
    // {
    //     $goods = \app\common\model\common\Goods::where('type_id',$id)->find();
    //     if($goods){
    //         $this->fail('正在使用中,不能删除');
    //     }   
    //     //删除数据 （商品类型、类型下的规格名，类型下的规格值、类型下的属性）
    //     \app\common\model\Type::destroy($id);
    //     \app\common\model\common\Spec::destroy(['type_id',$id]);

    //     \app\common\model\common\SpecValue::destroy(['type_id',$id]);
    //     \app\common\model\common\Attribute::destroy(['type_id',$id]);
    //     //返回数据
    //     $this->ok();
    // }

    //事务删除
    public function delete($id)
    {

        //判断是否有商品在使用该商品类型
        $goods = \app\common\model\Goods::where('type_id', $id)->find();

        if ($goods) {
            $this->fail('正在使用中不能删除');
        }
        //开启事务
        Db::startTrans();

        try {

            //删除数据（商品类型、类型下的规格名称、类型下的规格值、类型下的属性）
            \app\common\model\Type::destroy($id);
            \app\common\model\Spec::destroy(['type_id', $id]);
            \app\common\model\SpecValue::destroy(['type_id', $id]);
            \app\common\model\Attribute::destroy(['type_id', $id]);

            //提交事务
            Db::commit();

            //返回数据
            $this->ok();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->fail('删除失败');
        }
    }
}
