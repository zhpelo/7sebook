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

if(defined('TIMEZONE')) {
    date_default_timezone_set(TIMEZONE);
}else{
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
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    
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
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    
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
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    
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
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    
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
    
    $pdo = get_db_connection();
    if (!$pdo) {
        return 0;
    }
    
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
    $pdo = get_db_connection();
    if (!$pdo) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新用户密码
 */
function update_user_password($user_id, $new_password)
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    return $stmt->execute([$password_hash, $user_id]);
}

/**
 * 更新用户邮箱
 */
function update_user_email($user_id, $new_email)
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }
    
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    return $stmt->execute([$user_id, $new_email]);
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
 * 构建分页URL（前台搜索使用）
 */
function build_search_page_url($query, $type, $new_page)
{
    // 获取当前的format参数
    $format = $_GET['format'] ?? '';
    
    // 构建参数数组
    $params = array_filter([
        'q' => $query,
        'type' => $type,
        'format' => $format,
        'page' => $new_page
    ], function ($value) {
        return !empty($value) || $value === 0;
    });
    
    return '?' . http_build_query($params);
}

/**
 * 构建分页URL（后台管理使用）
 */
function build_admin_page_url($action, $new_page, $additional_params = [])
{
    $params = array_merge([
        'action' => $action,
        'page' => $new_page
    ], $additional_params);
    
    return '?' . http_build_query($params);
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
    'azw', 'azw1', 'azw3', 'azw4', 'cb7', 'cbc', 'cbr', 'cbz', 'chm',
    'docx', 'epub', 'fb2', 'fbz', 'html', 'htmlz', 'imp', 'kepub',
    'lit', 'lrf', 'lrx', 'mobi', 'odt', 'oebzip', 'opf', 'pdb', 'pdf',
    'pml', 'pmlz', 'pobi', 'prc', 'rar', 'rb', 'rtf', 'snb', 'tpz',
    'txt', 'txtz', 'updb', 'zip'
]);

// 兼容旧代码的变量
$supported_exts = SUPPORTED_EXTS;

//清理或转换字符串为合法 UTF-8
function clean_to_utf8($str, $fromEncoding = 'auto') {
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
