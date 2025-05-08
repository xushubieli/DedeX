<?php
/**
 * 标签管理
 *
 * @version        $id:tag_test_action.php 23:07 2010年7月20日 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__).'/config.php');
CheckPurview('sys_Keyword');
require_once(DEDEINC.'/datalistcp.class.php');
$timestamp = time();
if (empty($tag)) $tag = '';
if (empty($action)) {
    $tag = HtmlReplace($tag, -1);
    $orderby = empty($orderby) ? 'id' : preg_replace("#[^a-z]#i", '', $orderby);
    $orderway = isset($orderway) && $orderway == 'asc' ? 'asc' : 'desc';
    if (!empty($tag)) $where = " WHERE tag like '%$tag%' OR id='$tag'";
    else $where = '';
    $neworderway = ($orderway == 'desc' ? 'asc' : 'desc');
    $query = "SELECT T.*,TI.* FROM `#@__tagindex` T LEFT JOIN `#@__tagindex_infos` TI ON TI.tagid=T.id $where ORDER BY $orderby $orderway";
    $dlist = new DataListCP();
    $tag = stripslashes($tag);
    $dlist->SetParameter("tag", $tag);
    $dlist->SetParameter("orderway", $orderway);
    $dlist->SetParameter("orderby", $orderby);
    $dlist->pagesize = 30;
    $dlist->SetTemplet(DEDEADMIN."/templets/tags_main.htm");
    $dlist->SetSource($query);
    $dlist->Display();
    exit();
} else if ($action == 'update') {
    $tid = (empty($tid) ? 0 : intval($tid));
    $count = (empty($count) ? 0 : intval($count));
    $litpic = (empty($litpic) ? '' : HtmlReplace($litpic, -1));
    $title = (empty($title) ? '' : HtmlReplace($title, -1));
    $keywords = (empty($keywords) ? '' : HtmlReplace($keywords, -1));
    $description = (empty($description) ? '' : HtmlReplace($description, -1));
    if (empty($tid)) {
        die('请选择需要更新的标签');
    }
    $query = "UPDATE `#@__tagindex` SET `count`='$count',`title`='$title',`keywords`='$keywords',`description`='$description' WHERE id='$tid' ";
    $dsql->ExecuteNoneQuery($query);
    $row = $dsql->GetOne("SELECT COUNT(*) AS dd FROM `#@__tagindex_infos` WHERE tagid = $tid");
    if ($row['dd'] > 0) {
        $dsql->ExecuteNoneQuery("UPDATE `#@__tagindex_infos` SET `litpic`=='$litpic'");
    } else {
        $dsql->ExecuteNoneQuery("INSERT INTO `#@__tagindex_infos` (`tagid`,`litpic`) VALUES ('$tid','$litpic')");
    }
    echo "success";
    exit();
} else if ($action == 'delete') {
    if (@is_array($ids)) {
        $stringids = implode(',', $ids);
    } else if (!empty($ids)) {
        $stringids = $ids;
    } else {
        ShowMsg('请选择需要删除的标签', '-1');
        exit();
    }
    $query = "DELETE FROM `#@__tagindex` WHERE id IN ($stringids)";
    if ($dsql->ExecuteNoneQuery($query)) {
        $query = "DELETE FROM `#@__taglist` WHERE tid IN ($stringids)";
        $dsql->ExecuteNoneQuery($query);
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__tagindex_infos` WHERE tagid IN ($stringids)");
        ShowMsg("删除[$stringids]标签成功", 'tags_main.php');
    } else {
        ShowMsg("删除[$stringids]标签失败", 'tags_main.php');
    }
    exit();
} else if ($action == 'fetch') {
    $wheresql = '';
    $start = isset($start) && is_numeric($start) ? $start : 0;
    $where = array();
    if (isset($startaid) && is_numeric($startaid) && $startaid > 0) {
        $where[] = " id>=$startaid ";
    } else {
        $startaid = 0;
    }
    if (isset($endaid) && is_numeric($endaid) && $endaid > 0) {
        $where[] = " id<=$endaid ";
    } else {
        $endaid = 0;
    }
    if (!empty($where)) {
        $wheresql = " WHERE arcrank>-1 AND ".implode(' AND ', $where);
    }
    $query = "SELECT id as aid,arcrank,typeid,keywords FROM `#@__archives` $wheresql LIMIT $start, 100";
    $dsql->SetQuery($query);
    $dsql->Execute();
    $complete = true;
    $now = time();
    while ($row = $dsql->GetArray()) {
        $aid = $row['aid'];
        $typeid = $row['typeid'];
        $arcrank = $row['arcrank'];
        $row['keywords'] = trim($row['keywords']);
        if ($row['keywords'] != '' && !preg_match("#,#", $row['keywords'])) {
            $keyarr = explode(' ', $row['keywords']);
        } else {
            $keyarr = explode(',', $row['keywords']);
        }
        foreach ($keyarr as $keyword) {
            $keyword = trim($keyword);
            if ($keyword != '' && strlen($keyword) < 24) {
                $keyword = addslashes($keyword);
                $row = $dsql->GetOne("SELECT id,total FROM `#@__tagindex` WHERE tag LIKE '$keyword'");
                if (is_array($row)) {
                    $tid = $row['id'];
                    $trow = $dsql->GetOne("SELECT COUNT(*) as dd FROM `#@__taglist` WHERE tag LIKE '$keyword'");
                    if (intval($trow['dd']) != $row['total']) {
                        $query = "UPDATE `#@__tagindex` SET `total`=".$trow['dd'].",uptime=$now WHERE id='$tid' ";
                        $dsql->ExecuteNoneQuery($query);
                    }
                } else {
                    $query = "INSERT INTO `#@__tagindex` (`tag`,`count`,`total`,`weekcc`,`monthcc`,`weekup`,`monthup`,`addtime`,`uptime`) VALUES ('$keyword','0','1','0','0','$timestamp','$timestamp','$timestamp','$now');";
                    $dsql->ExecuteNoneQuery($query);
                    $tid = $dsql->GetLastID();
                }
                $query = "REPLACE INTO `#@__taglist` (`tid`,`aid`,`typeid`,`arcrank`,`tag`) VALUES ('$tid','$aid','$typeid','$arcrank','$keyword'); ";
                $dsql->ExecuteNoneQuery($query);
            }
        }
        $complete = FALSE;
    }
    if ($complete) {
        ShowMsg('完成标签获取', 'tags_main.php');
        exit();
    }
    $start = $start + 100;
    $goto = "tags_main.php?action=fetch&startaid=$startaid&endaid=$endaid&start=$start";
    ShowMsg('正在获取标签', $goto);
    exit();
}
?>