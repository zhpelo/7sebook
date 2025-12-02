<?php

/**
 * 公共函数库
 * 包含前台和后台共用的函数和初始化代码
 */

// 确保 config.php 已加载
require_once __DIR__ . '/config.php';
if (!file_exists(__DIR__ . '/config.php')) {
    die('找不到 数据库 配置文件 config.php');
}

if (defined('DEBUG') && DEBUG === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (defined('TIMEZONE')) {
    date_default_timezone_set(TIMEZONE);
} else {
    // 设置时区为中国上海
    date_default_timezone_set('Asia/Shanghai');
}

$pdo = get_db_connection();
// 检查数据库连接
if (!$pdo) {
    die('数据库连接失败');
}
// ============================================
// 数据库连接
// ============================================

/**
 * 获取数据库连接
 * @return PDO|null 返回PDO连接对象，失败返回null
 */
function get_db_connection()
{
    global $db_host, $db_name, $db_user, $db_password, $db_charset;

    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('数据库连接失败: ' . $e->getMessage() . '，请检查数据库配置');
            return null;
        }
    }

    return $pdo;
}


// ============================================
// 用户认证相关函数
// ============================================

/**
 * 检查用户是否已登录
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * 获取当前登录用户的ID
 */
function get_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * 获取当前登录用户的角色
 */
function get_user_role()
{
    return $_SESSION['user_role'] ?? 'guest';
}

/**
 * 检查是否为管理用户
 */
function is_admin()
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 检查是否已登录且为管理用户
 */
function is_admin_logged_in()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * 设置用户登录状态
 * @param array $user 用户信息数组（包含 id, username, role）
 */
function set_user_session($user)
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';

    // 检查是否为管理用户，设置管理登录状态
    if (isset($user['role']) && $user['role'] === 'admin') {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $_SESSION['admin_logged_in'] = false;
    }
}

/**
 * 清除用户登录状态
 */
function clear_user_session()
{
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['user_role']);
    unset($_SESSION['admin_logged_in']);
}

/**
 * 验证用户登录
 * @param string $username 用户名
 * @param string $password 密码
 * @return array|false 成功返回用户信息数组，失败返回false
 */
function verify_user_login($username, $password)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }

    return false;
}

// ============================================
// 用户相关数据库操作
// ============================================

/**
 * 获取客户端真实IP地址
 */
function get_client_ip()
{
    $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            // 验证IP地址格式
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * 检查用户今日下载次数（带IP限制）
 * 使用更严格的时间检查，避免时区问题
 */
function check_download_limit($user_id, $ip_address = null)
{
    global $pdo;

    if ($ip_address === null) {
        $ip_address = get_client_ip();
    }

    // 使用更精确的时间范围，避免时区边界问题
    // 使用服务器本地时区的当前日期
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');

    // 检查用户ID和IP地址的今日下载次数
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM download_logs 
        WHERE (
            (user_id = :user_id AND download_time >= :today_start AND download_time <= :today_end)
            OR 
            (ip_address = :ip_address AND download_time >= :today_start AND download_time <= :today_end)
        )
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'ip_address' => $ip_address,
        'today_start' => $today_start,
        'today_end' => $today_end
    ]);
    $download_count = $stmt->fetchColumn();

    return $download_count < 5;
}

/**
 * 尝试记录下载日志（原子操作，带锁）
 * 如果超过限制则返回false，否则返回true
 */
