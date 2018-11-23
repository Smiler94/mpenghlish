<?php
/**
 * Curl 请求类
 */
namespace app\common\util;

class Curl
{
    private $ch      = null;
    private $option  = array();
    private $log_dir = '';
    private $time    = 2;

    private $begin   = 0; // 开始请求时间
    private $end     = 0; // 结束请求时间
    //单例模式
    private static $instance = null;
    /**
     * 初始化
     *
     * @author 林祯 2017-01-23T10:54:28
     * @param  string $url 请求的链接
     * @return object      curl对象
     */
    public static function init($url)
    {
        if (self::$instance == null) {
            self::$instance = new self();
        } 
        self::$instance->option(array(
            'URL'            => $url,
            'POST'           => true,
            'TIMEOUT'        => 120,
            'RETURNTRANSFER' => true,
            'CONNECTTIMEOUT' => 30,
            'SSL_VERIFYPEER' => false,
        ));
        return self::$instance;
    }
    final protected function __clone(){ }
    /**
     * 构造函数
     *
     * @author 林祯 2017-01-23T10:55:12
     */
    final protected function __construct()
    {
        $this->ch = curl_init();
    }
    
    public function __call($func, $args)
    {
        if (function_exists('curl_'.$func)) {
            return call_user_func('curl_'.$func, $args);
        }
    }
    /**
     * 设置参数
     *
     * @author 林祯 2017-01-23T10:55:24+0800
     * @param  mix $option 参数名或者参数数组
     * @param  string $value 参数值
     * @return object      curl对象
     */
    public function option($option, $value = '')
    {
        if (is_array($option)) {
            foreach($option as $opt => $val) {
                $this->option[strtoupper($opt)] = $val;
            }
        } else {
            $this->option[strtoupper($option)] = $value;
        }
        return $this;
    }
    /**
     * 将参数转化为CURL的参数值
     *
     * @author 林祯 2017-01-23T10:56:51
     * @param  string $opt 参数简称||常量名||常量值 ex:设定url 可以为 'url'、'CURLOPT_URL'、CURLOPT_URL
     * @return 参数值
     */
    private function parseOpt($opt)
    {
        if (preg_match('/^\d+$/',$opt)) {
            return $opt;
        }
        $opt = 'CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($opt));
        if (!defined($opt)) {
            return false;
        }
        return constant($opt);
    }
    /**
     * 设定日志目录
     *
     * @author 林祯 2017-01-23T10:59:58
     * @param  string $dir 日志目录路径
     * @return object curl对象
     */
    public function logdir($dir)
    {
        if ($dir) {
            $this->log_dir = $dir;
        }
        return $this;
    }
    /**
     * 请求次数，即最多请求$time次直至请求成功
     *
     * @author 林祯 2017-01-23T11:01:14
     * @param  int $time 最多请求次数
     * @return object curl对象
     */
    public function time($time)
    {
        if (preg_match('/^\d+$/',$time) && $time > 0) {
            $this->time = $time;
        }
        return $this;
    }
    /**
     * 发起请求
     *
     * @author 林祯 2017-01-23T11:02:28
     * @return mix 请求结果
     */
    public function run()
    {
        $this->setopt();
        for($i = 1 ;$i <= $this->time ;$i++){
            $this->begin    = microtime(true);// 开始时间
            $this->response = $this->exec();// 执行请求
            $this->end      = microtime(true);// 结束时间
            if (!$this->errno()) {// 请求成功
                break;
            }
            if ($i == $this->time) {// 重新请求
//                $this->log();
//                return false;
            }
        }
//        $this->log();
        if ($this->option['RETURNTRANSFER']) {
            return $this->response;
        }
    }
    /**
     * 获取curl错误号
     *
     * @author 林祯 2017-01-23T11:02:47
     * @return int curl错误号
     */
    public function errno()
    {
        return curl_errno($this->ch);
    }
    /**
     * 获取curl错误信息
     *
     * @author 林祯 2017-01-23T11:03:18
     * @return string curl错误信息
     */
    public function error()
    {
        return curl_error($this->ch);
    }
    /**
     * 执行curl
     *
     * @author 林祯 2017-01-23T11:04:12
     * @return mix 执行结果
     */
    public function exec()
    {
        return curl_exec($this->ch);
    }
    /**
     * 设置curl参数
     *
     * @author 林祯 2017-01-23T11:05:09
     * @param  array  $option 参数数组
     */
    public function setopt($option = array())
    {
        $new_opt = array();
        $option = $option ? $option : $this->option;
        foreach ($option as $opt => $val) {
            if (($opt = $this->parseOpt($opt)) && $val !== null) {
                $new_opt[$opt] = $val;
            }
        }
        if (!empty($new_opt)) {
            curl_setopt_array($this->ch, $new_opt);
        }
    }
    /**
     * 日志记录
     *
     * @author 林祯 2017-01-23T11:05:56
     */
    private function log()
    {
        if ($this->log_dir) {
            $error = $this->error();// 记录日志
            $func = $error ? 'err' : 'info';
            Log::$func($this->log_dir, array(
                'url' => $this->option['URL'],
                'time_consum' => number_format($this->end - $this->begin, 4),
                'request' => $this->option['POSTFIELDS'],
                'response' => $error ? $error : str_replace(array("\r\n", "\r", "\n"), '', $this->response),
            ));
        }
    }
}
