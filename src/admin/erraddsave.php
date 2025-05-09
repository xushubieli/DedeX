<?php
/**
 * 挑错管理
 *
 * @version        $id:erraddsave.php 19:09 2010年7月12日 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/datalistcp.class.php');
require_once(DEDEINC.'/common.func.php');
if (empty($dopost)) $dopost = '';
if (empty($fmdo)) $fmdo = '';
function username($mid)
{
    global $dsql;
    if (!isset($mid) || empty($mid)) {
        return "游客";
    } else {
        $sql = "SELECT uname FROM `#@__member` WHERE `mid` = '$mid'";
        $row = $dsql->GetOne($sql);
        return $row['uname'];
    }
}
function typename($me)
{
    switch ($me) {
        case $me == 1:
            return $me = "错别字";
        case $me == 2:
            return $me = "成语运用不当";
        case $me == 3:
            return $me = "专业术语写法不规则";
        case $me == 4:
            return $me = "产品与图片不符";
        case $me == 5:
            return $me = "事实年代以及文档错误";
        case $me == 6:
            return $me = "事实年代以及文档错误";
        case $me == 7:
            return $me = "其他错误";
        default:
            return $me = "未知错误";
    }
}
if ($dopost == "delete") {
    if ($id == '') {
        ShowMsg("参数无效", "-1");
        exit();
    }
    if ($fmdo == 'yes') {
        $id = explode("`", $id);
        foreach ($id as $var) {
            $query = "DELETE FROM `#@__erradd` WHERE `id` = '$var'";
            $dsql->ExecuteNoneQuery($query);
        }
        ShowMsg("成功删除指定的文档", "erraddsave.php");
        exit();
    } else {
        require_once(DEDEINC."/libraries/oxwindow.class.php");
        $wintitle = "删除文档错误";
        $win = new OxWindow();
        $win->Init("erraddsave.php", "/static/web/js/admin.blank.js", "POST");
        $win->AddHidden("fmdo", "yes");
        $win->AddHidden("dopost", $dopost);
        $win->AddHidden("id", $id);
        $win->AddTitle("您确定要删除".$id."错误提示吗");
        $winform = $win->GetWindow("ok");
        $win->Display();
        exit();
    }
}
$sql = "SELECT * FROM `#@__erradd` ORDER BY id DESC";
$dlist = new DataListCP();
$dlist->SetTemplet(DEDEADMIN."/templets/erradd.htm");
$dlist->SetSource($sql);
$dlist->display();
?>