function try_log_download($user_id, $book_id, $ip_address = null)
{
    global $pdo;

    if ($ip_address === null) {
        $ip_address = get_client_ip();
    }

    try {
        // 开始事务
        $pdo->beginTransaction();

        // 使用行锁，防止并发
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');

        // 锁定相关行并检查下载次数
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM download_logs 
            WHERE (
                (user_id = :user_id AND download_time >= :today_start AND download_time <= :today_end)
                OR 
                (ip_address = :ip_address AND download_time >= :today_start AND download_time <= :today_end)
            )
            FOR UPDATE
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'today_start' => $today_start,
            'today_end' => $today_end
        ]);
        $download_count = $stmt->fetchColumn();

        // 如果已超过限制，回滚并返回false
        if ($download_count >= 5) {
            $pdo->rollBack();
            return false;
        }

        // 记录下载日志（尝试插入IP字段，如果不存在则忽略）
        $current_time = date('Y-m-d H:i:s');

        // 先尝试带IP字段的插入（如果表结构已更新）
        try {
            $stmt = $pdo->prepare("
                INSERT INTO download_logs (user_id, book_id, download_time, ip_address) 
                VALUES (:user_id, :book_id, :download_time, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'book_id' => $book_id,
                'download_time' => $current_time,
                'ip_address' => $ip_address
            ]);
        } catch (PDOException $e) {
            // 如果IP字段不存在，使用旧的插入方式
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $pdo->prepare("
                    INSERT INTO download_logs (user_id, book_id, download_time) 
                    VALUES (:user_id, :book_id, :download_time)
                ");
                $stmt->execute([
                    'user_id' => $user_id,
                    'book_id' => $book_id,
                    'download_time' => $current_time
                ]);
            } else {
                throw $e;
            }
        }

        // 提交事务
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // 发生错误，回滚
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('下载日志记录失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 记录下载日志（保持向后兼容）
 * @deprecated 建议使用 try_log_download 代替
 */
function log_download($user_id, $book_id)
{
    $ip_address = get_client_ip();
    $current_time = date('Y-m-d H:i:s');
    global $pdo;

    try {
        // 尝试带IP字段的插入
        $stmt = $pdo->prepare("
            INSERT INTO download_logs (user_id, book_id, download_time, ip_address) 
            VALUES (:user_id, :book_id, :download_time, :ip_address)
        ");
        return $stmt->execute([
            'user_id' => $user_id,
            'book_id' => $book_id,
            'download_time' => $current_time,
            'ip_address' => $ip_address
        ]);
    } catch (PDOException $e) {
        // 如果IP字段不存在，使用旧的插入方式
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            $stmt = $pdo->prepare("
                INSERT INTO download_logs (user_id, book_id, download_time) 
                VALUES (:user_id, :book_id, :download_time)
            ");
            return $stmt->execute([
                'user_id' => $user_id,
                'book_id' => $book_id,
                'download_time' => $current_time
            ]);
        }
        throw $e;
    }
}

/**
 * 获取用户今日下载次数（考虑IP限制）
 */
function get_user_download_count()
{
    if (!is_logged_in()) {
        return 0;
    }

    global $pdo;

    $user_id = get_user_id();
    $ip_address = get_client_ip();

    // 使用更精确的时间范围
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');

    // 计算用户ID和IP地址的今日下载次数（取较大值）
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM download_logs 
        WHERE user_id = :user_id 
        AND download_time >= :today_start 
        AND download_time <= :today_end
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'today_start' => $today_start,
        'today_end' => $today_end
    ]);
    $user_count = $stmt->fetchColumn();

    // 尝试检查IP地址的下载次数（如果表有ip_address字段）
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM download_logs 
            WHERE ip_address = :ip_address 
            AND download_time >= :today_start 
            AND download_time <= :today_end
        ");
        $stmt->execute([
            'ip_address' => $ip_address,
            'today_start' => $today_start,
            'today_end' => $today_end
        ]);
        $ip_count = $stmt->fetchColumn();
        // 返回两者中的较大值
        return max($user_count, $ip_count);
    } catch (PDOException $e) {
        // 如果IP字段不存在，只返回用户计数
        return $user_count;
    }
}

/**
 * 获取用户信息
 */
