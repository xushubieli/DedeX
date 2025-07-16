<?php
/**
 * 标签页
 * 
 * @version        $id:tags.php 2010-06-30 11:43:09 tianya $
 * @package        DedeX.Site
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/../system/common.inc.php");
require_once(DEDEINC."/archive/taglist.class.php");
//根据流量统计，限制用户浏览
if ($cfg_access == 'Y') {
    $viewIp = GetIP();
    $moon = time() - (24 * 60 * 60);
    $flow = $dsql->GetOne("SELECT COUNT(DISTINCT id) AS view_count FROM `#@__statistics_detail` WHERE ip='$viewIp' AND t>='$moon' AND url_type=4 ");
    if ($flow && $flow['view_count'] > $cfg_access_count) {
        header("HTTP/1.1 403 Forbidden");
        echo "拒绝访问";
        exit();
    }
}
$PageNo = 1;
if (isset($_SERVER['QUERY_STRING'])) {
    $tag = trim($_SERVER['QUERY_STRING']);
    $tags = explode('/', $tag);
    if (isset($tags[1])) $tag = $tags[1];
    if (isset($tags[2])) $PageNo = intval($tags[2]);
} else {
    $tag = '';
}
$tag = FilterSearch(urldecode($tag));
if ($tag != addslashes($tag)) $tag = '';
if ($tag == '') $dlist = new TagList($tag, 'tag.htm');
else $dlist = new TagList($tag, 'tag_list.htm');
$dlist->Display();
exit();
?>