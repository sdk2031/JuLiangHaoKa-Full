<?php
namespace app\openapi\controller;

use app\common\service\UploadService;

class Ticket extends BaseApi
{
    /**
     * 格式化时间🆕
     */
    private function formatTime($timestamp)
    {
        if (empty($timestamp)) {
            return date('Y-m-d H:i:s');
        }
        
        if (is_numeric($timestamp)) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        
        // 如果已经是格式化的时间字符串，直接返回
        if (is_string($timestamp) && strtotime($timestamp)) {
            return $timestamp;
        }
        
        return date('Y-m-d H:i:s');
    }
    
    /**
     * 获取工单状态文本
     */
    private function getStatusText($status)
    {
        $statusMap = [
            1 => '待处理',
            2 => '处理中',
            3 => '已解决',
            4 => '已关闭'
        ];
        return $statusMap[$status] ?? '待处理';
    }
    
    
    /**
     * 获取工单列表
     * 对应前端调用：GET /openapi/ticket/list
     */
    public function list()
    {
        try {
            error_log("Ticket list method called");
            
            // 检查代理身份验证
            $authResult = $this->checkAgentAuth();
            if (!$authResult['success']) {
                return $this->error($authResult['message'], 401);
            }
            
            $agentId = $authResult['agent_id'];
            
            // 获取数据库连接
            $db = $this->getDbConnection();
            error_log("Database connection established");
            
            // 获取请求参数
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
            
            // 计算偏移量
            $offset = ($page - 1) * $limit;
            
            // 构建查询条件
            $whereConditions = ["t.agent_id = ?"];
            $params = [$agentId];
            
            if ($categoryId !== '') {
                $whereConditions[] = "t.category_id = ?";
                $params[] = $categoryId;
            }
            
            if ($status !== '') {
                $whereConditions[] = "t.status = ?";
                $params[] = $status;
            }
            
            if ($keyword) {
                $whereConditions[] = "(t.title LIKE ? OR t.ticket_no LIKE ?)";
                $params[] = '%' . $keyword . '%';
                $params[] = '%' . $keyword . '%';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM tickets t WHERE " . $whereClause;
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $totalResult = $countStmt->fetch(\PDO::FETCH_ASSOC);
            $total = $totalResult['total'];
            
            // 查询列表数据
            $sql = "SELECT t.id, t.ticket_no, t.title, t.content, t.category_id, t.status, 
                           t.create_time, t.update_time, t.reply_time,
                           tc.name as category_name
                    FROM tickets t 
                    LEFT JOIN ticket_categories tc ON t.category_id = tc.id 
                    WHERE " . $whereClause . "
                    ORDER BY 
                        CASE 
                            WHEN t.reply_time > 0 THEN t.reply_time
                            WHEN t.update_time > 0 THEN t.update_time
                            WHEN t.create_time > 0 THEN t.create_time  
                            ELSE UNIX_TIMESTAMP() - t.id 
                        END DESC,
                        t.id DESC 
                    LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $tickets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $data = [];
            
            foreach ($tickets as $item) {
                $data[] = [
                    'id' => (int)$item['id'],
                    'ticket_no' => $item['ticket_no'],
                    'title' => $item['title'],
                    'description' => strip_tags($item['content']),  // 去除HTML标签
                    'category' => $item['category_name'] ?: '未分类',
                    'category_id' => (int)$item['category_id'],
                    'status' => $this->getStatusText((int)$item['status']),
                    'status_code' => (int)$item['status'],
                    'created_at' => $this->formatTime($item['create_time']),
                    'updated_at' => $this->formatTime($item['update_time']),
                    'reply_time' => $this->formatTime($item['reply_time']),
                ];
            }
            
            error_log("Ticket list returning " . count($data) . " items for agent: " . $agentId);
            return $this->success($data, '获取成功');
            
        } catch (\Exception $e) {
            error_log("Ticket list error: " . $e->getMessage());
            error_log("Ticket list stack trace: " . $e->getTraceAsString());
            return $this->error('获取工单列表失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取工单分类列表
     * 对应前端调用：GET /openapi/ticket/categories
     */
    public function categories()
    {
        try {
            error_log("Ticket categories method called");
            
            // 获取数据库连接
            $db = $this->getDbConnection();
            
            // 查询工单分类
            $sql = "SELECT id, name, description, sort_order, status, create_time, update_time
                    FROM ticket_categories 
                    WHERE status = 1
                    ORDER BY sort_order ASC, id ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $data = [];
            
            foreach ($categories as $item) {
                $data[] = [
                    'id' => (int)$item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'] ?: '',
                    'sort_order' => (int)$item['sort_order'],
                    'status' => (int)$item['status'],
                    'created_at' => $this->formatTime($item['create_time']),
                    'updated_at' => $this->formatTime($item['update_time']),
                ];
            }
            
            error_log("Ticket categories returning " . count($data) . " items");
            return $this->success($data, '获取成功');
            
        } catch (\Exception $e) {
            error_log("Ticket categories error: " . $e->getMessage());
            return $this->error('获取工单分类失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取工单详情
     * 对应前端调用：GET /openapi/ticket/detail
     */
    public function detail()
    {
        try {
            error_log("Ticket detail method called");
            
            // 检查代理身份验证
            $authResult = $this->checkAgentAuth();
            if (!$authResult['success']) {
                return $this->error($authResult['message'], 401);
            }
            
            $agentId = $authResult['agent_id'];
            
            // 获取工单ID
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) {
                return $this->error('工单ID不能为空');
            }
            
            // 获取数据库连接
            $db = $this->getDbConnection();
            
            // 查询工单详情
            $sql = "SELECT t.id, t.ticket_no, t.title, t.content, t.category_id, t.status, 
                           t.create_time, t.update_time, t.reply_time, t.close_time,
                           tc.name as category_name
                    FROM tickets t 
                    LEFT JOIN ticket_categories tc ON t.category_id = tc.id 
                    WHERE t.id = ? AND t.agent_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$id, $agentId]);
            $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return $this->error('工单不存在或无权限访问');
            }
            
            $data = [
                'id' => (int)$ticket['id'],
                'ticket_no' => $ticket['ticket_no'],
                'title' => $ticket['title'],
                'description' => $ticket['content'],
                'category' => $ticket['category_name'] ?: '未分类',
                'category_id' => (int)$ticket['category_id'],
                'status' => $this->getStatusText((int)$ticket['status']),
                'status_code' => (int)$ticket['status'],
                'created_at' => $this->formatTime($ticket['create_time']),
                'updated_at' => $this->formatTime($ticket['update_time']),
                'reply_time' => $this->formatTime($ticket['reply_time']),
                'close_time' => $this->formatTime($ticket['close_time']),
            ];
            
            return $this->success($data, '获取成功');
            
        } catch (\Exception $e) {
            error_log("Ticket detail error: " . $e->getMessage());
            return $this->error('获取工单详情失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取工单回复列表
     * 对应前端调用：GET /openapi/ticket/replies
     */
    public function replies()
    {
        try {
            error_log("Ticket replies method called");
            
            // 检查代理身份验证
            $authResult = $this->checkAgentAuth();
            if (!$authResult['success']) {
                return $this->error($authResult['message'], 401);
            }
            
            $agentId = $authResult['agent_id'];
            
            // 获取工单ID
            $ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
            if (!$ticketId) {
                return $this->error('工单ID不能为空');
            }
            
            // 获取数据库连接
            $db = $this->getDbConnection();
            
            // 先检查工单是否属于当前用户
            $checkSql = "SELECT id FROM tickets WHERE id = ? AND agent_id = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$ticketId, $agentId]);
            if (!$checkStmt->fetch()) {
                return $this->error('工单不存在或无权限访问');
            }
            
            // 查询回复列表
            $sql = "SELECT id, ticket_id, user_type, user_id, content, attachments, is_internal, create_time
                    FROM ticket_replies 
                    WHERE ticket_id = ? AND is_internal = 0
                    ORDER BY create_time ASC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$ticketId]);
            $replies = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $data = [];
            foreach ($replies as $item) {
                $data[] = [
                    'id' => (int)$item['id'],
                    'ticket_id' => (int)$item['ticket_id'],
                    'user_type' => (int)$item['user_type'], // 1=代理商，2=管理员
                    'user_id' => (int)$item['user_id'],
                    'content' => $item['content'],
                    'attachments' => $this->decodeAttachments($item['attachments'] ?? null),
                    'created_at' => $this->formatTime($item['create_time']),
                ];
            }
            
            return $this->success($data, '获取成功');
            
        } catch (\Exception $e) {
            error_log("Ticket replies error: " . $e->getMessage());
            return $this->error('获取回复列表失败：' . $e->getMessage());
        }
    }
    
    /**
     * 回复工单
     * 对应前端调用：POST /openapi/ticket/reply
     */
    public function reply()
    {
        try {
            error_log("Ticket reply method called");
            
            // 检查代理身份验证
            $authResult = $this->checkAgentAuth();
            if (!$authResult['success']) {
                return $this->error($authResult['message'], 401);
            }
            
            $agentId = $authResult['agent_id'];
            
            // 获取POST数据
            $postData = json_decode(file_get_contents('php://input'), true);
            
            // 验证参数
            $ticketId = isset($postData['ticket_id']) ? (int)$postData['ticket_id'] : 0;
            $content = isset($postData['content']) ? trim($postData['content']) : '';
            
            if (!$ticketId) {
                return $this->error('工单ID不能为空');
            }
            
            $images = isset($postData['images']) ? $postData['images'] : [];
            if (is_string($images)) {
                $decodedImages = json_decode($images, true);
                $images = is_array($decodedImages) ? $decodedImages : [];
            }
            if (!is_array($images)) {
                $images = [];
            }
            
            if (!$content && empty($images)) {
                return $this->error('回复内容不能为空');
            }
            
            if ($content && strlen($content) > 500) {
                return $this->error('回复内容不能超过500字符');
            }
            
            // 获取数据库连接
            $db = $this->getDbConnection();
            
            // 检查工单是否存在且属于当前用户
            $checkSql = "SELECT id, status, title FROM tickets WHERE id = ? AND agent_id = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$ticketId, $agentId]);
            $ticket = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return $this->error('工单不存在或无权限操作');
            }
            
            if ($ticket['status'] == 4) {
                return $this->error('工单已关闭，无法回复');
            }
            
            // 插入回复记录
            $attachmentsJson = !empty($images) ? json_encode($this->normalizeAttachments($images), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            $insertSql = "INSERT INTO ticket_replies (ticket_id, user_type, user_id, content, attachments, is_internal, create_time) 
                         VALUES (?, 1, ?, ?, ?, 0, ?)";
            $insertStmt = $db->prepare($insertSql);
            $result = $insertStmt->execute([$ticketId, $agentId, $content, $attachmentsJson, time()]);
            
            if (!$result) {
                error_log("Insert reply failed with error info: " . print_r($insertStmt->errorInfo(), true));
                return $this->error('回复失败');
            }
            
            // 更新工单回复时间
            $updateSql = "UPDATE tickets SET reply_time = ?, update_time = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([time(), time(), $ticketId]);
            
            error_log("Ticket reply added successfully: " . $ticketId . " by agent: " . $agentId);
            return $this->success([], '回复成功');
            
        } catch (\Exception $e) {
            error_log("Ticket reply error: " . $e->getMessage());
            return $this->error('回复失败：' . $e->getMessage());
        }
    }

    /**
     * 关闭工单
     * 对应前端调用：POST /openapi/ticket/close
     */
    public function close()
    {
        try {
            error_log("Ticket close method called");
            
            // 检查代理身份验证
            $authResult = $this->checkAgentAuth();
            if (!$authResult['success']) {
                return $this->error($authResult['message'], 401);
            }
            
            $agentId = $authResult['agent_id'];
            
            // 获取POST数据
            $postData = json_decode(file_get_contents('php://input'), true);
            
            // 获取工单ID
            $ticketId = isset($postData['ticket_id']) ? (int)$postData['ticket_id'] : 0;
            
            if (!$ticketId) {
                return $this->error('工单ID不能为空');
            }
            
            // 获取数据库连接
            $db = $this->getDbConnection();
            
            // 检查工单是否存在且属于当前用户
            $checkSql = "SELECT id, status, title FROM tickets WHERE id = ? AND agent_id = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$ticketId, $agentId]);
            $ticket = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return $this->error('工单不存在或无权限操作');
            }
            
            if ($ticket['status'] == 4) {
                return $this->error('工单已关闭');
            }
            
            // 关闭工单
            $updateSql = "UPDATE tickets SET status = 4, update_time = ?, close_time = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $result = $updateStmt->execute([time(), time(), $ticketId]);
            
            if (!$result) {
                error_log("Close ticket failed with error info: " . print_r($updateStmt->errorInfo(), true));
                return $this->error('关闭工单失败');
            }
            
            error_log("Ticket closed successfully: " . $ticketId . " by agent: " . $agentId);
            return $this->success([], '工单已关闭');
            
        } catch (\Exception $e) {
            error_log("Ticket close error: " . $e->getMessage());
            return $this->error('关闭工单失败：' . $e->getMessage());
        }
    }

    /**
     * 创建工单
     * 对应前端调用：POST /openapi/ticket/create
     */
    public function create()
    {
        try {
            error_log("Ticket create method called");
            
            // 检查代理身份验证
            $authResult = $this->checkAgentAuth();
            if (!$authResult['success']) {
                return $this->error($authResult['message'], 401);
            }
            
            $agentId = $authResult['agent_id'];
            
            // 获取POST数据
            $postData = json_decode(file_get_contents('php://input'), true);
            
            // 验证参数
            $title = isset($postData['title']) ? trim($postData['title']) : '';
            $content = isset($postData['content']) ? trim($postData['content']) : '';
            $categoryId = isset($postData['category_id']) ? (int)$postData['category_id'] : 0;
            
            if (!$title) {
                return $this->error('工单标题不能为空');
            }
            
            if (strlen($title) > 100) {
                return $this->error('工单标题不能超过100字符');
            }
            
            if (!$content) {
                return $this->error('问题描述不能为空');
            }
            
            if (strlen($content) > 2000) {
                return $this->error('问题描述不能超过2000字符');
            }
            
            // 获取数据库连接
            $db = $this->getDbConnection();
            
            // 验证分类是否存在（如果提供了分类ID）
            if ($categoryId > 0) {
                $categorySql = "SELECT id FROM ticket_categories WHERE id = ? AND status = 1";
                $categoryStmt = $db->prepare($categorySql);
                $categoryStmt->execute([$categoryId]);
                if (!$categoryStmt->fetch()) {
                    return $this->error('选择的分类不存在');
                }
            }
            
            // 生成工单编号
            $ticketNo = 'T' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // 确保工单编号唯一
            $checkSql = "SELECT id FROM tickets WHERE ticket_no = ?";
            $checkStmt = $db->prepare($checkSql);
            $attempts = 0;
            do {
                if ($attempts > 0) {
                    $ticketNo = 'T' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                }
                $checkStmt->execute([$ticketNo]);
                $attempts++;
            } while ($checkStmt->fetch() && $attempts < 10);
            
            if ($attempts >= 10) {
                $ticketNo = 'T' . date('Ymd') . time() . mt_rand(10, 99);
            }
            
            // 插入工单记录
            $insertSql = "INSERT INTO tickets (ticket_no, title, content, category_id, agent_id, status, create_time, update_time) 
                         VALUES (?, ?, ?, ?, ?, 1, ?, ?)";
            $insertStmt = $db->prepare($insertSql);
            $createTime = time();
            
            $result = $insertStmt->execute([
                $ticketNo,
                $title,
                $content,
                $categoryId > 0 ? $categoryId : null,
                $agentId,
                $createTime,
                $createTime
            ]);
            
            if (!$result) {
                error_log("Insert ticket failed with error info: " . print_r($insertStmt->errorInfo(), true));
                return $this->error('创建工单失败');
            }
            
            $ticketId = $db->lastInsertId();
            
            error_log("Ticket created successfully: " . $ticketId . " by agent: " . $agentId);
            
            return $this->success([
                'id' => (int)$ticketId,
                'ticket_no' => $ticketNo,
                'title' => $title,
                'status' => '待处理',
                'status_code' => 1,
                'created_at' => $this->formatTime($createTime)
            ], '工单创建成功');
            
        } catch (\Exception $e) {
            error_log("Ticket create error: " . $e->getMessage());
            return $this->error('创建工单失败：' . $e->getMessage());
        }
    }

    /**
     * 测试API连接
     */
    public function test()
    {
        try {
            error_log("Ticket test method called");
            
            // 测试数据库连接
            $db = $this->getDbConnection();
            
            // 简单查询测试
            $stmt = $db->query("SELECT COUNT(*) as count FROM tickets");
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $this->success([
                'message' => 'API连接正常',
                'ticket_count' => $result['count'],
                'timestamp' => time(),
                'formatted_time' => $this->formatTime(time())
            ], '测试成功');
            
        } catch (\Exception $e) {
            error_log("Ticket test error: " . $e->getMessage());
            return $this->error('测试失败：' . $e->getMessage());
        }
    }

    /**
     * 上传工单图片
     * 对应前端调用：POST /openapi/ticket/uploadImage
     */
    public function uploadImage()
    {
        try {
            $authResult = $this->checkAgentAuth();
            if (!$authResult['success']) {
                return $this->error($authResult['message'], 401);
            }
            $agentId = $authResult['agent_id'];

            $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
            if (!$ticketId) {
                return $this->error('工单ID不能为空');
            }

            $db = $this->getDbConnection();
            $checkSql = "SELECT id, status FROM tickets WHERE id = ? AND agent_id = ?";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([$ticketId, $agentId]);
            $ticket = $checkStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$ticket) {
                return $this->error('工单不存在或无权限操作');
            }
            if ((int)$ticket['status'] === 4) {
                return $this->error('工单已关闭，无法上传图片');
            }

            $uploadService = new UploadService();
            $result = $uploadService->upload('file', 'ticket');
            if (($result['code'] ?? 0) != 1 || empty($result['data'])) {
                return $this->error($result['msg'] ?? '上传失败');
            }

            $data = $result['data'];
            return $this->success([
                'url' => $data['url'] ?? '',
                'path' => $data['path'] ?? '',
                'name' => $data['original_name'] ?? '',
                'size' => $data['size'] ?? 0,
            ], '上传成功');
        } catch (\Exception $e) {
            error_log("Ticket uploadImage error: " . $e->getMessage());
            return $this->error('上传失败：' . $e->getMessage());
        }
    }

    /**
     * 规范化附件数组
     */
    private function normalizeAttachments($attachments)
    {
        if (!is_array($attachments)) {
            return [];
        }
        $result = [];
        foreach ($attachments as $item) {
            if (is_string($item)) {
                $url = trim($item);
                if ($url !== '') {
                    $result[] = ['url' => $url];
                }
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $url = trim((string)($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $result[] = [
                'url' => $url,
                'path' => (string)($item['path'] ?? ''),
                'name' => (string)($item['name'] ?? ''),
                'size' => (int)($item['size'] ?? 0),
            ];
        }
        return $result;
    }

    /**
     * 反序列化附件
     */
    private function decodeAttachments($raw)
    {
        if (empty($raw)) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
?>