function get_user_info($user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新用户密码
 */
function update_user_password($user_id, $new_password)
{
    global $pdo;

    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    return $stmt->execute([$password_hash, $user_id]);
}

/**
 * 验证用户名格式
 * 用户名格式要求：
 * - 长度 4-20
 * - 只允许字母、数字、下划线、连字符
 * - 以字母开头
 * - 不能包含连续特殊字符
 * @param string $username 用户名
 * @return array 返回 ['valid' => bool, 'error' => string]
 */
function validate_username($username)
{
    // 检查长度
    $len = mb_strlen($username);
    if ($len < 4 || $len > 20) {
        return ['valid' => false, 'error' => '用户名长度必须在4-20个字符之间'];
    }

    // 检查是否以字母开头
    if (!preg_match('/^[a-zA-Z]/', $username)) {
        return ['valid' => false, 'error' => '用户名必须以字母开头'];
    }

    // 检查是否只包含字母、数字、下划线、连字符
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['valid' => false, 'error' => '用户名只能包含字母、数字、下划线和连字符'];
    }

    // 检查是否包含连续特殊字符（下划线或连字符连续出现）
    if (preg_match('/[_-]{2,}/', $username)) {
        return ['valid' => false, 'error' => '用户名不能包含连续的特殊字符（下划线或连字符）'];
    }

    return ['valid' => true, 'error' => ''];
}

/**
 * 验证密码格式
 * 密码格式要求：
 * - 长度 6-32 位
 * @param string $password 密码
 * @return array 返回 ['valid' => bool, 'error' => string]
 */
function validate_password($password)
{
    $len = strlen($password);
    if ($len < 6) {
        return ['valid' => false, 'error' => '密码长度至少需要6个字符'];
    }
    if ($len > 32) {
        return ['valid' => false, 'error' => '密码长度不能超过32个字符'];
    }

    return ['valid' => true, 'error' => ''];
}

/**
 * 更新用户邮箱（自动转为小写）
 */
function update_user_email($user_id, $new_email)
{
    global $pdo;

    // 统一转为小写
    $new_email = strtolower(trim($new_email));

    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    return $stmt->execute([$new_email, $user_id]);
}

/**
 * 记录搜索历史
 * @param string $query 搜索关键词
 * @param string $type 搜索类型
 * @param int $results_count 搜索结果数量
 * @param int|null $user_id 用户ID（如果已登录）
 * @param string|null $ip_address IP地址
 */
function log_search_history($query, $type = 'all', $results_count = 0, $user_id = null, $ip_address = null)
{
    global $pdo;

    if (empty($query)) {
        return false;
    }

    if ($ip_address === null) {
        $ip_address = get_client_ip();
    }

    // 如果用户已登录，使用用户ID
    if ($user_id === null && is_logged_in()) {
        $user_id = get_user_id();
    }

    try {
        // 尝试插入带ip_address字段的记录
        $stmt = $pdo->prepare("
            INSERT INTO search_history (user_id, search_query, search_type, results_count, ip_address, search_time) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$user_id, $query, $type, $results_count, $ip_address]);
    } catch (PDOException $e) {
        // 如果ip_address字段不存在，使用旧的插入方式
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            $stmt = $pdo->prepare("
                INSERT INTO search_history (user_id, search_query, search_type, results_count, search_time) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$user_id, $query, $type, $results_count]);
        }
        throw $e;
    }
}

/**
 * 获取热门搜索关键词（按搜索次数排序）
 * @param int $limit 返回数量，默认10
 * @return array
 */
