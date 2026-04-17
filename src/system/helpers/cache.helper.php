<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
/**
 * 缓存助手，支持文件和dedex cache
 *
 * @version        $id:cache.helper.php 10:46 2011-3-2 tianya $
 * @package        DedeX.Helpers
 * @license        GNU GPL v2 (/license.txt)
 */
/**
 *  读缓存
 *
 * @access    public
 * @param     string  $prefix  前缀
 * @param     string  $key  键
 * @return    string
 */
if (!function_exists('GetCache')) {
    function GetCache($prefix, $key)
    {
        $key = md5($key);
        $key = substr($key, 0, 2).'/'.$key;
        $result = @file_get_contents(DEDEDATA."/cache/$prefix/$key.php");
        if ($result === false) {
            return false;
        }
        $result = str_replace("<?php exit('Error: Invalid! ');?>\n\r", "", $result);
        $result = @unserialize($result);
        if ($result['timeout'] != 0 && $result['timeout'] < time()) {
            return false;
        }
        return $result['data'];
    }
}
/**
 *  写缓存
 *
 * @access    public
 * @param     string  $prefix  前缀
 * @param     string  $key  键
 * @param     string  $value  值
 * @param     string  $timeout  缓存时间
 * @return    int
 */
if (!function_exists('SetCache')) {
    function SetCache($prefix, $key, $value, $timeout = 3600)
    {
        $key = md5($key);
        $key = substr($key, 0, 2).'/'.$key;
        $tmp['data'] = $value;
        $tmp['timeout'] = $timeout != 0 ? time() + (int) $timeout : 0;
        $cache_data = "<?php exit('Error: Invalid! ');?>\n\r".@serialize($tmp);
        return @PutFile(DEDEDATA."/cache/$prefix/$key.php",  $cache_data);
    }
}
/**
 *  删除缓存
 *
 * @access    public
 * @param     string  $prefix  前缀
 * @param     string  $key  键
 * @return    string
 */
if (!function_exists('DelCache')) {
    //删缓存
    function DelCache($prefix, $key)
    {
        $key = md5($key);
        $key = substr($key, 0, 2).'/'.$key;
        return @unlink(DEDEDATA."/cache/$prefix/$key.php");
    }
}
?>