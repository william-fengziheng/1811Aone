<?php
namespace app\adminapi\controller;

use think\Request;

use \think\Db;

class Goods extends BaseApi
{
    //渲染到展示页面
    public function exam()
    {
        return view('/exam');
    }

    public function selectCate()
    {
        //接收ID  如果没有 默认是0
        $id = input('id', 0);

        $cate = \app\common\model\Category::where('pid', $id)->select();

        dump($cate);
        die;

    }

    public function index()
    {
        //接收参数  keyword  page（自动处理）
        $params = input();
        $where = [];
        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $where['goods_name'] = ['like', "%$keyword%"];
        }

        //分页搜索
        $list = \app\common\model\Goods::with('category,brand,type')
            ->where($where)
            ->order('id desc')
            ->paginate(10);
        //返回数据
        $this->ok($list);


    }

    public function read($id)
    {
        //查询数据
        $info = \app\common\model\Goods::with('category_row,brand_row,goods_images,spec_goods')->find($id);
        //按照接口要求，改属性名
        $info['category'] = $info['category_row'];
        unset($info['category_row']);
        $info['brand'] = $info['brand_row'];
        unset($info['brand_row']);

        //商品所属模型信息
        $type = \app\common\model\Type::with('spec,specs.spec_values,attrs')->find($info['type_id']);
        $info['type'] = $type;
        $this->ok($info);
    }

    public function save(Request $request)
    {
        //接收参数
        $params = input();
        //参数数组参考；
        //参数检测
        $validate = $this->validate($params, [
            'goods_name|商品名称' => 'require',
            'goods_price|商品价格' => 'require|float|get:0',

            //省略无数字段检测
            'goods_logo|商品logo' => 'require',
            'goods_images|相册图片' => 'require|array',
            'attr|商品属性值' => 'require|array',
            'item|规格商品Sku' => 'require|array'
        ], [
            'goods_price.float' => '商品价格必须是小数或者整数'
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }

        // 启动事务
        Db::startTrans();
        try {
            //添加商品基本信息
            if (is_file('.' . $params['goods_logo'])) {
                //商品logo图片 生成略缩图
                $goods_logo = dirname($params['goods_logo']) . DS . 'thumb_' . basename($params['goods_logo']);
                \think\Image::open('.', $params['goods_logo'])->thumb(210, 240)->save('.' . $goods_logo);
                $params['goods_logo'] = $goods_logo;
            }
            //商品属性  生成json字符串
            $params['goods_attr'] = json_encode($params['attr'], JSON_UNESCAPED_UNICODE);
            $goods = \app\common\model\Goods::create($params, true);

            //批量添加商品相册图片

            $goods_images = [];

            foreach ($params['goods_images'] as $image) {
                //生成两张不同尺寸的略缩图  800*800  400*400
                if (is_file('.' . $image)) {
                    //定义  两张略缩图路径
                    $pics_big = dirname($image) . DS . "thumb_800_" . basename($image);
                    $pics_sma = dirname($image) . DS . "thumb_400_" . basename($image);

                    $image_obj = \think\Image::open('.', $image);
                    $image_obj->thumb(800, 800)->save('.' . $pics_big);
                    $image_obj->thumb(400, 400)->save('.' . $pics_sma);

                    //组装一条数据
                    $row = [
                        'goods_id' => $goods['id'],
                        'pics_big' => $pics_big,
                        'pics_sma' => $pics_sma,
                    ];
                    $goods_images[] = $row;
                }
            }
            // $goods_images_model = new \app\common\model\GoodsImages();
            // $goods_images_model->saveAll($goods_images);

            //批量添加规格商品 SKU
            $spec_goods = [];
            foreach ($params['item'] as $v) {
                $v['goods_id'] = $goods['id'];
                $spec_goods[] = $v;
            }
            // $spec_goods_model = new \app\common\model\SpecGoods();
            // $spec_goods_model ->allowField(true)->saveAll($spec_goods);

            //提交事务
            Db::commit();

            //返回数据
            $info = \app\common\model\Goods::with('category,brand,type')->find($goods['id']);
            $this->ok($info);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->fail('操作失败');
        }

    }

    public function delete($id)
    {
        $goods = \app\common\model\Goods::find($id);
        //
        if (empty($goods)) {
            $this->fail('数据异常，商品已经不存在');
        }

        if ($goods['is_on_sale']) {
            //上架中 ，无法删除
            $this->fail('上架中，无法删除');
        }
        //删除
        $goods->delete();

        $this->ok();
    }

    public function edit($id)
    {
        //查询商品基本信息（关联模型查询）
        //嵌套关联太多，只能写一个 category_row,brands type_row.specs type_ro.attrs tyoe_row.specs.spec_values
        $goods = \app\common\model\Goods::with('category,category_row.brands,brand_row,goods_images,spec_goods')->find($id);

        $goods['category'] = $goods['category_row'];
        $goods['brand'] = $goods['brand_row'];
        unset($goods['category_row']);
        unset($goods['brand_row']);

        //单独查询所属模型及规格属性等信息
        $goods['type'] = \app\common\model\Type::whih('specs,specs.spec_values,attrs')->find($goods['type_id']);

        //查询分类信息(所有一级，所属一级的二级，所属二级的三级)
        $cate_one = \app\common\model\Category::where('pid',0)->select();
        //从产品所属的三级分类的pid_path中，去除所属的二级id和一级id
        $pid_path = explode('_',$goods['category']['pid_path']);

        //$pid_path[1] 一级id；$pid_path[2] 二级id
        //查询所属一级的所有二级
        $cate_two = \app\common\model\Category::where('pid',$pid_path[1])->select();
        //查询所属二级的所有三级
        $cate_three = \app\common\model\Category::where('pid',$pid_path[2])->select();

        //查询所有的类型信息
        $type = \app\common\model\Type::select();

        //返回数据
        $data = [
            'goods'=>$goods,
            'category'=>[
                'cate_one'=>$cate_one,
                'cate_two'=>$cate_two,
                'cate_three'=>$cate_three,
            ],
            'type'=>$type
        ];
        $this->ok($data);
    }
}
