<?php

namespace Fairy;

use Exception;
use PHPExcel;
use PHPExcel_IOFactory;

class Excel
{
    const TYPE_XLS = 'xls';

    const TYPE_XLSX = 'xlsx';

    const TYPE_PDF = 'pdf';

    const TYPE_ODS = 'ods';

    static private $instance;

    static private $phpexcel;

    static private $sheetIndex = 1;

    static public function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {

    }

    public function getPHPExcel()
    {
        if (is_null(self::$phpexcel)) {
            self::$phpexcel = new PHPExcel();
        }
        return self::$phpexcel;
    }

    /**
     * 添加sheet表
     * @return $this
     * @throws \PHPExcel_Exception
     */
    public function addSheet()
    {
        $objPHPExcel = $this->getPHPExcel();
        $objPHPExcel->createSheet();
        $objPHPExcel->setActiveSheetIndex(self::$sheetIndex);
        self::$sheetIndex++;

        return $this;
    }

    /**
     * 添加excel内容
     * @param array $header Excel 头部 ["COL1","COL2","COL3",...]
     * @param array $body 和头部长度相等字段查询出的数据就可以直接导出
     * @Param string $sheetName
     * @return $this
     * @throws \PHPExcel_Exception
     */
    public function addContent(array $head, array $body, $sheetName = 'Worksheet')
    {
        $objPHPExcel = $this->getPHPExcel();
        $sheetPHPExcel = $objPHPExcel->getActiveSheet();
        $sheetPHPExcel->setTitle($sheetName);
        $charIndex = range("A", "Z");

        // Excel 表格头
        foreach ($head as $key => $val) {
            $sheetPHPExcel->setCellValue("{$charIndex[$key]}1", $val);
        }

        // Excel body 部分
        foreach ($body as $key => $val) {
            $row = $key + 2;
            $col = 0;
            foreach ($val as $k => $v) {
//                    $sheetPHPExcel->setCellValueExplicit("{$charIndex[$col]}{$row}", $v, PHPExcel_Cell_DataType::TYPE_STRING);
                $sheetPHPExcel->setCellValue("{$charIndex[$col]}{$row}", $v);
                $col++;
            }
        }

        return $this;
    }

    /**
     * 生成文件
     * @param $filename
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function makeFile($filename)
    {
        $objWriter = PHPExcel_IOFactory::createWriter($this->getPHPExcel(), $this->getType($filename));
        $objWriter->save($filename);
    }

    /**
     * 一键导出
     * @param null|string $name 文件名，不包含扩展名，为空默认为当前时间
     * @param string|int $version Excel版本 xls|xlsx
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function download($name = null, $version = 'xlsx')
    {
        $name = empty($name) ? date('YmdHis') : $name;

        header('Content-Type: ' . $this->version($version)['mime']);
        header('Content-Disposition: attachment;filename="' . $name . $this->version($version)['ext'] . '"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($this->getPHPExcel(), $this->version($version)['write_type']);
        $objWriter->save('php://output');
    }

    /**
     * @param $type
     * @return mixed
     * @throws Exception
     */
    protected function version($type)
    {
        // 版本差异信息
        $versionOpt = [
            self::TYPE_XLSX => [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ext' => '.xlsx',
                'write_type' => 'Excel2007',
            ],
            self::TYPE_XLS => ['mime' => 'application/vnd.ms-excel',
                'ext' => '.xls',
                'write_type' => 'Excel5',
            ],
            self::TYPE_PDF => ['mime' => 'application/pdf',
                'ext' => '.pdf',
                'write_type' => 'PDF',
            ],
            self::TYPE_ODS => ['mime' => 'application/vnd.oasis.opendocument.spreadsheet',
                'ext' => '.ods',
                'write_type' => 'OpenDocument',
            ],
        ];

        if (!isset($versionOpt[$type])) {
            throw new Exception('error type');
        }

        return $versionOpt[$type];
    }

