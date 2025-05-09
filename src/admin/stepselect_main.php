<?php
/**
 * 联动选择管理
 *
 * @version        $id:stepselect_main.php 2 13:23 2011-3-24 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('c_Stepselect');
require_once(DEDEINC."/datalistcp.class.php");
require_once(DEDEINC.'/enums.func.php');
//前台视图
$ENV_GOBACK_URL = (isset($ENV_GOBACK_URL) ? $ENV_GOBACK_URL : 'stepselect_main.php');
if (empty($action)) {
    DedeSetCookie("ENV_GOBACK_URL", $dedeNowurl, time() + 3600, "/");
    if (!isset($egroup)) $egroup = '';
    if (!isset($topvalue)) $topvalue = 0;
    $etypes = array();
    $egroups = array();
    $dsql->Execute('me', 'SELECT * FROM `#@__stepselect` ORDER BY id DESC');
    while ($arr = $dsql->GetArray()) {
        $etypes[] = $arr;
        $egroups[$arr['egroup']] = $arr['itemname'];
    }
    if ($egroup != '') {
        $orderby = 'ORDER BY disorder ASC, evalue ASC';
        if (!empty($topvalue)) {
            //判断是否为1级联动
            if ($topvalue % 500 == 0) {
                $egroupsql = " WHERE egroup LIKE '$egroup' AND evalue>=$topvalue AND evalue < ".($topvalue + 500);
            } else {
                $egroupsql = " WHERE (evalue LIKE '$topvalue.%%%' OR evalue=$topvalue) AND egroup LIKE '$egroup'";
            }
        } else {
            $egroupsql = " WHERE egroup LIKE '$egroup' ";
        }
        $sql = "SELECT * FROM `#@__sys_enum` $egroupsql $orderby";
    } else {
        $egroupsql = '';
        $sql = "SELECT * FROM `#@__stepselect` ORDER BY id DESC";
    }
    //echo $sql;exit;
    $dlist = new DataListCP();
    $dlist->SetParameter('egroup', $egroup);
    $dlist->SetParameter('topvalue', $topvalue);
    $dlist->SetTemplet(DEDEADMIN."/templets/stepselect_main.htm");
    $dlist->SetSource($sql);
    $dlist->display();
    exit();
} else if ($action == 'edit' || $action == 'addnew' || $action == 'addenum' || $action == 'view') {
    AjaxHead();
    include('./templets/stepselect_showajax.htm');
    exit();
}
//删除类型或枚举值
else if ($action == 'del') {
    $arr = $dsql->GetOne("SELECT * FROM `#@__stepselect` WHERE id='$id' ");
    if (!is_array($arr)) {
        ShowMsg("无法获取分类信息，不允许后续操作", "stepselect_main.php?".ExecTime());
        exit();
    }
    if ($arr['issystem'] == 1) {
        ShowMsg("系统内置的枚举分类不能删除", "stepselect_main.php?".ExecTime());
        exit();
    }
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__stepselect` WHERE id='$id';");
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__sys_enum` WHERE egroup='{$arr['egroup']}';");
    ShowMsg("成功删除一个分类", "stepselect_main.php?".ExecTime());
    exit();
} else if ($action == 'delenumAllSel') {
    if (isset($ids) && is_array($ids)) {
        $id = join(',', $ids);
        $groups = array();
        $dsql->Execute('me', "SELECT egroup FROM `#@__sys_enum` WHERE id IN($id) GROUP BY egroup");
        while ($row = $dsql->GetArray('me')) {
            $groups[] = $row['egroup'];
        }
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__sys_enum` WHERE id IN($id);");
        //更新缓存
        foreach ($groups as $egropu) {
            WriteEnumsCache($egroup);
        }
        ShowMsg("成功删除选中的枚举分类", $ENV_GOBACK_URL);
    } else {
        ShowMsg("您没选择任何分类", "-1");
    }
    exit();
} else if ($action == 'delenum') {
    $row = $dsql->GetOne("SELECT egroup FROM `#@__sys_enum` WHERE id = '$id' ");
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__sys_enum` WHERE id='{$id}';");
    WriteEnumsCache($row['egroup']);
    ShowMsg("成功删除一个枚举", $ENV_GOBACK_URL);
    exit();
}
//保存类型修改
else if ($action == 'edit_save') {
    if (preg_match("#[^0-9a-z_-]#i", $egroup)) {
        ShowMsg("组名称不能有全角字符或特殊符号", "-1");
        exit();
    }
    $dsql->ExecuteNoneQuery("UPDATE `#@__stepselect` SET `itemname`='$itemname',`egroup`='$egroup' WHERE id='$id';");
    ShowMsg("成功修改一个分类", "stepselect_main.php?".ExecTime());
    exit();
}
//保存新类型
else if ($action == 'addnew_save') {
    if (preg_match("#[^0-9a-z_-]#i", $egroup)) {
        ShowMsg("组名称不能有全角字符或特殊符号", "-1");
        exit();
    }
    $arr = $dsql->GetOne("SELECT * FROM `#@__stepselect` WHERE itemname LIKE '$itemname' OR egroup LIKE '$egroup' ");
    if (is_array($arr)) {
        ShowMsg("您指定的类别名称或组名称已经存在，不能使用", "stepselect_main.php");
        exit();
    }
    $dsql->ExecuteNoneQuery("INSERT INTO `#@__stepselect` (`itemname`,`egroup`,`issign`,`issystem`) VALUES ('$itemname','$egroup','0','0');");
    WriteEnumsCache($egroup);
    ShowMsg("成功添加一个分类", "stepselect_main.php?egroup=$egroup");
    exit();
}
//旧版全国省市表替换当前地区数据
else if ($action == 'exarea') {
    $bigtypes = array();
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__sys_enum` WHERE egroup='nativeplace';");
    $query = "SELECT * FROM `#@__area` WHERE reid =0 ORDER BY id ASC";
    $dsql->Execute('me', $query);
    $n = 1;
    while ($row = $dsql->GetArray()) {
        $bigtypes[$row['id']] = $evalue = $disorder = $n * 500;
        $dsql->ExecuteNoneQuery("INSERT INTO `#@__sys_enum` (`ename`,`evalue`,`egroup`,`disorder`,`issign`)
            VALUES ('{$row['name']}','$evalue','nativeplace','$disorder','0');");
        $n++;
    }
    $stypes = array();
    foreach ($bigtypes as $k => $v) {
        $query = "SELECT * FROM `#@__area` WHERE reid=$k ORDER BY id ASC";
        $dsql->Execute('me', $query);
        $n = 1;
        while ($row = $dsql->GetArray()) {
            $stypes[$row['id']] = $evalue = $disorder = $v + $n;
            $dsql->ExecuteNoneQuery("INSERT INTO `#@__sys_enum` (`ename`,`evalue`,`egroup`,`disorder`,`issign`)
                VALUES ('{$row['name']}','$evalue','nativeplace','$disorder','0');");
            $n++;
        }
    }
    WriteEnumsCache('nativeplace');
    ShowMsg("成功导入所有旧的地区数据", "stepselect_main.php?egroup=nativeplace");
    exit();
}
//关于二级枚举：为了节省查询速度，二级枚举是通过特殊算法生成的，原理为凡是能被500整除的都是一级枚举(500 * n) + 1 < em < 500 * (n+1)为下级枚举，例：1000的下级枚举对应的值为 1001,1002,10031499对于issign=1的，表示这个类别只有一级枚举，则不受上面的算法限制。更新算法：新增二级枚举下添加"-N"自己类别选择，例：1001二级枚举下面的3级栏目，则为1001-1,1001-2这时候需要issign=2
else if ($action == 'addenum_save') {
    if (empty($ename) || empty($egroup)) {
        Showmsg("类别名称或组名称不能为空", "-1");
        exit();
    }
    if ($issign == 1 || $topvalue == 0) {
        $enames = explode(',', $ename);
        foreach ($enames as $ename) {
            $arr = $dsql->GetOne("SELECT * FROM `#@__sys_enum` WHERE egroup='$egroup' AND (evalue MOD 500)=0 ORDER BY disorder DESC");
            if (!is_array($arr)) $disorder = $evalue = ($issign == 1 ? 1 : 500);
            else $disorder = $evalue = $arr['disorder'] + ($issign == 1 ? 1 : 500);
            $dsql->ExecuteNoneQuery("INSERT INTO `#@__sys_enum` (`ename`,`evalue`,`egroup`,`disorder`,`issign`) 
                VALUES ('$ename','$evalue','$egroup','$disorder','$issign');");
        }
        WriteEnumsCache($egroup);
        ShowMsg("成功添加枚举分类".$dsql->GetError(), $ENV_GOBACK_URL);
        exit();
    } else if ($issign == 2 && $topvalue != 0) {
        $minid = $topvalue;
        $maxnum = 500; //三级子类最多500个
        $enames = explode(',', $ename);
        foreach ($enames as $ename) {
            $arr = $dsql->GetOne("SELECT * FROM `#@__sys_enum` WHERE egroup='$egroup' AND evalue LIKE '$topvalue.%%%' ORDER BY evalue DESC");
            if (!is_array($arr)) {
                $disorder = $minid;
                $evalue = $minid.'.001';
            } else {
                $disorder = $minid;
                preg_match("#([0-9]{1,})\.([0-9]{1,})#", $arr['evalue'], $matchs);
                $addvalue = $matchs[2] + 1;
                $addvalue = sprintf("%03d", $addvalue);
                $evalue = $matchs[1].'.'.$addvalue;
            }
            $sql = "INSERT INTO `#@__sys_enum` (`ename`,`evalue`,`egroup`,`disorder`,`issign`) 
                VALUES ('$ename','$evalue','$egroup','$disorder','$issign'); ";
            //echo $sql;exit;
            $dsql->ExecuteNoneQuery($sql);
        }
        //echo $minid;
        WriteEnumsCache($egroup);
        ShowMsg("成功添加枚举分类", $ENV_GOBACK_URL);
        exit();
    } else {
        $minid = $topvalue;
        $maxid = $topvalue + 500;
        $enames = explode(',', $ename);
        foreach ($enames as $ename) {
            $arr = $dsql->GetOne("SELECT * FROM `#@__sys_enum` WHERE egroup='$egroup' AND evalue>$minid AND evalue<$maxid ORDER BY evalue DESC");
            if (!is_array($arr)) {
                $disorder = $evalue = $minid + 1;
            } else {
                $disorder = $arr['disorder'] + 1;
                $evalue = $arr['evalue'] + 1;
            }
            $dsql->ExecuteNoneQuery("INSERT INTO `#@__sys_enum` (`ename`,`evalue`,`egroup`,`disorder`,`issign`) 
                VALUES ('$ename','$evalue','$egroup','$disorder','$issign');");
        }
        WriteEnumsCache($egroup);
        ShowMsg("成功添加枚举分类", $ENV_GOBACK_URL);
        exit();
    }
}
//修改枚举名称和排序
else if ($action == 'upenum') {
    $ename = trim(preg_replace("# └─(─){1,}#", '', $ename));
    $row = $dsql->GetOne("SELECT egroup FROM `#@__sys_enum` WHERE id = '$aid' ");
    WriteEnumsCache($row['egroup']);
    $dsql->ExecuteNoneQuery("UPDATE `#@__sys_enum` SET `ename`='$ename',`disorder`='$disorder' WHERE id='$aid';");
    ShowMsg("成功修改一个枚举", $ENV_GOBACK_URL);
    exit();
}
//更新枚举缓存
else if ($action == 'upallcache') {
    if (!isset($egroup)) $egroup = '';
    WriteEnumsCache($egroup);
    ShowMsg("成更新枚举缓存", $ENV_GOBACK_URL);
    exit();
}
?>