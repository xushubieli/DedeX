<?php
/**
 * 流量统计表
 *
 * @version        $id:statistics_list.php 2024-04-15 tianya,xushubieli $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
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
if (empty($mobile)) $mobile = '';
if (isset($dopost) && $dopost == "delete") {
    $ids = explode('`',$aids);
    $dquery = "";
    foreach ($ids as $id)
    {
        $id = intval($id);
        if ($dquery == "") $dquery .= "id='$id' ";
        else $dquery .= " OR id='$id' ";
    }
    if ($dquery != "") $dquery = " WHERE ".$dquery;
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__statistics_detail` $dquery");
    ShowMsg("成功删除指定的记录", "statistics_list.php");
    exit();    
} else {
    $addsql = " WHERE ip LIKE '%$ip%' ";
    if ($url_type === -1) {
        $addsql .= " AND url_type = -1 ";
    } else if ($url_type === 1) {
        $addsql .= " AND url_type > 0 ";
    }
    $sql = "SELECT * FROM `#@__statistics_detail` $addsql ORDER BY id DESC";
    $dlist = new DataListCP();
    //流量列表数
    $dlist->pagesize = 30;
    $tplfile = DEDEADMIN."/templets/statistics_list.htm";
    $dlist->SetParameter("ip", $ip);
    $dlist->SetParameter("url_type", $url_type);
    $dlist->SetTemplate($tplfile);
    $dlist->SetSource($sql);
    $dlist->Display();
}
?>