function get_popular_searches($limit = 10)
{
    global $pdo;
    try {
        // 获取热门搜索关键词统计
        $stmt = $pdo->prepare("
       SELECT 
           search_query,
           COUNT(*) as search_count,
           COUNT(DISTINCT user_id) as user_count,
           COUNT(DISTINCT ip_address) as ip_count
       FROM search_history
       WHERE search_query != ''
       GROUP BY search_query
       ORDER BY search_count DESC
       LIMIT 10
   ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('获取热门搜索失败: ' . $e->getMessage());
        return [];
    }
}

/**
 * 获取下载排行（按下载次数排序）
 * @param int $limit 返回数量，默认20
 * @return array
 */
function get_download_ranking($limit = 20)
{
    global $pdo;

    try {
         // 获取累计下载最高的10本书
         $stmt = $pdo->prepare("
         SELECT 
             b.*,
             COUNT(dl.id) as download_count
         FROM books b
         LEFT JOIN download_logs dl ON b.id = dl.book_id
         GROUP BY b.id, b.title, b.author
         ORDER BY download_count DESC
         LIMIT 10
     ");
     $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('获取下载排行失败: ' . $e->getMessage());
        return [];
    }
}

// ============================================
// 工具函数
// ============================================

/**
 * 将字节大小转换为人类可读的格式
 */
function format_file_size($bytes)
{
    if ($bytes === null || $bytes === '') {
        return '未知';
    }

    $bytes = floatval($bytes);
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $i < count($units); $i++) {
        if ($bytes < 1024) {
            return round($bytes, 2) . ' ' . $units[$i];
        }
        $bytes /= 1024;
    }

    return round($bytes, 2) . ' PB';
}

/**
 * 格式化作者信息，将多个作者分割并添加搜索链接
 */
function format_authors($authors)
{
    if (empty($authors)) {
        return '佚名';
    }

    // 使用多种分隔符分割作者名称
    $author_list = preg_split('/(;|,|；|，|、)/', $authors);
    $formatted_authors = [];

    foreach ($author_list as $author) {
        $author = trim($author);
        if (!empty($author)) {
            $formatted_authors[] = '<a href="?q=' . urlencode($author) . '&type=author" class="text-blue-600 hover:text-blue-800 hover:underline">' . htmlspecialchars($author) . '</a>';
        }
    }

    return implode(', ', $formatted_authors);
}

/**
 * 高亮匹配文本
 */
function highlight_matches($text, $query)
{
    if (!$query || !$text) {
        return $text;
    }

    $keywords = array_filter(explode(' ', $query), function ($k) {
        return strlen($k) > 0;
    });
    $result = $text;

    foreach ($keywords as $keyword) {
        if (strlen($keyword) > 0) {
            $escaped_keyword = preg_quote($keyword, '/');
            $result = preg_replace('/(' . $escaped_keyword . ')/i', '<span class="bg-yellow-200">$1</span>', $result);
        }
    }

    return $result;
}

/**
 * 获取当前时间（格式：Y-m-d H:i:s）
 */
function get_current_time()
{
    return date('Y-m-d H:i:s');
}

// ============================================
// 搜索相关函数
// ============================================

/**
 * 构建搜索条件
 */
function build_search_conditions($type, $query, $format = '')
{
    $where_parts = [];
    $relevance_parts = [];
    $params = [];

    // 将查询字符串按空格分割为关键词
    $keywords = array_filter(explode(' ', $query), function ($k) {
        return strlen(trim($k)) > 0;
    });

    foreach ($keywords as $idx => $keyword) {
        $param_name = ':keyword' . $idx;
        $params[$param_name] = '%' . $keyword . '%';

        $field_conditions = [];
        $relevance_expressions = [];

        switch ($type) {
            case 'title':
                $field_conditions[] = "b.title LIKE :keyword{$idx}";
                $relevance_expressions[] = "CASE WHEN b.title LIKE :keyword{$idx} THEN 10 ELSE 0 END";
                break;
            case 'author':
                $field_conditions[] = "b.author LIKE :keyword{$idx}";
                $relevance_expressions[] = "CASE WHEN b.author LIKE :keyword{$idx} THEN 8 ELSE 0 END";
                break;
            case 'description':
                $field_conditions[] = "b.description LIKE :keyword{$idx}";
                $relevance_expressions[] = "CASE WHEN b.description LIKE :keyword{$idx} THEN 5 ELSE 0 END";
                break;
            default:
                $field_conditions[] = "(b.title LIKE :keyword{$idx} OR b.author LIKE :keyword{$idx} OR b.description LIKE :keyword{$idx})";
                $relevance_expressions[] = "
                    CASE 
                        WHEN b.title LIKE :keyword{$idx} THEN 10
                        WHEN b.author LIKE :keyword{$idx} THEN 8
                        WHEN b.description LIKE :keyword{$idx} THEN 5
                        ELSE 0
                    END";
        }

        $where_parts[] = '(' . implode(' OR ', $field_conditions) . ')';
        $relevance_parts[] = implode(' + ', $relevance_expressions);
    }

    // 如果指定了文件格式，则添加格式筛选条件
    if (!empty($format)) {
        $where_parts[] = "b.file_format = :format";
        $params[':format'] = $format;
    }

    $where_clause = implode(' AND ', $where_parts);
    $relevance_clause = implode(' + ', $relevance_parts);

    return [
        'where' => $where_clause,
        'relevance' => $relevance_clause,
        'params' => $params
    ];
}

/**
 * 统一的分页URL构建函数
 * @param int $new_page 目标页码
 * @param array $base_params 基础参数数组（如 ['action' => 'books'] 或 ['q' => 'test', 'type' => 'all']）
 * @param array $preserve_params 需要保留的GET参数列表（如 ['search', 'format']），为空则保留所有非page参数
 * @return string 分页URL
 */
function build_page_url($new_page, $base_params = [], $preserve_params = [])
{
    // 合并基础参数
    $params = array_merge($base_params, ['page' => $new_page]);

    // 处理需要保留的参数
    if (empty($preserve_params)) {
        // 如果未指定，保留所有非page参数
        foreach ($_GET as $key => $value) {
            if ($key !== 'page' && !isset($base_params[$key])) {
                $params[$key] = $value;
            }
        }
    } else {
        // 只保留指定的参数
        foreach ($preserve_params as $key) {
            if (isset($_GET[$key]) && !isset($base_params[$key])) {
                $params[$key] = $_GET[$key];
            }
        }
    }

    // 过滤空值（但保留0和'0'）
    $params = array_filter($params, function ($value) {
        return $value !== '' && $value !== null;
    });

    return '?' . http_build_query($params);
}

/**
 * 构建分页URL（前台搜索使用）- 保持向后兼容
 */
function build_search_page_url($query, $type, $new_page)
{
    $base_params = array_filter([
        'q' => $query,
        'type' => $type,
        'format' => $_GET['format'] ?? ''
    ], function ($value) {
        return !empty($value) || $value === 0;
    });

    return build_page_url($new_page, $base_params);
}

/**
 * 构建分页URL（后台管理使用）- 保持向后兼容
 */
function build_admin_page_url($action, $new_page, $additional_params = [])
{
    $base_params = array_merge(['action' => $action], $additional_params);
    return build_page_url($new_page, $base_params);
}

/**
 * 渲染分页导航HTML
 * @param int $current_page 当前页码
 * @param int $total_pages 总页数
 * @param int $total_records 总记录数
 * @param int $limit 每页记录数
 * @param callable|array $url_builder URL构建器，可以是函数或参数数组
 *   - 如果是函数：function($page) { return '?page=' . $page; }
 *   - 如果是数组：['base_params' => [...], 'preserve_params' => [...]]
 * @param array $options 配置选项
 *   - 'show_info' => true/false 是否显示分页信息（默认true）
 *   - 'show_jump' => true/false 是否显示跳转输入框（默认true）
 *   - 'max_buttons' => int 最多显示的分页按钮数（默认5）
 *   - 'show_first_last' => true/false 是否显示首页/末页按钮（默认true）
 *   - 'show_prev_next' => true/false 是否显示上一页/下一页按钮（默认true）
 *   - 'class_prefix' => string CSS类前缀（默认''）
 * @return string 分页HTML
 */
function render_pagination($current_page, $total_pages, $total_records = 0, $limit = 20, $url_builder = null, $options = [])
{
    // 默认配置
    $defaults = [
        'show_info' => true,
        'show_jump' => true,
        'max_buttons' => 5,
        'show_first_last' => true,
        'show_prev_next' => true,
        'class_prefix' => ''
    ];
    $options = array_merge($defaults, $options);

    // 如果只有一页或总页数为0，不显示分页
    if ($total_pages <= 1) {
        if ($options['show_info'] && $total_records > 0) {
            return '<div class="text-sm text-gray-700 text-center py-4">共 ' . number_format($total_records) . ' 条记录</div>';
        }
        return '';
    }

    // 确定URL构建方式
    if ($url_builder === null) {
        // 默认使用统一的分页URL构建函数
        $url_builder = function ($page) {
            return build_page_url($page);
        };
    } elseif (is_array($url_builder)) {
        // 如果是数组，使用统一的分页URL构建函数
        $base_params = $url_builder['base_params'] ?? [];
        $preserve_params = $url_builder['preserve_params'] ?? [];
        $url_builder = function ($page) use ($base_params, $preserve_params) {
            return build_page_url($page, $base_params, $preserve_params);
        };
    }

    // 计算显示的页码范围
    $half = floor($options['max_buttons'] / 2);
    $start_page = max(1, $current_page - $half);
    $end_page = min($total_pages, $start_page + $options['max_buttons'] - 1);

    // 如果右侧空间不足，向左调整
    if ($end_page - $start_page < $options['max_buttons'] - 1) {
        $start_page = max(1, $end_page - $options['max_buttons'] + 1);
    }

    ob_start();
?>
    <div class="pagination-container <?php echo htmlspecialchars($options['class_prefix']); ?>">
        <?php if ($options['show_info']): ?>
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-2">
                <div class="text-sm text-gray-700">
                    共 <span class="font-bold text-blue-600"><?php echo number_format($total_records); ?></span> 条记录，
                    第 <span class="font-bold"><?php echo $current_page; ?></span> / <span class="font-bold"><?php echo $total_pages; ?></span> 页
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap justify-center items-center gap-2">
            <?php if ($options['show_prev_next']): ?>
                <!-- 上一页按钮 -->
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo htmlspecialchars($url_builder($current_page - 1)); ?>"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chevron-left mr-1"></i> 上一页
                    </a>
                <?php else: ?>
                    <span class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                        <i class="fas fa-chevron-left mr-1"></i> 上一页
                    </span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($options['show_first_last'] && $start_page > 1): ?>
                <!-- 首页按钮 -->
                <a href="<?php echo htmlspecialchars($url_builder(1)); ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    1
                </a>
                <?php if ($start_page > 2): ?>
                    <span class="px-2 text-gray-500">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <!-- 页码按钮 -->
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($url_builder($i)); ?>"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($options['show_first_last'] && $end_page < $total_pages): ?>
                <!-- 末页按钮 -->
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="px-2 text-gray-500">...</span>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($url_builder($total_pages)); ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <?php echo $total_pages; ?>
                </a>
            <?php endif; ?>

            <?php if ($options['show_prev_next']): ?>
                <!-- 下一页按钮 -->
                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo htmlspecialchars($url_builder($current_page + 1)); ?>"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        下一页 <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                <?php else: ?>
                    <span class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                        下一页 <i class="fas fa-chevron-right ml-1"></i>
                    </span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($options['show_jump'] && $total_pages > $options['max_buttons']): ?>
                <!-- 跳转输入框 -->
                <?php
                $jump_id = 'pagination-jump-' . md5($url_builder(1) . $current_page);
                $jump_url_template = $url_builder(1);
                ?>
                <div class="flex items-center gap-2 ml-2">
                    <span class="text-sm text-gray-600">跳至</span>
                    <input type="number"
                        id="<?php echo $jump_id; ?>"
                        min="1"
                        max="<?php echo $total_pages; ?>"
                        value="<?php echo $current_page; ?>"
                        class="w-16 px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button"
                        onclick="paginationJump('<?php echo $jump_id; ?>', <?php echo $total_pages; ?>, '<?php echo htmlspecialchars($jump_url_template, ENT_QUOTES); ?>')"
                        class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        确定
                    </button>
                </div>
                <script>
                    (function() {
                        if (typeof window.paginationJump === 'undefined') {
                            window.paginationJump = function(inputId, maxPage, urlTemplate) {
                                const input = document.getElementById(inputId);
                                if (!input) return;
                                const page = parseInt(input.value);
                                if (page >= 1 && page <= maxPage) {
                                    // 替换URL中的页码，如果URL中没有page参数则添加
                                    let url = urlTemplate;
                                    if (url.includes('page=')) {
                                        url = url.replace(/page=\d+/, 'page=' + page);
                                    } else {
                                        url += (url.includes('?') ? '&' : '?') + 'page=' + page;
                                    }
                                    window.location.href = url;
                                } else {
                                    alert('请输入 1 到 ' + maxPage + ' 之间的页码');
                                    input.focus();
                                }
                            };
                        }
                        // 支持回车键跳转
                        const input = document.getElementById('<?php echo $jump_id; ?>');
                        if (input) {
                            input.addEventListener('keypress', function(e) {
                                if (e.key === 'Enter') {
                                    window.paginationJump('<?php echo $jump_id; ?>', <?php echo $total_pages; ?>, '<?php echo htmlspecialchars($jump_url_template, ENT_QUOTES); ?>');
                                }
                            });
                        }
                    })();
                </script>
            <?php endif; ?>
        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * 检查是否需要登录查看更多结果
 */
function needs_login_for_more_results($total, $page, $limit)
{
    $total_pages = ceil($total / $limit);
    return !is_logged_in() && $total_pages > 1 && $page === 1;
}

// ============================================
// SEO相关函数
// ============================================

/**
 * 生成书籍结构化数据
 */
function generate_book_structured_data($book)
{
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Book',
        'name' => $book['title'],
        'author' => explode(',', $book['author']),
        'description' => $book['description'],
        'datePublished' => $book['published_at'],
        'publisher' => $book['publisher'],
        'inLanguage' => $book['languages'],
        'isbn' => $book['identifiers']
    ];

    // 移除空值
    $data = array_filter($data, function ($value) {
        return !empty($value) && $value !== '暂无资料';
    });

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * 生成面包屑结构化数据
 */
function generate_breadcrumb_structured_data($action, $book = null)
{
    $items = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => '首页',
            'item' => 'https://' . $_SERVER['HTTP_HOST'] . '/'
        ]
    ];

    if ($action === 'detail' && $book) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $book['title'],
            'item' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        ];
    } else if (!empty($_GET['q'])) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => '搜索结果',
            'item' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        ];
    }

    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// ============================================
