<?php

namespace Fairy;

use Curl\Curl;

/**
 * 接口购买地址 https://www.253.com/
 * 创蓝253
 * Class SmsSender
 * @package Fairy
 */
class SmsSender
{
    static private $instance;

    private $code;

    private $templateMsg;

    private $errorMsg;

    private $curl;

    protected $config = [
        'Geteway' => 'http://smssh1.253.com/msg/', // 短信网关地址
        'Account' => 'xxxxxxxx',                   // 账号
        'Password' => 'xxxxxxxx',                  // 密码
        'LifeTime' => 15,                          // 验证码有效期，分钟
        'MaxTime' => 5,                            // 每个账号一天允许的最大发送数
        'CacheDir' => './cache',
        'CacheFileSuffix' => '.bin'
    ];

    /**
     * 获取单例
     * @param array $config
     * @return SmsSender
     */
    public static function getInstance(array $config = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    protected function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->config['SmsName'] = 'sms_' . date('Ymd');
        if (!file_exists($this->config['CacheDir'])) {
            mkdir($this->config['CacheDir'], 0777, true);
        }
        $this->curl = new Curl();
    }

    /**
     * 短信发送
     * @param $mobile
     * @return bool
     * @throws \ErrorException
     */
    public function send($mobile)
    {
        if ($this->getSendTimes($mobile) >= $this->config['MaxTime']) {
            $this->errorMsg = '您今天的短信数已超出限制';
            return false;
        }

        if (!$this->code) {
            $code = rand(100000, 999999);
            $this->setTemplateMsg($code);
        }
        $message = $this->getTemplateMsg();

        $apiURL = $this->config['Geteway'] . 'send/json';
        $apiParams = array(
            'account' => $this->config['Account'],
            'password' => $this->config['Password'],
            'msg' => urlencode($message),
            'phone' => $mobile,
            'report' => true,
        );
        $this->curl->setHeader('Content-Type', 'application/json; charset=utf-8');
        $result = $this->curl->post($apiURL, $apiParams);
        if (0 == $result->code) {
            $this->afterSend($mobile, $this->code);
            return true;
        }
        $this->errorMsg = $result->errorMsg;

        return false;
    }

    /**
     * 设置短信内容
     * @param $code
     * @param null $lifetime
     * @return $this
     */
    public function setTemplateMsg($code, $lifetime = null)
    {
        if (is_null($lifetime)) {
            $lifetime = $this->config['LifeTime'];
        }
        $this->code = $code;
        $this->templateMsg = "您好，您的验证码为 {$code}，{$lifetime}分钟内有效，请勿向任何人泄露，感谢您的使用！";

        return $this;
    }

    /**
     * 获取短信模板内容
     * @return mixed
     */
    public function getTemplateMsg()
    {
        return $this->templateMsg;
    }

    /**
     * 获取当日发送的次数
     * @param $mobile
     * @return int
     */
    public function getSendTimes($mobile)
    {
        $record = $this->getCache($this->config['SmsName']);

        return isset($record[$mobile]) ? $record[$mobile] : 0;
    }

    private function afterSend($mobile, $code)
    {
        $record = $this->getCache($this->config['SmsName']);
        if (isset($record[$mobile])) {
            $record[$mobile]++;
        } else {
            $record[$mobile] = 1;
        }
        $this->setCache($this->config['SmsName'], $record);
        $this->setCache($mobile, $code, $this->config['LifeTime'] * 60);
    }

    /**
     * 验证码是否正确
     * @param $mobile
     * @param $code
     * @return bool
     */
    public function isEffective($mobile, $code)
    {
        $orginCode = $this->getCache($mobile);
        $this->removeCode($mobile);
        if ($orginCode == $code) {
            return true;
        }
        return false;
    }

    /**
     * 验证后移除验证码 防止在有效期内被暴力破解
     * @param $mobile
     */
    public function removeCode($mobile)
    {
        $this->delCache($mobile);
    }

    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param int $lifetime
     * @return bool
     */
    public function setCache($key, $value, $lifetime = -1)
    {
        $filename = $this->config['CacheDir'] . DIRECTORY_SEPARATOR . $key . $this->config['CacheFileSuffix'];
        $length = file_put_contents($filename, json_encode($value), LOCK_EX);
        if ($lifetime < 0) {
            $lifetime = 315360000;
        }
        touch($filename, $lifetime + time());

        return $length > 0;
    }

    public function getCache($key)
    {
        $filename = $this->config['CacheDir'] . DIRECTORY_SEPARATOR . $key . $this->config['CacheFileSuffix'];
        if (file_exists($filename) && filemtime($filename) > time()) {
            return json_decode(file_get_contents($filename), true);
        } elseif (file_exists($filename) && filemtime($filename) < time()) {
            unlink($filename);
        }
        return false;
    }

    public function delCache($key)
    {
        $filename = $this->config['CacheDir'] . DIRECTORY_SEPARATOR . $key . $this->config['CacheFileSuffix'];
        if (is_file($filename)) {
            return unlink($filename);
        }
        return false;
    }

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->errorMsg;
    }

    protected function __clone()
    {

    }

    protected function __wakeup()
    {

    }

    /**
     * 销毁curl对象
     */
    public function __destruct()
    {
        $this->curl->close();
    }

    /**
     * 主动释放对象(主要为了兼容swoole模式)
     * 普通模式下不需要手动释放
     */
    public function destory()
    {
        if (self::$instance) {
            self::$instance = null;
        }
    }
}