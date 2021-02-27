<?php

namespace app\common\model;


use think\Model;

class Spec extends Model
{
    public function specValues(){
        return $this->hasMany('SpecValue','spec_id');
    }
}
