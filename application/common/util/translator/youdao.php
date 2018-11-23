<?php
/**
 * Created by PhpStorm.
 * User: linzhen
 * Date: 2018/11/20
 * Time: 13:37
 */

namespace app\common\util\translator;

use app\common\util\curl;

class youdao
{
    private $url = '';
    private $key = '';
    private $secret = '';
    public function __construct($config = [])
    {
        $this->url = isset($config['url']) ? : 'http://openapi.youdao.com/api';
        $this->key = isset($config['key']) ? : '301a2e7a0fd82cd4';
        $this->secret = isset($config['secret']) ? : 'akB4Zhd9HAY0P5LbZkLaDUcJn7hQ52zo';
    }

    public function translate($query, $from = 'EN', $to = 'zh-CHS')
    {
        $args = [
            'q' => $query,
            'appKey' => $this->key,
            'salt' => rand(10000,99999),
            'from' => $from,
            'to' => $to
        ];
        $args['sign'] = $this->buildSign($query, $args['salt']);
        $ret = $this->requestApi($args);

        return $ret;
    }

    private function buildSign($query, $salt)
    {
        $str = $this->key . $query . $salt . $this->secret;
        return md5($str);
    }

    private function requestApi($args)
    {
        $curl = Curl::init($this->url);
        $res = $curl->option([
            'httpheader' => [
                'Content-type:application/x-www-form-urlencoded'
            ],
            'postfields' => http_build_query($args),
        ])->run();

        return json_decode($res, true);
    }
}