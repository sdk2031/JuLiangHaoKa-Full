<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use app\common\helper\AuHelper;

class Orderbatch extends Base
{
    public function __construct()
    {
        parent::__construct(); // 调用父类构造函数，执行登录检查🆕
        AuHelper::check();      // 然后执行授权检查
    }
    public function index()
    {
        return View::fetch('orderbatch/index');
    }

    /**
     * 下载导入模板
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('订单模板');
            
            // 设置表头
            $sheet->setCellValue('A1', '订单号（支持本地订单号或上游订单号）');
            $sheet->setCellValue('B1', '生产号码');
            $sheet->setCellValue('C1', '物流公司');
            $sheet->setCellValue('D1', '物流单号');
            $sheet->setCellValue('E1', 'ICCID');
            $sheet->setCellValue('F1', 'PUK');
            $sheet->setCellValue('G1', '订单状态（0-7或中文）');
            $sheet->setCellValue('H1', '备注');

            // 关键列强制按文本处理，避免 Excel/WPS 把 ICCID、订单号等转成科学计数法
            $sheet->getStyle('A:H')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            
            // 设置表头样式
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            $sheet->getStyle('A1:H1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // 设置列宽
            $sheet->getColumnDimension('A')->setWidth(35);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(24);
            $sheet->getColumnDimension('F')->setWidth(18);
            $sheet->getColumnDimension('G')->setWidth(20);
            $sheet->getColumnDimension('H')->setWidth(30);
            
            // 添加说明行
            $sheet->setCellValue('A2', '填写本地订单号（如 SH202410210001）或上游订单号（如 P2025111605252231760714165）');
            $sheet->getStyle('A2:H2')->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle('A2:H2')->getFont()->getColor()->setARGB('FF999999');
            
            // 添加示例数据
            $sheet->setCellValueExplicit('A3', 'SH202410210001', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B3', '13800138000', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C3', '顺丰速运', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D3', 'SF1234567890', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E3', '8986001234567890123', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F3', '12345678', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G3', '待发货', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('H3', '备注信息示例', DataType::TYPE_STRING);
            
            // 先写入临时文件，再输出下载，避免输出缓冲导致 xlsx 损坏
            $tempFile = tempnam(sys_get_temp_dir(), 'order_batch_tpl_');
            if ($tempFile === false) {
                throw new \RuntimeException('无法创建临时文件');
            }

            $xlsxFile = $tempFile . '.xlsx';
            @unlink($tempFile);

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($xlsxFile);

            $filename = 'order_batch_template_' . date('YmdHis') . '.xlsx';

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (function_exists('ini_set')) {
                @ini_set('zlib.output_compression', 'Off');
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($xlsxFile));
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            header('Expires: 0');

            readfile($xlsxFile);

            @unlink($xlsxFile);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            exit;
            
        } catch (\Exception $e) {
            return $this->error('模板生成失败：' . $e->getMessage());
        }
    }

    /**
     * 下载CSV模板（无需依赖，轻量快速）
     */
    public function downloadCsvTemplate()
    {
        try {
            // 设置 CSV 文件头
            $filename = '订单批量操作模板_' . date('YmdHis') . '.csv';
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // 打开输出流
            $output = fopen('php://output', 'w');
            
            // 添加 BOM 头（确保 Excel 正确识别 UTF-8）
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 写入表头
            fputcsv($output, [
                '订单号（支持本地订单号或上游订单号）',
                '生产号码',
                '物流公司',
                '物流单号',
                'ICCID',
                'PUK',
                '订单状态（0-7或中文）',
                '备注'
            ]);
            
            // 写入说明行
            fputcsv($output, [
                '填写本地订单号（如 SH202410210001）或上游订单号（如 P2025111605252231760714165）',
                '选填',
                '选填',
                '选填',
                '选填（请保持文本格式）',
                '选填',
                '选填（支持 0-7 或中文状态）',
                '选填'
            ]);
            
            // 写入示例数据
            fputcsv($output, [
                'SH202410210001',
                '13800138000',
                '顺丰速运',
                'SF1234567890',
                '8986001234567890123',
                '12345678',
                '待发货',
                '备注信息示例'
            ]);
            
            fclose($output);
            exit;
            
        } catch (\Exception $e) {
            return $this->error('CSV模板生成失败：' . $e->getMessage());
        }
    }

