<?php
/**
 * 自由列表管理
 *
 * @version        $id:freelist_main.php 8:48 2010年7月13日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('c_FreeList');
require_once DEDEINC.'/channelunit.func.php';
DedeSetCookie("ENV_GOBACK_URL", $dedeNowurl, time() + 3600, "/");
if (empty($pagesize)) $pagesize = 10;
if (empty($pageno)) $pageno = 1;
if (empty($dopost)) $dopost = '';
if (empty($orderby)) $orderby = 'aid';
if (empty($keyword)) {
    $keyword = '';
    $addget = '';
    $addsql = '';
} else {
    $addget = '&keyword='.urlencode($keyword);
    $addsql = " WHERE title like '%{$keyword}%' ";
}
//删除列表
if ($dopost == 'del') {
    $aid = preg_replace("#[^0-9]#", "", $aid);
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__freelist` WHERE aid='$aid';");
    ShowMsg("成功删除一个自由列表", "freelist_main.php?pageno={$pageno}&pagesize={$pagesize}&orderby={$orderby}");
    exit();
}
$row = $dsql->GetOne("SELECT COUNT(*) AS dd FROM `#@__freelist` $addsql ");
$totalRow = $row['dd'];
include(DEDEADMIN."/templets/freelist_main.htm");
//获得特定自由列表
function GetTagList($dsql, $pageno, $pagesize, $orderby = 'aid')
{
    global $cfg_phpurl, $addsql, $addget;
    $start = ($pageno-1) * $pagesize;
    $printhead ="<div class='table-responsive'>
    <table class='table table-borderless table-hover'>
        <thead>
        <tr>
            <td scope='col'><a href=\"freelist_main.php?orderby=aid{$addget}\">ID<i class='bi bi-chevron-expand'></i></a></td>
            <td scope='col'>列表名称</td>
            <td scope='col'>模板文件</td>
            <td scope='col'><a href=\"freelist_main.php?orderby=click{$addget}\">点击<i class='bi bi-chevron-expand'></i></a></td>
            <td scope='col'>创建时间</td>
            <td scope='col'>操作</td>
        </tr>
    </thead><tbody>";
    echo $printhead;
    $dsql->SetQuery("SELECT aid,title,templet,click,edtime,namerule,listdir,defaultpage,nodefault FROM `#@__freelist` $addsql ORDER BY $orderby DESC LIMIT $start,$pagesize");
    $dsql->Execute();
    while ($row = $dsql->GetArray()) {
        $listurl = GetFreeListUrl($row['aid'],$row['namerule'],$row['listdir'],$row['defaultpage'],$row['nodefault']);
        $line = "<tr>
            <td>{$row['aid']}</td>
            <td><a href='{$listurl}' target='_blank'>{$row['title']}</a></td>
            <td>{$row['templet']}</td>
            <td>{$row['click']}</td>
            <td>".MyDate("y-m-d",$row['edtime'])."</td>
            <td>
                <a href=\"makehtml_freelist.php?aid={$row['aid']}\" class='btn btn-light btn-sm'><i class='bi bi-arrow-counterclockwise' title='更新'></i></a>
                <a href=\"freelist_edit.php?aid={$row['aid']}\" class='btn btn-light btn-sm'><i class='bi bi-pencil-square' title='修改'></i></a>
                <a href='{$listurl}' target='_blank' class='btn btn-light btn-sm'><i class='bi bi-box-arrow-up-right' title='预览'></i></a>
                <a href=\"freelist_main.php?dopost=del&aid={$row['aid']}&pageno={$pageno}\" class='btn btn-danger btn-sm'><i class='bi bi-trash' title='删除'></i></a>
            </td>
        </tr>";
        echo $line;
    }
    echo "</tbody></table></div>";
}
?>