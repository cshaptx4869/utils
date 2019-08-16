<?php

require_once 'vendor/autoload.php';

use Fairy\Excel;
use Fairy\MhtFileMaker;
use Fairy\Pinyin;
use Fairy\SmsSender;
use Fairy\SMTPMailSender;
use Fairy\Str;
use Fairy\Time;
use Fairy\Token;

class Run
{
    /**
     * 生成jwt token
     * @return string
     */
    public function jwtEncode()
    {
        return Token::getInstance()->encode($uid = 1);
    }

    /**
     * 解析jwt
     * @return mixed
     */
    public function jwtDecode()
    {
        $jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvd3d3LmhhaGEuY29tIiwiYXVkIjoiaHR0cHM6XC9cL3d3dy5oYWhhLmNvbSIsImlhdCI6MTU2NTgzNzA0OSwibmJmIjoxNTY1ODM3MDQ5LCJkYXRhIjoxfQ.30uFnUP0CPMrCdU71_uLaxKMl2SkBHG4S7-hbDes-pU';
        if (($obj = Token::getInstance()->decode($jwt)) == false) {
            return Token::getInstance()->getError();
        }
        return $obj->data;
    }

    /**
     * html转word
     * @throws Exception
     */
    public function html2word()
    {
        // 本地下载
        MhtFileMaker::getInstance()
            ->addFile('resource/tpl.html')
            ->eraseLink()
            ->fetchImg('http://php.test/utils')
            ->makeFile('resource/a.doc');

        // 浏览器下载
        MhtFileMaker::getInstance()
            ->addFile('resource/tpl.html')
            ->fetchImg('http://php.test/utils')
            ->download();
    }

    /**
     * 生成excel
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function excelExport()
    {
        $head = ['姓名', '年龄', '性别', '体重', '身高'];
        $data = [
            ['张三', 20, '男', '60kg', '178cm'],
            ['李四', 24, '男', '70kg', '183cm'],
        ];
        $data2 = [
            ['王五', 21, '男', '63kg', '180cm'],
            ['赵六', 26, '男', '73kg', '185cm'],
        ];

        // 本地下载
        Excel::getInstance()->addContent($head, $data)->makeFile('resource/a.xls');

        // 添加多个sheet的内容
        Excel::getInstance()
            ->addContent($head, $data, 'sheet')
            ->addSheet()
            ->addContent($head, $data2, 'sheet2')
            ->makeFile('resource/a.xlsx');

        // 浏览器下载
        Excel::getInstance()->addContent($head, $data)->download();

        // 添加多个sheet的内容
        Excel::getInstance()
            ->addContent($head, $data, 'sheet')
            ->addSheet()
            ->addContent($head, $data2, 'sheet2')
            ->download();
    }

    /**
     * 解析excel
     * @return string
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function praseExcel()
    {
        $header = ['A' => 'name', 'B' => 'age', 'C' => 'sex', 'D' => 'weight', 'E' => 'height'];
        $data = Excel::getInstance()->parse('resource/a.xlsx', $header, 1);
        return json_encode($data);
    }

    /**
     * 发送邮件
     * @return mixed|string
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendMail()
    {
        // 参数配置看SMTPMailSender详情
        $bool = SMTPMailSender::getInstance()
            ->addAddress('994***638@qq.com', '君莫笑')
            ->addAttachment('resource/a.xlsx', '前方高能.xlsx')
            ->setContent('测试PHPMailer', '<p>这是来自PHPMailer的邮件发送！</p>')
            ->sendMail();

        if ($bool)
            return 'success';
        else
            return SMTPMailSender::getInstance()->getError();
    }

    /**
     * 创蓝253 发送短信
     * @return mixed|string
     * @throws ErrorException
     */
    public function sendSms()
    {
        // 参数配置看SmsSender详情
        // 发送短信
        $bool = SmsSender::getInstance()->send('183****1108');
        // 验证验证码是否正确
        $bool = SmsSender::getInstance()->isEffective('183****1108', 176020);

        if ($bool)
            return 'success';
        else
            return SmsSender::getInstance()->getError();
    }

    /**
     * 汉字转拼音
     * @return string
     */
    public function pinyin()
    {
        // 获取拼音
        return Pinyin::getPinyin("早上好");
    }

    /**
     * 字符串
     * @return string
     */
    public function str()
    {
        // 驼峰转下划线
        return Str::snake('ILoveYou');
    }

    /**
     * 时间
     * @return string
     */
    public function time()
    {
        // 上周起始时间
        return json_encode(Time::lastWeek());
    }
}