    /**
     * 导入Excel或CSV文件
     */
    public function importExcel()
    {
        try {
            $file = request()->file('file');
            
            if (!$file) {
                return json(['code' => 0, 'msg' => '请选择文件']);
            }
            
            // 保存上传文件到临时目录
            $uploadPath = root_path() . 'public/uploads/temp/' . date('Ymd') . '/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $extension = strtolower($file->getOriginalExtension());
            $savename = md5(uniqid()) . '.' . $extension;
            $file->move($uploadPath, $savename);
            $filePath = $uploadPath . $savename;
            
            $items = [];
            $errors = [];
            $hasRowLevelChanges = false;
            
            // 根据文件类型选择解析方式
            if ($extension === 'csv') {
                // ===== CSV 解析（使用 PHP 原生函数，无需依赖）=====
                $handle = fopen($filePath, 'r');
                if (!$handle) {
                    return json(['code' => 0, 'msg' => 'CSV文件打开失败']);
                }
                
                $row = 0;
                while (($data = fgetcsv($handle)) !== false) {
                    $row++;
                    
                    // 跳过表头行
                    if ($row === 1) {
                        continue;
                    }
                    
                    // 获取订单号（第一列）
                    $orderNo = isset($data[0]) ? trim($data[0]) : '';
                    
                    // 跳过空行或说明行
                    if (empty($orderNo) || mb_strpos($orderNo, '填写') !== false || mb_strpos($orderNo, '如 ') !== false) {
                        continue;
                    }
                    
                    // 查询订单是否存在（支持本地订单号和上游订单号）
                    $order = Db::name('order')
                        ->whereOr([
                            ['order_no', '=', $orderNo],
                            ['up_order_no', '=', $orderNo]
                        ])
                        ->find();
                    
                    if (!$order) {
                        $errors[] = "第{$row}行：订单号 {$orderNo} 不存在（支持本地订单号或上游订单号）";
                        continue;
                    }
                    
                    $statusParse = $this->parseImportOrderStatus($data[6] ?? '');
                    if (!$statusParse['success']) {
                        $errors[] = "第{$row}行：" . $statusParse['message'];
                        continue;
                    }

                    $iccidValidation = $this->validateLongTextField($data[4] ?? null, 'ICCID');
                    if (!$iccidValidation['success']) {
                        $errors[] = "第{$row}行：" . $iccidValidation['message'];
                        continue;
                    }

                    $changes = [
                        'new_production_number' => $this->normalizeNullableValue($data[1] ?? null),
                        'new_express_company' => $this->normalizeNullableValue($data[2] ?? null),
                        'new_tracking_number' => $this->normalizeNullableValue($data[3] ?? null),
                        'new_iccid' => $this->normalizeNullableValue($data[4] ?? null),
                        'new_puk' => $this->normalizeNullableValue($data[5] ?? null),
                        'new_status' => $statusParse['value'],
                        'new_remark' => $this->normalizeNullableValue($data[7] ?? null),
                    ];

                    if ($this->hasAnyImportChange($changes)) {
                        $hasRowLevelChanges = true;
                    }

                    $items[] = $this->buildImportItem($order, $orderNo, $changes);
                }
                
                fclose($handle);
                
            } else {
                // ===== Excel 解析（使用 PhpSpreadsheet）=====
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                
                // 从第2行开始读取（第1行是表头，第2行可能是说明或数据）
                for ($row = 2; $row <= $highestRow; $row++) {
                    $orderNo = trim($sheet->getCell('A' . $row)->getValue());
                    
                    // 跳过空行或说明行（包含"填写"、"如"等提示文字）
                    if (empty($orderNo) || mb_strpos($orderNo, '填写') !== false || mb_strpos($orderNo, '如 ') !== false) {
                        continue;
                    }
                    
                    // 查询订单是否存在（支持本地订单号和上游订单号）
                    $order = Db::name('order')
                        ->whereOr([
                            ['order_no', '=', $orderNo],
                            ['up_order_no', '=', $orderNo]
                        ])
                        ->find();
                    
                    if (!$order) {
                        $errors[] = "第{$row}行：订单号 {$orderNo} 不存在（支持本地订单号或上游订单号）";
                        continue;
                    }
                    
                    $statusParse = $this->parseImportOrderStatus($sheet->getCell('G' . $row)->getValue());
                    if (!$statusParse['success']) {
                        $errors[] = "第{$row}行：" . $statusParse['message'];
                        continue;
                    }

                    $iccidValue = $this->readSheetCellString($sheet, 'E' . $row);
                    $iccidValidation = $this->validateLongTextField($iccidValue, 'ICCID');
                    if (!$iccidValidation['success']) {
                        $errors[] = "第{$row}行：" . $iccidValidation['message'];
                        continue;
                    }

                    $changes = [
                        'new_production_number' => $this->readSheetCellString($sheet, 'B' . $row),
                        'new_express_company' => $this->readSheetCellString($sheet, 'C' . $row),
                        'new_tracking_number' => $this->readSheetCellString($sheet, 'D' . $row),
                        'new_iccid' => $iccidValidation['value'],
                        'new_puk' => $this->readSheetCellString($sheet, 'F' . $row),
                        'new_status' => $statusParse['value'],
                        'new_remark' => $this->readSheetCellString($sheet, 'H' . $row),
                    ];

                    if ($this->hasAnyImportChange($changes)) {
                        $hasRowLevelChanges = true;
                    }

                    $items[] = $this->buildImportItem($order, $orderNo, $changes);
                }
            }
            
            // 删除临时文件
            @unlink($filePath);
            
            if (empty($items) && empty($errors)) {
                return json(['code' => 0, 'msg' => '文件中没有有效数据']);
            }
            
            return json([
                'code' => 1,
                'msg' => '导入成功',
                'data' => [
                    'items' => $items,
                    'total' => count($items),
                    'errors' => $errors,
                    'operation_type' => 'mixed',
                    'has_changes' => $hasRowLevelChanges ? 1 : 0
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '导入失败：' . $e->getMessage()]);
        }
    }

    /**
     * 粘贴订单号导入
     */
    public function importText()
    {
        try {
            $orderText = trim((string)input('post.order_text', ''));
            $updateField = trim((string)input('post.update_field', ''));
            if ($orderText === '') {
                return json(['code' => 0, 'msg' => '请输入订单号']);
            }

            $items = [];
            $errors = [];

            if (in_array($updateField, ['remark', 'production_number', 'express'], true)) {
                $lines = preg_split('/\r\n|\r|\n/u', $orderText, -1, PREG_SPLIT_NO_EMPTY);
                if (empty($lines)) {
                    return json(['code' => 0, 'msg' => '未识别到有效内容']);
                }

                foreach ((array)$lines as $index => $line) {
                    $parsed = $this->parseTextImportLine(trim((string)$line), $updateField);
                    if (!$parsed['success']) {
                        $errors[] = '第' . ($index + 1) . '行：' . $parsed['message'];
                        continue;
                    }

                    $orderNo = $parsed['order_no'];
                    $order = Db::name('order')
                        ->whereOr([
                            ['order_no', '=', $orderNo],
                            ['up_order_no', '=', $orderNo]
                        ])
                        ->find();

                    if (!$order) {
                        $errors[] = '第' . ($index + 1) . '行：订单号 ' . $orderNo . ' 不存在（支持本地订单号或上游订单号）';
                        continue;
                    }

                    $items[] = [
                        'order_no' => $order['order_no'],
                        'up_order_no' => $order['up_order_no'],
                        'input_order_no' => $orderNo,
                        'order_id' => $order['id'],
                        'current_status' => $order['order_status'],
                        'current_remark' => $order['remark'],
                        'current_production_number' => $order['production_number'],
                        'current_express_company' => $order['express_company'],
                        'current_tracking_number' => $order['tracking_number'],
                        'current_iccid' => $order['iccid'] ?? '',
                        'current_puk' => $order['puk'] ?? '',
                        'new_remark' => $parsed['new_remark'],
                        'new_production_number' => $parsed['new_production_number'],
                        'new_express_company' => $parsed['new_express_company'],
                        'new_tracking_number' => $parsed['new_tracking_number'],
                        'new_iccid' => $parsed['new_iccid'],
                        'new_puk' => $parsed['new_puk'],
                        'new_status' => $parsed['new_status'],
                        'customer_name' => $order['customer_name'],
                        'phone' => $order['phone'],
                        'product_name' => $order['product_name'],
                    ];
                }
            } else {
                $rawList = preg_split('/[\s,，;；]+/u', $orderText, -1, PREG_SPLIT_NO_EMPTY);
                $orderNos = [];
                $seen = [];
                foreach ((array)$rawList as $value) {
                    $orderNo = trim((string)$value);
                    if ($orderNo === '' || isset($seen[$orderNo])) {
                        continue;
                    }
                    $seen[$orderNo] = true;
                    $orderNos[] = $orderNo;
                }

                if (empty($orderNos)) {
                    return json(['code' => 0, 'msg' => '未识别到有效订单号']);
                }

                foreach ($orderNos as $index => $orderNo) {
                    $order = Db::name('order')
                        ->whereOr([
                            ['order_no', '=', $orderNo],
                            ['up_order_no', '=', $orderNo]
                        ])
                        ->find();

                    if (!$order) {
                        $errors[] = '第' . ($index + 1) . '个：订单号 ' . $orderNo . ' 不存在（支持本地订单号或上游订单号）';
                        continue;
                    }

                    $items[] = [
                        'order_no' => $order['order_no'],
                        'up_order_no' => $order['up_order_no'],
                        'input_order_no' => $orderNo,
                        'order_id' => $order['id'],
                        'current_status' => $order['order_status'],
                        'current_remark' => $order['remark'],
                        'current_production_number' => $order['production_number'],
                        'current_express_company' => $order['express_company'],
                        'current_tracking_number' => $order['tracking_number'],
                        'current_iccid' => $order['iccid'] ?? '',
                        'current_puk' => $order['puk'] ?? '',
                        'new_remark' => '',
                        'new_production_number' => '',
                        'new_express_company' => '',
                        'new_tracking_number' => '',
                        'new_iccid' => null,
                        'new_puk' => null,
                        'new_status' => null,
                        'customer_name' => $order['customer_name'],
                        'phone' => $order['phone'],
                        'product_name' => $order['product_name'],
                    ];
                }
            }

            if (empty($items) && empty($errors)) {
                return json(['code' => 0, 'msg' => '没有有效订单数据']);
            }

            return json([
                'code' => 1,
                'msg' => '导入成功',
                'data' => [
                    'items' => $items,
                    'total' => count($items),
                    'errors' => $errors,
                    'operation_type' => in_array($updateField, ['remark', 'production_number', 'express'], true) ? 'update' : 'status',
                    'update_field' => $updateField
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '导入失败：' . $e->getMessage()]);
        }
    }

    private function parseTextImportLine(string $line, string $updateField): array
    {
        if ($line === '') {
            return ['success' => false, 'message' => '内容为空'];
        }

        $segments = preg_split('/[\t,，|]+/u', $line, -1, PREG_SPLIT_NO_EMPTY);
        if ($updateField === 'express') {
            if (count($segments) < 3) {
                $segments = preg_split('/\s+/u', $line, -1, PREG_SPLIT_NO_EMPTY);
            }
            if (count($segments) < 3) {
                return ['success' => false, 'message' => '物流信息格式不正确，请按“订单号 物流公司 物流单号”填写'];
            }

            $orderNo = trim((string)array_shift($segments));
            $expressCompany = trim((string)array_shift($segments));
            $trackingNumber = trim((string)implode(' ', $segments));

            if ($orderNo === '' || $expressCompany === '' || $trackingNumber === '') {
                return ['success' => false, 'message' => '物流信息格式不正确，请按“订单号 物流公司 物流单号”填写'];
            }

            return [
                'success' => true,
                'order_no' => $orderNo,
                'new_remark' => '',
                'new_production_number' => '',
                'new_express_company' => $expressCompany,
                'new_tracking_number' => $trackingNumber,
                'new_iccid' => null,
                'new_puk' => null,
                'new_status' => null,
            ];
        }

        if (!preg_match('/^(\S+)[\s,，;；|]+(.+)$/u', $line, $matches)) {
            return ['success' => false, 'message' => '格式不正确，请按“订单号 内容”填写'];
        }

        $orderNo = trim((string)($matches[1] ?? ''));
        $content = trim((string)($matches[2] ?? ''));
        if ($orderNo === '' || $content === '') {
            return ['success' => false, 'message' => '格式不正确，请按“订单号 内容”填写'];
        }

        return [
            'success' => true,
            'order_no' => $orderNo,
            'new_remark' => $updateField === 'remark' ? $content : '',
            'new_production_number' => $updateField === 'production_number' ? $content : '',
            'new_express_company' => '',
            'new_tracking_number' => '',
            'new_iccid' => null,
            'new_puk' => null,
            'new_status' => null,
        ];
    }

    private function normalizeNullableValue($value): ?string
    {
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function readSheetCellString($sheet, string $coordinate): ?string
    {
        $cell = $sheet->getCell($coordinate);
        $value = $cell->getFormattedValue();
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }

        return $this->normalizeNullableValue($value);
    }

    private function validateLongTextField($value, string $fieldLabel): array
    {
        $normalized = $this->normalizeNullableValue($value);
        if ($normalized === null) {
            return ['success' => true, 'value' => null];
        }

        if (preg_match('/^[0-9]+(\.[0-9]+)?E\+[0-9]+$/i', $normalized)) {
            return [
                'success' => false,
                'message' => $fieldLabel . ' 被 Excel/WPS 自动转成科学计数法，请重新下载最新模板并保持该列为文本格式后再导入'
            ];
        }

        return ['success' => true, 'value' => $normalized];
    }

    private function parseImportOrderStatus($value): array
    {
        $raw = $this->normalizeNullableValue($value);
        if ($raw === null) {
            return ['success' => true, 'value' => null];
        }

        $statusMap = [
            '0' => '0',
            '已提交' => '0',
            '提交' => '0',
            '1' => '1',
            '待发货' => '1',
            '2' => '2',
            '已发货' => '2',
            '3' => '3',
            '待传照片' => '3',
            '待上传照片' => '3',
            '4' => '4',
            '已激活' => '4',
            '激活' => '4',
            '5' => '5',
            '已结算' => '5',
            '结算' => '5',
            '6' => '6',
            '结算失败' => '6',
            '7' => '7',
            '审核失败' => '7',
        ];

        if (!array_key_exists($raw, $statusMap)) {
            return ['success' => false, 'message' => '订单状态仅支持 0-7 或中文状态名称'];
        }

        return ['success' => true, 'value' => $statusMap[$raw]];
    }

    private function hasAnyImportChange(array $changes): bool
    {
        foreach (['new_production_number', 'new_express_company', 'new_tracking_number', 'new_iccid', 'new_puk', 'new_status', 'new_remark'] as $field) {
            if (array_key_exists($field, $changes) && $changes[$field] !== null && $changes[$field] !== '') {
                return true;
            }
        }

        return false;
    }

    private function buildImportItem(array $order, string $inputOrderNo, array $changes): array
    {
        return [
            'order_no' => $order['order_no'],
            'up_order_no' => $order['up_order_no'],
            'input_order_no' => $inputOrderNo,
            'order_id' => $order['id'],
            'current_status' => $order['order_status'],
            'current_remark' => $order['remark'] ?? '',
            'current_production_number' => $order['production_number'] ?? '',
            'current_express_company' => $order['express_company'] ?? '',
            'current_tracking_number' => $order['tracking_number'] ?? '',
            'current_iccid' => $order['iccid'] ?? '',
            'current_puk' => $order['puk'] ?? '',
            'new_remark' => $changes['new_remark'] ?? null,
            'new_production_number' => $changes['new_production_number'] ?? null,
            'new_express_company' => $changes['new_express_company'] ?? null,
            'new_tracking_number' => $changes['new_tracking_number'] ?? null,
            'new_iccid' => $changes['new_iccid'] ?? null,
            'new_puk' => $changes['new_puk'] ?? null,
            'new_status' => $changes['new_status'] ?? null,
            'customer_name' => $order['customer_name'],
            'phone' => $order['phone'],
            'product_name' => $order['product_name'],
        ];
    }

    private function buildRemarkPayload($remark, $iccid, $puk): ?string
    {
        $remark = $remark === null ? null : (string)$remark;
        $iccid = $iccid === null ? null : (string)$iccid;
        $puk = $puk === null ? null : (string)$puk;

        if (($remark === null || $remark === '') && ($iccid === null || $iccid === '') && ($puk === null || $puk === '')) {
            return null;
        }

        return json_encode([
            'remark' => $remark,
            'iccid' => $iccid,
            'puk' => $puk,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function parseRemarkPayload($value): array
    {
        if ($value === null || $value === '') {
            return ['remark' => null, 'iccid' => null, 'puk' => null];
        }

        $decoded = json_decode((string)$value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && (
            array_key_exists('remark', $decoded) || array_key_exists('iccid', $decoded) || array_key_exists('puk', $decoded)
        )) {
            return [
                'remark' => array_key_exists('remark', $decoded) ? $decoded['remark'] : null,
                'iccid' => array_key_exists('iccid', $decoded) ? $decoded['iccid'] : null,
                'puk' => array_key_exists('puk', $decoded) ? $decoded['puk'] : null,
            ];
        }

        return ['remark' => (string)$value, 'iccid' => null, 'puk' => null];
    }

    /**
     * 执行批量操作
     */
    public function executeBatch()
    {
        try {
            $operationType = input('post.operation_type', '');
            $targetStatus = input('post.target_status', '');
            $items = input('post.items/a', []);

            // 兼容application/json提交，避免大批量数据触发max_input_vars截断
            if (empty($items)) {
                $rawInput = file_get_contents('php://input');
                if (!empty($rawInput)) {
                    $jsonData = json_decode($rawInput, true);
                    if (is_array($jsonData)) {
                        $operationType = $jsonData['operation_type'] ?? $operationType;
                        $targetStatus = $jsonData['target_status'] ?? $targetStatus;
                        $items = isset($jsonData['items']) && is_array($jsonData['items']) ? $jsonData['items'] : [];
                    }
                }
            }
            
            if (empty($items)) {
                return json(['code' => 0, 'msg' => '没有要处理的订单']);
            }

            // 预构建有效明细数据，后续使用insertAll分块入库
            $batchItems = [];
            foreach ($items as $item) {
                if (empty($item['order_id']) || empty($item['order_no'])) {
                    continue;
                }

                $parsedTargetStatus = $this->parseImportOrderStatus($item['new_status'] ?? null);
                if (!$parsedTargetStatus['success']) {
                    return json(['code' => 0, 'msg' => '订单号 ' . $item['order_no'] . ' 的订单状态不正确']);
                }
                $newStatus = $parsedTargetStatus['value'];
                if (($newStatus === null || $newStatus === '') && $operationType === 'status') {
                    $fallbackStatus = $this->parseImportOrderStatus($targetStatus);
                    $newStatus = $fallbackStatus['value'] ?? null;
                }

                $newRemarkPayload = $this->buildRemarkPayload(
                    $this->normalizeNullableValue($item['new_remark'] ?? null),
                    $this->normalizeNullableValue($item['new_iccid'] ?? null),
                    $this->normalizeNullableValue($item['new_puk'] ?? null)
                );

                $oldRemarkPayload = $newRemarkPayload !== null
                    ? $this->buildRemarkPayload(
                        isset($item['current_remark']) ? (string)$item['current_remark'] : '',
                        isset($item['current_iccid']) ? (string)$item['current_iccid'] : '',
                        isset($item['current_puk']) ? (string)$item['current_puk'] : ''
                    )
                    : null;

                $batchItems[] = [
                    'order_id' => $item['order_id'],
                    'order_no' => $item['order_no'],
                    'old_status' => $item['current_status'],
                    'new_status' => $newStatus,
                    'old_remark' => $oldRemarkPayload,
                    'new_remark' => $newRemarkPayload,
                    'old_production_number' => isset($item['current_production_number']) ? (string)$item['current_production_number'] : '',
                    'new_production_number' => $this->normalizeNullableValue($item['new_production_number'] ?? null),
                    'old_express_company' => isset($item['current_express_company']) ? (string)$item['current_express_company'] : '',
                    'new_express_company' => $this->normalizeNullableValue($item['new_express_company'] ?? null),
                    'old_tracking_number' => isset($item['current_tracking_number']) ? (string)$item['current_tracking_number'] : '',
                    'new_tracking_number' => $this->normalizeNullableValue($item['new_tracking_number'] ?? null),
                    'execute_status' => 0
                ];
            }

            if (empty($batchItems)) {
                return json(['code' => 0, 'msg' => '没有可处理的有效订单数据']);
            }
            
            // 获取管理员信息
            $adminInfo = $this->getAdminInfo();
            if (!$adminInfo) {
                return json(['code' => 0, 'msg' => '登录状态异常，请刷新页面重新登录']);
            }
            $adminId = $adminInfo['id'];
            $adminName = $adminInfo['nickname'] ?? $adminInfo['username'] ?? '管理员';
            
            // 生成批次号
            $batchNo = 'BATCH' . date('YmdHis') . rand(1000, 9999);
            
            Db::startTrans();
            
            try {
                // 创建批次记录
                $batchId = Db::name('order_batch')->insertGetId([
                    'batch_no' => $batchNo,
                    'admin_id' => $adminId,
                    'admin_name' => $adminName,
                    'operation_type' => $operationType,
                    'target_status' => $targetStatus,
                    'total_count' => count($batchItems),
                    'success_count' => 0,
                    'fail_count' => 0,
                    'status' => 0,
                    'create_time' => date('Y-m-d H:i:s')
                ]);
                
                // 批量创建明细记录（高性能：分块insertAll）
                $chunkSize = 500;
                foreach (array_chunk($batchItems, $chunkSize) as $chunk) {
                    $insertRows = [];
                    foreach ($chunk as $row) {
                        $insertRows[] = [
                            'batch_id' => $batchId,
                            'batch_no' => $batchNo,
                            'order_id' => $row['order_id'],
                            'order_no' => $row['order_no'],
                            'old_status' => $row['old_status'],
                            'new_status' => $row['new_status'],
                            'old_remark' => $row['old_remark'],
                            'new_remark' => $row['new_remark'],
                            'old_production_number' => $row['old_production_number'],
                            'new_production_number' => $row['new_production_number'],
                            'old_express_company' => $row['old_express_company'],
                            'new_express_company' => $row['new_express_company'],
                            'old_tracking_number' => $row['old_tracking_number'],
                            'new_tracking_number' => $row['new_tracking_number'],
                            'execute_status' => 0
                        ];
                    }
                    if (!empty($insertRows)) {
                        Db::name('order_batch_item')->insertAll($insertRows);
                    }
                }
                
                Db::commit();
                
                return json([
                    'code' => 1,
                    'msg' => '批次创建成功',
                    'data' => [
                        'batch_id' => $batchId,
                        'batch_no' => $batchNo
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    /**
     * 处理批次（AJAX分批处理）
     */
    public function processBatch()
    {
        try {
            $batchId = input('post.batch_id/d', 0);
            $highPerformance = input('post.high_performance/d', 0);
            $limit = input('post.limit/d', $highPerformance ? 100 : 20);
            $limit = max(1, min($limit, 500));
            
            if (empty($batchId)) {
                return json(['code' => 0, 'msg' => '缺少批次ID']);
            }
            
            // 获取批次信息
            $batch = Db::name('order_batch')->where('id', $batchId)->find();
            if (!$batch) {
                return json(['code' => 0, 'msg' => '批次不存在']);
            }
            
            // 更新批次状态为处理中
            if (intval($batch['status']) === 0) {
                Db::name('order_batch')->where('id', $batchId)->update([
                    'status' => 1,
                    'execute_time' => date('Y-m-d H:i:s')
                ]);
            }
            
            // 获取待处理的订单
            $items = Db::name('order_batch_item')
                ->where('batch_id', $batchId)
                ->where('execute_status', 0)
                ->limit($limit)
                ->select();
            
            $successCount = 0;
            $failCount = 0;
            $results = [];
            
            foreach ($items as $item) {
                try {
                    $updateData = [];
                    
                    // 根据操作类型准备更新数据
                    if ($item['new_status'] !== null && $item['new_status'] !== '') {
                        $updateData['order_status'] = $item['new_status'];
                    }

                    $remarkPayload = $this->parseRemarkPayload($item['new_remark']);
                    if ($remarkPayload['remark'] !== null) {
                        $updateData['remark'] = $remarkPayload['remark'];
                    }
                    if ($remarkPayload['iccid'] !== null) {
                        $updateData['iccid'] = $remarkPayload['iccid'];
                    }
                    if ($remarkPayload['puk'] !== null) {
                        $updateData['puk'] = $remarkPayload['puk'];
                    }

                    if ($item['new_production_number'] !== null && $item['new_production_number'] !== '') {
                        $updateData['production_number'] = $item['new_production_number'];
                    }

                    if ($item['new_express_company'] !== null && $item['new_express_company'] !== '') {
                        $updateData['express_company'] = $item['new_express_company'];
                    }

                    if ($item['new_tracking_number'] !== null && $item['new_tracking_number'] !== '') {
                        $updateData['tracking_number'] = $item['new_tracking_number'];
                    }
                    
                    if (!empty($updateData)) {
                        // 状态变为已激活时，设置激活时间
                        if (isset($updateData['order_status']) && $updateData['order_status'] == '4' && $item['old_status'] != '4') {
                            // 查询当前jh_time，有值不覆盖
                            $currentJhTime = Db::name('order')->where('id', $item['order_id'])->value('jh_time');
                            if (empty($currentJhTime)) {
                                $updateData['jh_time'] = date('Y-m-d H:i:s');
                            }
                        }
                        
                        // 状态变为已结算时，设置结算时间
                        if (isset($updateData['order_status']) && $updateData['order_status'] == '5' && $item['old_status'] != '5') {
                            $updateData['js_time'] = date('Y-m-d H:i:s');
                        }
                        
                        $updateData['update_time'] = date('Y-m-d H:i:s');
                        
                        // 更新订单
                        Db::name('order')->where('id', $item['order_id'])->update($updateData);
                        
                        // 如果状态变更为已激活或已结算，且原状态不是该状态，触发佣金处理
                        // OrderCommissionService 内部有去重逻辑，会检查是否已有记录
                        if (isset($updateData['order_status']) && in_array($updateData['order_status'], ['4', '5'])) {
                            $oldStatus = $item['old_status'] ?? '';
                            $newStatus = $updateData['order_status'];
                            
                            // 只有状态真正发生变化时才处理佣金
                            if (($newStatus == '4' && $oldStatus != '4') || ($newStatus == '5' && $oldStatus != '5')) {
                                // 付费卡补齐：批量导入触发结算前，确保溢价结算所需数据存在
                                $orderForSettlement = Db::name('order')
                                    ->where('id', $item['order_id'])
                                    ->field('id, order_no, card_type, product_id, pay_status, pay_time, agent_change')
                                    ->find();

                                if ($orderForSettlement && intval($orderForSettlement['card_type']) === 1) {
                                    // 1) 补齐agent_change里的markup快照（老订单/历史订单可能没有）
                                    $needRecordMarkup = true;
                                    if (!empty($orderForSettlement['agent_change'])) {
                                        $agentChange = json_decode($orderForSettlement['agent_change'], true);
                                        if (is_array($agentChange) && !empty($agentChange)) {
                                            $hasMarkupField = false;
                                            foreach ($agentChange as $acItem) {
                                                if (array_key_exists('markup', (array)$acItem)) {
                                                    $hasMarkupField = true;
                                                    break;
                                                }
                                            }
                                            $needRecordMarkup = !$hasMarkupField;
                                        }
                                    }
                                    if ($needRecordMarkup && !empty($orderForSettlement['product_id'])) {
                                        \app\common\service\MarkupSettlementService::recordMarkupChain(
                                            intval($orderForSettlement['id']),
                                            intval($orderForSettlement['product_id'])
                                        );
                                    }

                                    // 2) 手工批量改到已结算时，若支付状态未完成则补齐，避免溢价结算被"未支付"拦截
                                    if ($newStatus == '5' && intval($orderForSettlement['pay_status']) !== 1) {
                                        $payFixData = ['pay_status' => 1];
                                        if (empty($orderForSettlement['pay_time']) || $orderForSettlement['pay_time'] === '0000-00-00 00:00:00') {
                                            $payFixData['pay_time'] = date('Y-m-d H:i:s');
                                        }
                                        Db::name('order')->where('id', $item['order_id'])->update($payFixData);
                                    }
                                }

                                $settlementResult = \app\common\helper\OrderSettlementHelper::processOrderSettlement($item['order_id'], $newStatus);
                                if (!$settlementResult['success']) {
                                    \think\facade\Log::warning("批量导入订单佣金处理失败: ID={$item['order_id']}, 错误: " . $settlementResult['message']);
                                }
                            }
                        }
                        
                        // 更新明细状态为成功
                        Db::name('order_batch_item')->where('id', $item['id'])->update([
                            'execute_status' => 1,
                            'execute_time' => date('Y-m-d H:i:s')
                        ]);
                        
                        $successCount++;
                        if (!$highPerformance) {
                            $results[] = [
                                'index' => $item['id'],
                                'order_id' => $item['order_id'],
                                'order_no' => $item['order_no'],
                                'status' => 'success',
                                'message' => '成功'
                            ];
                        }
                    } else {
                        // 没有需要更新的数据
                        Db::name('order_batch_item')->where('id', $item['id'])->update([
                            'execute_status' => 2,
                            'fail_reason' => '没有需要更新的数据',
                            'execute_time' => date('Y-m-d H:i:s')
                        ]);
                        $failCount++;
                        if (!$highPerformance) {
                            $results[] = [
                                'index' => $item['id'],
                                'order_id' => $item['order_id'],
                                'order_no' => $item['order_no'],
                                'status' => 'fail',
                                'message' => '没有需要更新的数据'
                            ];
                        }
                    }
                    
                } catch (\Exception $e) {
                    // 更新明细状态为失败
                    Db::name('order_batch_item')->where('id', $item['id'])->update([
                        'execute_status' => 2,
                        'fail_reason' => $e->getMessage(),
                        'execute_time' => date('Y-m-d H:i:s')
                    ]);
                    $failCount++;
                    if (!$highPerformance) {
                        $results[] = [
                            'index' => $item['id'],
                            'order_id' => $item['order_id'],
                            'order_no' => $item['order_no'],
                            'status' => 'fail',
                            'message' => $e->getMessage()
                        ];
                    }
                }
            }
            
            // 更新批次统计
            Db::name('order_batch')->where('id', $batchId)->inc('success_count', $successCount);
            Db::name('order_batch')->where('id', $batchId)->inc('fail_count', $failCount);
            
            // 检查是否全部处理完成
            $pendingCount = Db::name('order_batch_item')
                ->where('batch_id', $batchId)
                ->where('execute_status', 0)
                ->count();
            $processedCount = max(0, intval($batch['total_count']) - intval($pendingCount));
            
            if ($pendingCount === 0) {
                // 全部处理完成
                Db::name('order_batch')->where('id', $batchId)->update([
                    'status' => 1, // 1=已完成, 2=已撤回
                    'finish_time' => date('Y-m-d H:i:s')
                ]);
                
                return json([
                    'code' => 1,
                    'msg' => '处理完成',
                    'data' => [
                        'finished' => true,
                        'processed' => $batch['total_count'],
                        'total' => $batch['total_count'],
                        'success_count' => $batch['success_count'] + $successCount,
                        'fail_count' => $batch['fail_count'] + $failCount,
                        'results' => $highPerformance ? [] : $results
                    ]
                ]);
            }
            
            return json([
                'code' => 1,
                'msg' => '处理中',
                'data' => [
                    'finished' => false,
                    'processed' => $processedCount,
                    'total' => $batch['total_count'],
                    'success_count' => $batch['success_count'] + $successCount,
                    'fail_count' => $batch['fail_count'] + $failCount,
                    'results' => $highPerformance ? [] : $results
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '处理失败：' . $e->getMessage()]);
        }
    }

    /**
     * 撤回批次操作
     */
    public function rollbackBatch()
    {
        try {
            $batchId = input('post.batch_id/d', 0);
            
            if (empty($batchId)) {
                return json(['code' => 0, 'msg' => '缺少批次ID']);
            }
            
            // 获取批次信息
            $batch = Db::name('order_batch')->where('id', $batchId)->find();
            if (!$batch) {
                return json(['code' => 0, 'msg' => '批次不存在']);
            }
            
            if ($batch['status'] == 2) {
                return json(['code' => 0, 'msg' => '该批次已撤回']);
            }
            
            Db::startTrans();
            
            try {
                // 获取所有成功的明细
                $items = Db::name('order_batch_item')
                    ->where('batch_id', $batchId)
                    ->where('execute_status', 1)
                    ->select();
                
                $rollbackCount = 0;
                $commissionRollbackCount = 0;
                
                foreach ($items as $item) {
                    $updateData = [];
                    $needRollbackCommission = false;
                    
                    // 恢复原状态
                    if ($item['old_status'] !== null && $item['new_status'] !== null) {
                        $updateData['order_status'] = $item['old_status'];
                        
                        // 如果从已激活(4)或已结算(5)撤回，需要删除佣金记录
                        if (in_array($item['new_status'], [4, 5, '4', '5'])) {
                            $needRollbackCommission = true;
                        }
                    }
                    
                    // 恢复原备注
                    $newRemarkPayload = $this->parseRemarkPayload($item['new_remark']);
                    $oldRemarkPayload = $this->parseRemarkPayload($item['old_remark']);
                    if ($newRemarkPayload['remark'] !== null) {
                        $updateData['remark'] = $oldRemarkPayload['remark'] !== null ? $oldRemarkPayload['remark'] : '';
                    }

                    if ($newRemarkPayload['iccid'] !== null) {
                        $updateData['iccid'] = $oldRemarkPayload['iccid'] !== null ? $oldRemarkPayload['iccid'] : '';
                    }

                    if ($newRemarkPayload['puk'] !== null) {
                        $updateData['puk'] = $oldRemarkPayload['puk'] !== null ? $oldRemarkPayload['puk'] : '';
                    }
                    
                    // 恢复原生产号码
                    if ($item['new_production_number'] !== null) {
                        $updateData['production_number'] = $item['old_production_number'];
                    }
                    
                    // 恢复原物流信息
                    if ($item['new_express_company'] !== null) {
                        $updateData['express_company'] = $item['old_express_company'];
                    }
                    
                    if ($item['new_tracking_number'] !== null) {
                        $updateData['tracking_number'] = $item['old_tracking_number'];
                    }
                    
                    if (!empty($updateData)) {
                        $updateData['update_time'] = date('Y-m-d H:i:s');
                        $result = Db::name('order')->where('id', $item['order_id'])->update($updateData);
                        if ($result !== false) {
                            $rollbackCount++;
                        }
                    }
                    
                    // 撤销佣金记录
                    if ($needRollbackCommission) {
                        // 如果是已结算订单，需要先扣减余额，再标记记录为作废
                        if ($item['new_status'] == '5' || $item['new_status'] == 5) {
                            // 查询该订单的所有已结算佣金记录（有效的）
                            $balanceLogs = Db::name('agent_balance_logs')
                                ->where('order_id', $item['order_id'])
                                ->where('type', 'in')
                                ->where('status', 1)
                                ->whereIn('sub_type', ['order', 'parent', 'secret_price'])
                                ->select();
                            
                            if ($balanceLogs) {
                                // 扣减各级代理的余额，并记录撤回操作
                                foreach ($balanceLogs as $log) {
                                    if ($log['amount'] > 0) {
                                        // 获取代理当前余额
                                        $agent = Db::name('agents')->where('id', $log['agent_id'])->find();
                                        if ($agent) {
                                            $balanceBefore = $agent['balance'];
                                            $balanceAfter = $balanceBefore - $log['amount'];
                                            
                                            // 扣减余额
                                            Db::name('agents')
                                                ->where('id', $log['agent_id'])
                                                ->dec('balance', $log['amount'])
                                                ->update();
                                            
                                            // 记录撤回操作到余额变动表
                                            Db::name('agent_balance_logs')->insert([
                                                'agent_id' => $log['agent_id'],
                                                'order_id' => $item['order_id'],
                                                'order_no' => $item['order_no'],
                                                'type' => 'out',
                                                'sub_type' => 'manual',
                                                'amount' => $log['amount'],
                                                'balance_before' => $balanceBefore,
                                                'balance_after' => $balanceAfter,
                                                'remark' => '批次撤回：批次ID#' . $batchId . '，原' . $this->getSubTypeName($log['sub_type']) . '撤销',
                                                'status' => 1,
                                                'create_time' => time()
                                            ]);
                                        }
                                    }
                                    
                                    // 将原记录标记为作废
                                    Db::name('agent_balance_logs')
                                        ->where('id', $log['id'])
                                        ->update(['status' => 0]);
                                }
                            }
                        }
                        
                        // 删除或标记待结算记录为作废
                        Db::name('agent_balance_logs')
                            ->where('order_id', $item['order_id'])
                            ->where('type', 'pending')
                            ->whereIn('sub_type', ['order', 'parent', 'secret_price'])
                            ->update(['status' => 0]);
                        
                        $commissionRollbackCount++;
                    }
                    
                    // 更新明细状态为已撤回
                    Db::name('order_batch_item')->where('id', $item['id'])->update([
                        'execute_status' => 3,
                        'rollback_time' => date('Y-m-d H:i:s')
                    ]);
                }
                
                // 更新批次状态为已撤回
                Db::name('order_batch')->where('id', $batchId)->update([
                    'status' => 2, // 0=处理中, 1=已完成, 2=已撤回
                    'rollback_time' => date('Y-m-d H:i:s')
                ]);
                
                Db::commit();
                
                $msg = sprintf('撤回成功！共处理 %d 个订单', count($items));
                if ($commissionRollbackCount > 0) {
                    $msg .= sprintf('，删除 %d 个订单的佣金记录', $commissionRollbackCount);
                }
                
                return json(['code' => 1, 'msg' => $msg]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '撤回失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取批次详情
     */
    public function getBatchDetail()
    {
        try {
            $batchId = input('post.batch_id/d', 0);
            
            if (empty($batchId)) {
                return json(['code' => 0, 'msg' => '缺少批次ID']);
            }
            
            // 获取批次信息
            $batch = Db::name('order_batch')->where('id', $batchId)->find();
            if (!$batch) {
                return json(['code' => 0, 'msg' => '批次不存在']);
            }
            
            // 获取明细列表
            $items = Db::name('order_batch_item')
                ->where('batch_id', $batchId)
                ->order('id', 'asc')
                ->select();
            
            return json([
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'batch' => $batch,
                    'items' => $items
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取批次列表
     */
    public function getBatchList()
    {
        try {
            $page = input('page', 1);
            $limit = input('limit', 15);
            
            $count = Db::name('order_batch')->count();
            
            $list = Db::name('order_batch')
                ->order('id', 'desc')
                ->page($page, $limit)
                ->select()
                ->toArray();
            
            return json([
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '获取失败：' . $e->getMessage()]);
        }
    }
}