// 常量定义
// ============================================

/**
 * 支持的文件格式列表
 */
define('SUPPORTED_EXTS', [
    'azw',
    'azw1',
    'azw3',
    'azw4',
    'cb7',
    'cbc',
    'cbr',
    'cbz',
    'chm',
    'docx',
    'epub',
    'fb2',
    'fbz',
    'html',
    'htmlz',
    'imp',
    'kepub',
    'lit',
    'lrf',
    'lrx',
    'mobi',
    'odt',
    'oebzip',
    'opf',
    'pdb',
    'pdf',
    'pml',
    'pmlz',
    'pobi',
    'prc',
    'rar',
    'rb',
    'rtf',
    'snb',
    'tpz',
    'txt',
    'txtz',
    'updb',
    'zip'
]);

// 兼容旧代码的变量
$supported_exts = SUPPORTED_EXTS;

//清理或转换字符串为合法 UTF-8
function clean_to_utf8($str, $fromEncoding = 'auto')
{
    // 如果已经是合法 UTF-8，直接返回
    if (mb_check_encoding($str, 'UTF-8')) {
        return $str;
    }

    // 尝试检测原始编码（可指定来源编码，如 'GBK', 'ISO-8859-1' 等）
    $encoding = mb_detect_encoding($str, ['UTF-8', 'GBK', 'GB2312', 'ASCII', 'ISO-8859-1'], true);

    if ($encoding && $encoding !== 'UTF-8') {
        $str = mb_convert_encoding($str, 'UTF-8', $encoding);
    } else {
        // 如果无法识别，强制转为 UTF-8 并忽略非法字符
        $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }

    // 最后一步：移除无法表示的字符（保险起见）
    return mb_encode_numericentity($str, [0x0080, 0xffff, 0, 0xffff], 'UTF-8');
}

