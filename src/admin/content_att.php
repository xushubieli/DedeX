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
    $startID = 1;
    $endID = $idend;
    for (; $startID <= $endID; $startID++) {
        $att = ${'att_'.$startID};
        $attname = ${'attname_'.$startID};
        $sortid = ${'sortid_'.$startID};
        $query = "UPDATE `#@__arcatt` SET `attname`='$attname',`sortid`='$sortid' WHERE att='$att' ";
        $dsql->ExecuteNoneQuery($query);
    }
    ShowMsg('成功更新文档自定义属性', '-1');
    exit();
}
include DedeInclude('templets/content_att.htm');
?>