    /**
     * 解析 Excel 数据并写入到数据库
     * @param string $file Excel 路径名文件名
     * @param array $header 表头对应字段信息 ['A'=>'field1', 'B'=>'field2', ...]
     * @param int $sheet 哪个sheet
     * @param int $start 数据开始读取行数
     * @param string $type Excel2007|Excel5
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function parse($file, array $header, $sheet = 0, $start = 2, $type = '')
    {
        if (!is_file($file)) {
            throw new Exception('file:' . $file . ' not exists!');
        }
        $type = $this->getType($file, $type);
        $objReader = PHPExcel_IOFactory::createReader($type);
        $objPHPExcel = $objReader->load($file);
        // 数据数组
        $data = [];
        $objWorkSheet = $objPHPExcel->getSheet($sheet);
        $highestRow = $objWorkSheet->getHighestRow();
        if ($start > $highestRow) {
            return $data;
        }
        // 指定跳过的行数
        foreach ($objWorkSheet->getRowIterator($start) as $row) {
            // 逐个单元格读取，减少内存消耗
            $cellIterator = $row->getCellIterator();
            // 不跳过空值
            $cellIterator->setIterateOnlyExistingCells(false);
            // 只读取显示的行、列，跳过隐藏行、列
            if ($objWorkSheet->getRowDimension($row->getRowIndex())->getVisible()) {
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    if ($objWorkSheet->getColumnDimension($cell->getColumn())->getVisible()) {
                        if (isset($header[$cell->getColumn()])) {
                            $rowData[$header[$cell->getColumn()]] = $cell->getValue();
                        }
                    }
                }
                $data[] = $rowData;
            }
        }

        return $data;
    }

    /**
     * 解析 Excel 获取第一行头信息
     * @param string $file Excel 路径名文件名
     * @param string $type Excel2007|Excel5
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function parseHeader($file, $type = '')
    {
        $type = $this->getType($file, $type);
        $objReader = PHPExcel_IOFactory::createReader($type);
        $objPHPExcel = $objReader->load($file);
        $header = [];
        foreach ($objPHPExcel->getSheet()->getRowIterator() as $row) {
            // 逐个单元格读取，减少内存消耗
            $cellIterator = $row->getCellIterator();
            // 不跳过空值
            $cellIterator->setIterateOnlyExistingCells();
            if ($objPHPExcel->getActiveSheet()->getRowDimension($row->getRowIndex())->getVisible()) {
                foreach ($cellIterator as $cell) {
                    if ($objPHPExcel->getActiveSheet()->getColumnDimension($cell->getColumn())->getVisible()) {
                        $header[$cell->getColumn()] = $cell->getValue();
                    }
                }
                break;
            }
        }

        return $header;
    }

    /**
     * 自动获取 Excel 类型
     * @param string $file Excel 路径名文件名
     * @param string $type Excel2007|Excel5
     * @return string
     * @throws Exception
     */
    protected function getType($file, $type = '')
    {
        if (!$type) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'xls' :
                    $type = 'Excel5';
                    break;
                case 'xlsx' :
                    $type = 'Excel2007';
                    break;
                default:
                    throw new Exception('请指定Excel的类型');
            }
        }

        return $type;
    }

    /**
     * 将 Excel 时间转为标准的时间格式
     * @param $date
     * @param bool $time
     * @return array|int|string
     */
    public function excelTime($date, $time = false)
    {
        if (function_exists('GregorianToJD')) {
            if (is_numeric($date)) {
                $jd = GregorianToJD(1, 1, 1970);
                $gregorian = JDToGregorian($jd + intval($date) - 25569);
                $date = explode('/', $gregorian);
                $date_str = str_pad($date [2], 4, '0', STR_PAD_LEFT)
                    . "-" . str_pad($date [0], 2, '0', STR_PAD_LEFT)
                    . "-" . str_pad($date [1], 2, '0', STR_PAD_LEFT)
                    . ($time ? " 00:00:00" : '');

                return $date_str;
            }
        } else {
            $date = $date > 25568 ? $date + 1 : 25569;
            $ofs = (70 * 365 + 17 + 3) * 86400;
            $date = date("Y-m-d", ($date * 86400) - $ofs) . ($time ? " 00:00:00" : '');
        }

        return $date;
    }

    protected function __clone()
    {

    }

    protected function __wakeup()
    {

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