/**
 * 渲染书籍列表（支持方格和列表两种布局）
 * @param array $books 书籍数组
 * @param string $layout 布局类型：'grid' 或 'list'，默认为 'grid'
 * @param string $query 搜索关键词（用于高亮显示）
 * @param string $container_id 容器ID（用于布局切换）
 */
function render_books_list($books, $layout = 'grid', $query = '', $container_id = 'booksList')
{
    if (empty($books)) {
        return '';
    }

    $layout_class = $layout === 'list' ? 'list' : 'grid';
    $grid_class = $layout === 'list' ? 'grid-cols-1' : 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5';

    ob_start();
?>
    <div id="<?php echo htmlspecialchars($container_id); ?>" class="books-container" data-layout="<?php echo htmlspecialchars($layout); ?>">
        <?php if ($layout === 'list'): ?>
            <!-- 列表布局 -->
            <div class="space-y-4">
                <?php foreach ($books as $book): ?>
                    <a href="?action=detail&id=<?php echo $book['id']; ?>">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 flex mb-6">
                            <div class="w-32 sm:w-40 flex-shrink-0">
                                <div class="bg-gray-100 flex items-center justify-center h-full">
                                    <?php if ($book['cover_url']): ?>
                                        <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="text-gray-400 text-center p-2">
                                            <?php
                                            // 简单文件格式到FontAwesome图标类的映射
                                            $format_icon_map = [
                                                'pdf' => 'fa-file-pdf',
                                                'epub' => 'fa-file-lines',
                                                'mobi' => 'fa-file-lines',
                                                'azw3' => 'fa-file-lines',
                                                'txt' => 'fa-file-alt',
                                                'doc' => 'fa-file-word',
                                                'docx' => 'fa-file-word',
                                                'xls' => 'fa-file-excel',
                                                'xlsx' => 'fa-file-excel',
                                                'ppt' => 'fa-file-powerpoint',
                                                'pptx' => 'fa-file-powerpoint',
                                                'zip' => 'fa-file-archive',
                                                'rar' => 'fa-file-archive',
                                            ];
                                            $icon = 'fa-book';
                                            $fmt = strtolower($book['file_format'] ?? '');
                                            if ($fmt && isset($format_icon_map[$fmt])) {
                                                $icon = $format_icon_map[$fmt];
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon; ?> text-2xl mb-1"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-1 p-4 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-lg mb-2"><?php echo empty($query) ? htmlspecialchars($book['title']) : highlight_matches(htmlspecialchars($book['title']), $query); ?></h3>
                                    <?php if ($book['author']): ?>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-user text-sm mr-2"></i>
                                            <?php echo empty($query) ? htmlspecialchars($book['author']) : highlight_matches(htmlspecialchars($book['author']), $query); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($book['description'])): ?>
                                        <p class="text-gray-500 text-sm mb-2 line-clamp-4"><?php echo htmlspecialchars($book['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex gap-2 items-center text-xs">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($book['file_format']); ?></span>
                                        <span class="text-gray-500"><?php echo format_file_size($book['file_size']); ?></span>
                                    </div>
                                    <?php if (isset($book['relevance_score']) && !empty($query)): ?>
                                        <div class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                            相关度: <?php echo round($book['relevance_score'], 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- 方格布局 -->
            <div class="grid <?php echo $grid_class; ?> gap-4">
                <?php foreach ($books as $book): ?>
                    <a href="?action=detail&id=<?php echo $book['id']; ?>">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <div class="bg-gray-100 flex items-center justify-center aspect-[2/3]">
                                <?php if ($book['cover_url']): ?>
                                    <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="text-gray-400 text-center p-4">
                                        <i class="fas fa-book text-4xl mb-2"></i>
                                        <div class="text-sm"><?php echo htmlspecialchars($book['title']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-2 line-clamp-2"><?php echo empty($query) ? htmlspecialchars($book['title']) : highlight_matches(htmlspecialchars($book['title']), $query); ?></h3>
                                <?php if (!empty($book['description'])): ?>
                                    <p class="text-gray-500 text-sm line-clamp-2 mb-2"><?php echo htmlspecialchars(substr($book['description'], 0, 150)); ?><?php echo strlen($book['description']) > 150 ? '...' : ''; ?></p>
                                <?php endif; ?>

                                <?php if ($book['author']): ?>
                                    <p class="text-gray-600 mb-2 line-clamp-2">
                                        <i class="fas fa-user text-sm mr-2"></i>
                                        <?php echo empty($query) ? htmlspecialchars($book['author']) : highlight_matches(htmlspecialchars($book['author']), $query); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-2 flex justify-between items-center text-xs">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($book['file_format']); ?></span>
                                    <span class="text-gray-500"><?php echo format_file_size($book['file_size']); ?></span>
                                </div>
                                <?php if (isset($book['relevance_score']) && !empty($query)): ?>
                                    <div class="mt-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded inline-block">
                                        相关度: <?php echo round($book['relevance_score'], 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}
