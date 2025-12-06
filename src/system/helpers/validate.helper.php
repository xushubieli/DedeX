<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
/**
 * 验证助手
 *
 * @version        $id:validate.helper.php 2010-07-05 11:43:09 tianya $
 * @package        DedeX.Helpers
 * @license        GNU GPL v2 (/license.txt)
 */
//邮箱格式检查
if (!function_exists('CheckEmail')) {
    function CheckEmail($email)
    {
        if (!empty($email)) {
            return preg_match('/^[a-z0-9]+([\+_\-\.]?[a-z0-9]+)*@([a-z0-9]+[\-]?[a-z0-9]+\.)+[a-z]{2,6}$/i', $email);
        }
        return FALSE;
    }
}
?>