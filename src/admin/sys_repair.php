<?php
/**
 * 系统修复工具
 *
 * @version        $id:sys_repair.php 22:28 2010年7月20日 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__).'/config.php');
CheckPurview('sys_ArcBatch');
require_once(DEDEINC.'/libraries/oxwindow.class.php');
if (empty($dopost)) {
    $win = new OxWindow();
    $win->Init("sys_repair.php", "/static/web/js/admin.blank.js", "POST' enctype='multipart/form-data'");
    $wintitle = "系统修复工具";
    $win->AddTitle('系统修复工具用于检测并修复数据错误');
    $msg = "<tr>
        <td>
            <p>由于手动升级未运行指定SQL语句，或自动升级过程中出现遗漏或错误，可能会导致一些问题。使用本工具可自动检测并处理这些问题。目前，本工具主要执行以下操作：</p>
            <p>1、修复/优化数据表</p>
            <p>2、更新缓存</p>
            <p>3、检测系统变量一致性</p>
            <p>4、检测微表与主表数据一致性</p>
        </td>
    </tr>
    <tr>
        <td align='center'><a href='sys_repair.php?dopost=1' class='btn btn-success btn-sm'>开始检测</a></td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", false);
    $win->Display();
    exit();
}
//数据结构常规检测
else if ($dopost == 1) {
    $win = new OxWindow();
    $win->Init("sys_repair.php", "/static/web/js/admin.blank.js", "POST' enctype='multipart/form-data'");
    $wintitle = "检测数据结构";
    $win->AddTitle('系统修复工具用于检测并修复数据错误');
    $msg = "<tr>
        <td>
            <p>已完成数据结构完整性检测：</p>
            <p>1、获取主键失败，无法进行后续操作</p>
            <p>2、更新数据库#@__archivess表时出错</p>
            <p>3、列表显示数据目与实际文档数不一致</p>
        </td>
    </tr>
    <tr>
        <td align='center'><a href='sys_repair.php?dopost=2' class='btn btn-success btn-sm'>下一步</a></td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", false);
    $win->Display();
    exit();
}
//检测微表正确性并尝试修复
else if ($dopost == 2) {
    $msg = '';
    $allarcnum = 0;
    $row = $dsql->GetOne("SELECT COUNT(*) AS dd FROM `#@__archives`");
    $allarcnum = $arcnum = $row['dd'];
    $msg .= "<p>#@__archives表总记录数：{$arcnum}</p>";
    $shtables = array();
    $dsql->Execute('me', "SELECT addtable FROM `#@__channeltype` WHERE id < -1 ");
    while ($row = $dsql->GetArray('me')) {
        $addtable = strtolower(trim(str_replace('#@__', $cfg_dbprefix, $row['addtable'])));
        if (empty($addtable)) {
            continue;
        } else {
            if (!isset($shtables[$addtable])) {
                $shtables[$addtable] = 1;
                $row = $dsql->GetOne("SELECT COUNT(aid) AS dd FROM `$addtable`");
                $msg .= "<p>{$addtable}表总记录数：{$row['dd']}</p>";
                $allarcnum += $row['dd'];
            }
        }
    }
    $msg .= "<p>总有效记录数：{$allarcnum}</p>";
    $errall = "<a href='index_body.php' class='btn btn-success btn-sm'>完成修复</a>";
    $row = $dsql->GetOne("SELECT COUNT(*) AS dd FROM `#@__arctiny`");
    $msg .= "<p>微统计表记录数：{$row['dd']}</p>";
    if ($row['dd'] == $allarcnum) {
        $msg .= "<p>两者记录一致，无需修复</p>";
    } else {
        $sql = "TRUNCATE TABLE `#@__arctiny`";
        $dsql->ExecuteNoneQuery($sql);
        $msg .= "<p>两者记录不一致，尝试进行简单修复</p>";
        //导入普通模型微数据
        $sql = "INSERT INTO `#@__arctiny` (id,typeid,typeid2,arcrank,channel,senddate,sortrank,mid) SELECT id,typeid,typeid2,arcrank,channel,senddate,sortrank,mid FROM `#@__archives` ";
        $dsql->ExecuteNoneQuery($sql);
        //导入自定义模型微数据
        foreach ($shtables as $tb => $v) {
            $sql = "INSERT INTO `#@__arctiny` (id,typeid,typeid2,arcrank,channel,senddate,sortrank,mid) SELECT aid,typeid,0,arcrank,channel,senddate,0,mid FROM `$tb` ";
            $rs = $dsql->ExecuteNoneQuery($sql);
            $doarray[$tb]  = 1;
        }
        $row = $dsql->GetOne("SELECT COUNT(*) AS dd FROM `#@__arctiny`");
        if ($row['dd'] == $allarcnum) {
            $msg .= "<p>修复记录成功</p>";
        } else {
            $msg .= "<p>修复记录失败，建议高级检测</p>";
            $errall = "<a href='sys_repair.php?dopost=3' class='btn btn-success btn-sm'>高级检测</a>";
        }
    }
    UpDateCatCache();
    $win = new OxWindow();
    $win->Init("sys_repair.php", "/static/web/js/admin.blank.js", "POST' enctype='multipart/form-data'");
    $wintitle = "检测微表数据";
    $win->AddTitle('系统修复工具用于检测并修复数据错误');
    $msg = "<tr>
        <td>{$msg}</td>
    </tr>
    <tr>
        <td align='center'>{$errall}</td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", false);
    $win->Display();
    exit();
}
//高级方式修复微表，会删除不合法主键的文档
else if ($dopost == 3) {
    $errnum = 0;
    $sql = "TRUNCATE TABLE `#@__arctiny`";
    $dsql->ExecuteNoneQuery($sql);
    $sql = "SELECT arc.id, arc.typeid, arc.typeid2,arc.arcrank,arc.channel,arc.senddate,arc.sortrank,arc.mid, ch.addtable FROM `#@__archives` arc LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel ";
    $dsql->Execute('me', $sql);
    while ($row = $dsql->GetArray('me')) {
        $sql = "INSERT INTO `#@__arctiny`(id,typeid, typeid2,arcrank,channel,senddate,sortrank,mid) VALUES ('{$row['id']}','{$row['typeid']}','{$row['typeid2']}','{$row['arcrank']}','{$row['channel']}','{$row['senddate']}','{$row['sortrank']}','{$row['mid']}'); ";
        $rs = $dsql->ExecuteNoneQuery($sql);
        if (!$rs) {
            $addtable = trim($addtable);
            $errnum++;
            $dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='{$row['id']}' ");
            if (!empty($addtable)) $dsql->ExecuteNoneQuery("DELETE FROM `$addtable` WHERE id='{$row['id']}' ");
        }
    }
    //导入自定义模型微数据
    $dsql->SetQuery("SELECT id,addtable FROM `#@__channeltype` WHERE id < -1 ");
    $dsql->Execute();
    $doarray = array();
    while ($row = $dsql->GetArray()) {
        $tb = str_replace('#@__', $cfg_dbprefix, $row['addtable']);
        if (empty($tb) || isset($doarray[$tb])) {
            continue;
        } else {
            $sql = "INSERT INTO `#@__arctiny`(id,typeid,typeid2,arcrank,channel,senddate,sortrank,mid) SELECT aid,typeid,0,arcrank,channel,senddate,0,mid FROM `$tb` ";
            $rs = $dsql->ExecuteNoneQuery($sql);
            $doarray[$tb]  = 1;
        }
    }
    $win = new OxWindow();
    $win->Init("sys_repair.php", "/static/web/js/admin.blank.js", "POST' enctype='multipart/form-data'");
    $wintitle = "高级检测";
    $win->AddTitle('系统修复工具用于检测并修复数据错误');
    $msg = "<tr>
        <td>完成所有修复操作，移除错误记录{$errnum}条</td>
    </tr>
    <tr>
        <td align='center'><a href='index_body.php' class='btn btn-success btn-sm'>完成修复</a></td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", false);
    $win->Display();
    exit();
}
?>