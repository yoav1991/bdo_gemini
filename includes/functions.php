<?php
/**
 * 格式化货币, 将大数字转换为以'万'或'亿'为单位的字符串
 * @param int $number
 * @return string
 */
function format_silver($number) {
    if (!is_numeric($number)) {
        return '0';
    }
    if ($number >= 100000000) {
        return rtrim(rtrim(sprintf('%.4f', $number / 100000000), '0'), '.') . '亿';
    }
    if ($number >= 10000) {
        return rtrim(rtrim(sprintf('%.4f', $number / 10000), '0'), '.') . '万';
    }
    return number_format($number);
}

/**
 * 检查用户是否登录
 * @param string|null $redirect_url 如果未登录, 跳转到指定URL
 */
function check_login($redirect_url = null) {
    if (!isset($_SESSION['user_id'])) {
        if ($redirect_url) {
            header("Location: " . $redirect_url);
            exit();
        }
        return false;
    }
    return true;
}

/**
 * 检查是否为管理员
 * @param string|null $redirect_url 如果不是管理员, 跳转到指定URL
 */
function check_admin($redirect_url = null) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        if ($redirect_url) {
            header("Location: " . $redirect_url);
            exit();
        }
        return false;
    }
    return true;
}
?>