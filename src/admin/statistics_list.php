<?php
/**
 * 流量统计表
 *
 * @version        $id:statistics_list.php 2024-04-15 tianya,xushubieli $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC.'/datalistcp.class.php');
function RenderUrlType($t) {
    switch ($t) {
        case -1:
            return "蜘蛛";
        case 1:
            return "列表";
        case 2:
            return "文档";
        case 3:
            return "搜索";
        case 4:
            return "标签";
        default:
            return "综合";
    }
}
//检查权限，感谢：乖乖女
if (isset($id) && isset($reid)) {
    if ($id == 0 && $reid == 0) {
        CheckPurview('c_List');
    }
}
$ip = isset($ip) ? HtmlReplace(trim($ip)) : '';
$url_type = isset($url_type) ? intval($url_type) : 0;
$day_peak = isset($day_peak) ? trim($day_peak) : '';
if (empty($mobile)) $mobile = '';
if (isset($dopost) && $dopost == "delete") {
    $ids = explode('`',$aids);
    $dquery = "";
    foreach ($ids as $id) {
        $id = intval($id);
        if ($dquery == "") $dquery .= "id='$id' ";
        else $dquery .= " OR id='$id' ";
    }
    if ($dquery != "") $dquery = " WHERE $dquery";
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__statistics_detail` $dquery");
    ShowMsg("成功删除指定记录", "statistics_list.php");
    exit();
} else {
    $addsql = " WHERE ip LIKE '%{$ip}%' ";
    if ($url_type === -1) {
        $addsql .= " AND url_type = -1 ";
    } else if ($url_type === 1) {
        $addsql .= " AND url_type > 0 ";
    }
}
$sql = "SELECT * FROM `#@__statistics_detail` $addsql ORDER BY id DESC";
//今日峰值
if ($day_peak == '1') {
    $dsql->Execute('peak', "SELECT ip,dduuid,browser,os,COUNT(*) as count FROM `#@__statistics_detail` WHERE t >= ".strtotime(date('Y-m-d'))." AND url_type != -1 GROUP BY ip ORDER BY count DESC LIMIT 15");
}
$dlist = new DataListCP();
$dlist->pagesize = 10;
$tplfile = DEDEADMIN."/templets/statistics_list.htm";
$dlist->SetParameter("ip", $ip);
$dlist->SetParameter("url_type", $url_type);
$dlist->SetParameter("day_peak", $day_peak);
$dlist->SetTemplate($tplfile);
$dlist->SetSource($sql);
$dlist->Display();
?>