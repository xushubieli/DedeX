<?php
/**
 * 搜索关键词维护
 *
 * @version        $id:search_keywords_main.php 15:46 2010年7月20日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
DedeSetCookie("ENV_GOBACK_URL", $dedeNowurl, time() + 3600, "/");
if (empty($pagesize)) $pagesize = 10;
if (empty($pageno)) $pageno = 1;
if (empty($dopost)) $dopost = '';
if (empty($orderby)) $orderby = 'aid';
$orderby = HtmlReplace($orderby, -1);
$pageno = intval($pageno);
$pagesize = intval($pagesize);
//更新词
if ($dopost == 'update') {
    $aid = preg_replace("#[^0-9]#", "", $aid);
    $count = preg_replace("#[^0-9]#", "", $count);
    $keyword = trim($keyword);
    $spwords = trim($spwords);
    $dsql->ExecuteNoneQuery("UPDATE `#@__search_keywords` SET keyword='$keyword',spwords='$spwords',count='$count' WHERE aid='$aid';");
    ShowMsg("成功更新关键词", "search_keywords_main.php?pageno={$pageno}&pagesize={$pagesize}&orderby={$orderby}");
    exit();
}
//删除词
else if ($dopost == 'del') {
    $aid = preg_replace("#[^0-9]#", "", $aid);
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__search_keywords` WHERE aid='$aid';");
    ShowMsg("成功删除关键词", "search_keywords_main.php?pageno={$pageno}&pagesize={$pagesize}&orderby={$orderby}");
    exit();
}
//批量删词
else if ($dopost == 'delall') {
    if (!empty($aids) && is_array($aids)) {
        foreach ($aids as $aid) {
            $aid = preg_replace("#[^0-9]#", "", $aid);
            $dsql->ExecuteNoneQuery("DELETE FROM `#@__search_keywords` WHERE aid='$aid';");
        }
    }
    ShowMsg("批量删除关键词完成", "search_keywords_main.php?pageno={$pageno}");
    exit();
}
$row = $dsql->GetOne("SELECT COUNT(*) AS dd FROM `#@__search_keywords`");
$totalRow = isset($row['dd']) ? (int)$row['dd'] : 0;
include(DEDEADMIN."/templets/search_keywords_main.htm");
//获得特定搜索列表
function GetKeywordList($dsql, $pageno, $pagesize, $orderby = 'aid')
{
    global $cfg_phpurl;
    $start = ($pageno - 1) * $pagesize;
    $printhead = "<form name='form3' action='search_keywords_main.php' method='post'>
    <input name='dopost' type='hidden' value='delall'>
    <input name='pageno' type='hidden' value='{$pageno}'>
    <input name='pagesize' type='hidden' value='{$pagesize}'>
    <input name='orderby' type='hidden' value='{$orderby}'>
    <div class='table-responsive'>
    <table class='table table-borderless table-hover'>
    <thead>
        <tr>
            <td scope='col'>选择</td>
            <td scope='col'><a href=\"search_keywords_main.php?pageno={$pageno}&pagesize={$pagesize}&orderby=aid\">ID<i class='bi bi-chevron-expand'></i></a></td>
            <td scope='col'>关键词</td>
            <td scope='col'>关键词调整</td>
            <td scope='col'>分词调整</td>
            <td scope='col'><a href=\"search_keywords_main.php?pageno={$pageno}&pagesize={$pagesize}&orderby=count\">频率<i class='bi bi-chevron-expand'></i></a></td>
            <td scope='col'><a href=\"search_keywords_main.php?pageno={$pageno}&pagesize={$pagesize}&orderby=result\">索引<i class='bi bi-chevron-expand'></i></a></td>
            <td scope='col'><a href=\"search_keywords_main.php?pageno={$pageno}&pagesize={$pagesize}&orderby=lasttime\">搜索时间<i class='bi bi-chevron-expand'></i></a></td>
            <td scope='col'>操作</td>
        </tr>
    </thead><tbody>";
    echo $printhead;
    if ($orderby == 'result') $orderby = $orderby." ASC";
    else $orderby = $orderby." DESC";
    $dsql->SetQuery("SELECT * FROM `#@__search_keywords` ORDER BY $orderby LIMIT $start,$pagesize ");
    $dsql->Execute();
    while ($row = $dsql->GetArray()) {
        $line = "<tr>
            <td><input name='aids[]' type='checkbox' value=\"{$row['aid']}\"></td>
            <td>{$row['aid']}</td>
            <td><a href='{$cfg_phpurl}/search.php?keyword=".urlencode($row['keyword'])."' target='_blank'>{$row['keyword']}</a></td>
            <td><input type='text' name='keyword_{$row['aid']}' value='{$row['keyword']}' class='form-control admin-w-sm'></td>
            <td><input type='text' name='spwords_{$row['aid']}' value='{$row['spwords']}' class='form-control admin-w-md'></td>
            <td><input type='text' name='count_{$row['aid']}' value='{$row['count']}' class='form-control admin-w-sm'></td>
            <td>{$row['result']}</td>
            <td>".MyDate("Y-m-d H:i:s", $row['lasttime'])."</td>
            <td>
                <a href='javascript:void(0);' class='btn btn-light btn-sm' onclick='confirmUpdate({$row['aid']});'><i class='bi bi-arrow-counterclockwise' title='更新'></i></a>
                <a href='search_keywords_main.php?dopost=del&aid={$row['aid']}&pageno={$pageno}' class='btn btn-danger btn-sm'><i class='bi bi-trash' title='删除'></i></a>
            </td>
        </tr>";
        echo $line;
    }
    echo "<tr>
        <td colspan='9'>
        <a href=\"javascript:selAll();\" class='btn btn-primary btn-sm'>反选</a>
        <a href=\"javascript:noselAll();\" class='btn btn-primary btn-sm'>取消</a>
        <a href=\"javascript:delall();\" class='btn btn-danger btn-sm'>删除</a>
       </td>
    </tr>";
    echo "</tbody></table></div></form>";
}
?>