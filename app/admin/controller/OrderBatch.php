<?php
namespace app\admin\controller;

use think\facade\Db;
use think\facade\Session;
use think\facade\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\common\helper\AuHelper;

class OrderBatch extends Base
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
            
            // 设置表头
            $sheet->setCellValue('A1', '订单号（支持本地订单号或上游订单号）');
            $sheet->setCellValue('B1', '备注');
            $sheet->setCellValue('C1', '生产号码');
            $sheet->setCellValue('D1', '物流公司');
            $sheet->setCellValue('E1', '物流单号');
            
            // 设置表头样式
            $sheet->getStyle('A1:E1')->getFont()->setBold(true);
            $sheet->getStyle('A1:E1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // 设置列宽
            $sheet->getColumnDimension('A')->setWidth(35);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(20);
            
            // 添加说明行
            $sheet->setCellValue('A2', '填写本地订单号（如 SH202410210001）或上游订单号（如 P2025111605252231760714165）');
            $sheet->getStyle('A2:E2')->getFont()->setItalic(true)->setSize(9);
            $sheet->getStyle('A2:E2')->getFont()->getColor()->setARGB('FF999999');
            
            // 添加示例数据
            $sheet->setCellValue('A3', 'SH202410210001');
            $sheet->setCellValue('B3', '备注信息示例');
            $sheet->setCellValue('C3', '13800138000');
            $sheet->setCellValue('D3', '顺丰速运');
            $sheet->setCellValue('E3', 'SF1234567890');
            
            // 输出文件
            $filename = '订单批量操作模板_' . date('YmdHis') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
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
                '备注',
                '生产号码',
                '物流公司',
                '物流单号'
            ]);
            
            // 写入说明行
            fputcsv($output, [
                '填写本地订单号（如 SH202410210001）或上游订单号（如 P2025111605252231760714165）',
                '选填',
                '选填',
                '选填',
                '选填'
            ]);
            
            // 写入示例数据
            fputcsv($output, [
                'SH202410210001',
                '备注信息示例',
                '13800138000',
                '顺丰速运',
                'SF1234567890'
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
                        'new_remark' => isset($data[1]) ? trim($data[1]) : '',
                        'new_production_number' => isset($data[2]) ? trim($data[2]) : '',
                        'new_express_company' => isset($data[3]) ? trim($data[3]) : '',
                        'new_tracking_number' => isset($data[4]) ? trim($data[4]) : '',
                        'customer_name' => $order['customer_name'],
                        'phone' => $order['phone'],
                        'product_name' => $order['product_name'],
                    ];
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
                    
                    $items[] = [
                        'order_no' => $order['order_no'], // 使用本地订单号
                        'up_order_no' => $order['up_order_no'], // 上游订单号
                        'input_order_no' => $orderNo, // 用户输入的订单号
                        'order_id' => $order['id'],
                        'current_status' => $order['order_status'],
                        'current_remark' => $order['remark'],
                        'current_production_number' => $order['production_number'],
                        'current_express_company' => $order['express_company'],
                        'current_tracking_number' => $order['tracking_number'],
                        'new_remark' => trim($sheet->getCell('B' . $row)->getValue()),
                        'new_production_number' => trim($sheet->getCell('C' . $row)->getValue()),
                        'new_express_company' => trim($sheet->getCell('D' . $row)->getValue()),
                        'new_tracking_number' => trim($sheet->getCell('E' . $row)->getValue()),
                        'customer_name' => $order['customer_name'],
                        'phone' => $order['phone'],
                        'product_name' => $order['product_name'],
                    ];
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
                    'errors' => $errors
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '导入失败：' . $e->getMessage()]);
        }
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
                $batchItems[] = [
                    'order_id' => $item['order_id'],
                    'order_no' => $item['order_no'],
                    'old_status' => $item['current_status'],
                    'new_status' => $operationType === 'status' ? $targetStatus : null,
                    'old_remark' => $item['current_remark'],
                    'new_remark' => !empty($item['new_remark']) ? $item['new_remark'] : null,
                    'old_production_number' => $item['current_production_number'],
                    'new_production_number' => !empty($item['new_production_number']) ? $item['new_production_number'] : null,
                    'old_express_company' => $item['current_express_company'],
                    'new_express_company' => !empty($item['new_express_company']) ? $item['new_express_company'] : null,
                    'old_tracking_number' => $item['current_tracking_number'],
                    'new_tracking_number' => !empty($item['new_tracking_number']) ? $item['new_tracking_number'] : null,
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
                    if ($batch['operation_type'] === 'status' && !empty($item['new_status'])) {
                        $updateData['order_status'] = $item['new_status'];
                    }
                    
                    if (!empty($item['new_remark'])) {
                        $updateData['remark'] = $item['new_remark'];
                    }
                    
                    if (!empty($item['new_production_number'])) {
                        $updateData['production_number'] = $item['new_production_number'];
                    }
                    
                    if (!empty($item['new_express_company'])) {
                        $updateData['express_company'] = $item['new_express_company'];
                    }
                    
                    if (!empty($item['new_tracking_number'])) {
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
                    if ($item['new_remark'] !== null) {
                        $updateData['remark'] = $item['old_remark'];
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
