<?php
/**
 * Created by PhpStorm.
 * User: linzhen
 * Date: 2018/11/23
 * Time: 19:41
 */

namespace app\index\controller;


class Word
{
    public function daily()
    {
        $count = input('get.id') ? : 5;

        $words = \app\index\model\Word::all(function($query) use ($count) {
            $query->where('learn_times', 0)->limit($count);
        });
        foreach($words as $word) {
            $word['explains'] = json_decode($word['explains'], true);
        }
        return $words;
    }
}