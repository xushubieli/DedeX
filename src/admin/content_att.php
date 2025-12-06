<?php
/**
 * 文档自定义属性
 *
 * @version        $id:content_att.php 14:31 2010年7月12日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_Att');
if (empty($dopost)) $dopost = '';
//保存修改
if ($dopost == "save") {
    for ($i = 1; $i <= $idend; $i++) {
        $att = ${'att_'.$i};
        $attname = ${'attname_'.$i};
        $sortid = ${'sortid_'.$i};
        if (empty($att) || empty($attname)) continue;
        if (preg_match('#[^a-z]#', $att)) {
            ShowMsg('保存属性值失败，请使用数字小写a-z', '-1');
            exit();
        }
        $query = "SELECT * FROM `#@__arcatt` WHERE att='$att'";
        $dsql->SetQuery($query);
        $dsql->Execute();
        if ($dsql->GetTotalRow() > 0) {
            $query = "UPDATE `#@__arcatt` SET attname='$attname',sortid='$sortid' WHERE att='$att'";
        } else {
            $query = "INSERT INTO `#@__arcatt` (att,attname,sortid) VALUES ('$att','$attname','$sortid')";
        }
        $dsql->ExecuteNoneQuery($query);
    }
    $attList = [];
    $dsql->SetQuery("SELECT att FROM `#@__arcatt` ORDER BY sortid ASC");
    $dsql->Execute();
    while ($row = $dsql->GetObject()) $attList[] = $row->att;
    $sqlAlter = "ALTER TABLE `#@__archives` CHANGE `flag` `flag` SET('".implode("','", $attList)."')";
    $dsql->ExecuteNoneQuery($sqlAlter);
    ShowMsg('成功更新文档自定义属性', '-1');
    exit();
}
include DedeInclude('templets/content_att.htm');
?>