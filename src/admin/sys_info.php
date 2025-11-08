<?php
/**
 * 系统设置
 *
 * @version        $id:sys_info.php 22:28 2010年7月20日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_Edit');
if (empty($dopost)) $dopost = '';
$configfile = DEDEDATA.'/config.cache.inc.php';
//更新配置函数
function ReWriteConfig()
{
    global $dsql, $configfile;
    if (!is_writeable($configfile)) {
        ShowMsg("配置文件{$configfile}不支持写入，无法修改系统配置参数", "-1");
        exit();
    }
    $fp = fopen($configfile, 'w');
    flock($fp, 3);
    fwrite($fp, "<"."?php\r\n");
    $dsql->SetQuery("SELECT `varname`,`type`,`value`,`groupid` FROM `#@__sysconfig` ORDER BY aid ASC");
    $dsql->Execute();
    while ($row = $dsql->GetArray()) {
        if ($row['type'] == 'number') {
            $row['value'] = preg_replace("#[^0-9-]#i", "", $row['value']);
            if ($row['value'] == '') $row['value'] = 0;
            fwrite($fp, "\${$row['varname']} = {$row['value']};\r\n");
        } else {
            $row['value'] = stripslashes($row['value']);
            fwrite($fp, "\${$row['varname']} = '".str_replace(array('\\"', "'", "\\\\"),array('"', "\'", "\\"),$row['value'])."';\r\n");
        }
    }
    fwrite($fp, "?".">");
    fclose($fp);
}
//保存配置变量
if ($dopost == "save") {
    CheckCSRF();
    foreach ($_POST as $n => $v) {
        if (!preg_match("#^edit_#", $n)) continue;
        $k = preg_replace("#^edit_#", "", $n);
        $v = cn_substrR($v, 1024);
        $row = $dsql->GetOne("SELECT `type` FROM `#@__sysconfig` WHERE varname='$k'");
        if (!$row) continue;
        if ($row['type'] == 'number') {
            if ($v != '' && preg_match("#[^0-9-]#i", $v)) {
                ShowMsg("{$n}数字变量值必须由0-9组成", "-1");
                exit();
            }
            if ($v != '') {
                if (strpos($v, '-') !== false) {
                    if ($v[0] != '-') {
                        ShowMsg("{$n}数字变量值-必须在开头", "-1");
                        exit();
                    }
                    if (strlen($v) == 1) {
                        ShowMsg("{$n}数字变量值-后面必须跟0-9", "-1");
                        exit();
                    }
                    if (strpos($v, '-', 1) !== false) {
                        ShowMsg("{$n}数字变量值不能包含多个-", "-1");
                        exit();
                    }
                }
            } else {
                $v = 0;
            }
        }
        $v = $dsql->Esc($v);
        $k = $dsql->Esc($k);
        $dsql->ExecuteNoneQuery("UPDATE `#@__sysconfig` SET `value`='$v' WHERE varname='$k'");
    }
    ReWriteConfig();
    ShowMsg("成功修改系统设置", "sys_info.php");
    exit();
}
//添加新变量
else if ($dopost == 'add') {
    CheckCSRF();
    if ($vartype == 'bool' && ($nvarvalue != 'Y' && $nvarvalue != 'N')) {
        ShowMsg("布尔变量值必须为Y或N", "-1");
        exit();
    }
    if ($vartype == 'number') {
        if ($nvarvalue != '' && preg_match("#[^0-9-]#i", $nvarvalue)) {
            ShowMsg("数字变量值必须由0-9组成", "-1");
            exit();
        }
        if ($nvarvalue != '') {
            if (strpos($nvarvalue, '-') !== false) {
                if ($nvarvalue[0] != '-') {
                    ShowMsg("数字变量值-必须在开头", "-1");
                    exit();
                }
                if (strlen($nvarvalue) == 1) {
                    ShowMsg("数字变量值-后面必须跟0-9", "-1");
                    exit();
                }
                if (strpos($nvarvalue, '-', 1) !== false) {
                    ShowMsg("数字变量值不能包含多个-", "-1");
                    exit();
                }
            }
        } else {
            $nvarvalue = 0;
        }
    }
    if (trim($nvarname) == '' || preg_match("#[^a-z_]#i", $nvarname)) {
        ShowMsg("变量名称不能为空并且必须由a-z_组成", "-1");
        exit();
    }
    $row = $dsql->GetOne("SELECT varname FROM `#@__sysconfig` WHERE varname LIKE '$nvarname' ");
    if (is_array($row)) {
        ShowMsg("变量名称已经存在，请重新填写", "-1");
        exit();
    }
    $row = $dsql->GetOne("SELECT aid FROM `#@__sysconfig` ORDER BY aid DESC");
    $aid = intval($row['aid']) + 1;
    $varmsg = HtmlReplace($varmsg);
    $inquery = "INSERT INTO `#@__sysconfig` (`aid`,`varname`,`info`,`value`,`type`,`groupid`) VALUES ('$aid','$nvarname','$varmsg','$nvarvalue','$vartype','$vargroup')";
    $rs = $dsql->ExecuteNoneQuery($inquery);
    if (!$rs) {
        ShowMsg("新增变量失败，有非法字符", "sys_info.php?gp={$vargroup}");
        exit();
    }
    if (!is_writeable($configfile)) {
        ShowMsg("写入{$configfile}失败，无法保存系统配置", "sys_info.php?gp={$vargroup}");
        exit();
    } else {
        ReWriteConfig();
        ShowMsg("成功添加一个变量", "sys_info.php?gp={$vargroup}");
        exit();
    }
}
//搜索配置
else if ($dopost == 'search') {
    $keywords = isset($keywords) ? strip_tags($keywords) : '';
    $i = 1;
    $configstr = <<<EOT
<table id="tdSearch" class="table table-borderless">
<thead>
    <tr>
        <td width="350">变量说明</td>
        <td scope="col">变量值</td>
        <td scope="col">变量名称</td>
    </tr>
</thead>
<tbody>
EOT;
    echo $configstr;
    if ($keywords) {
        $dsql->SetQuery("SELECT * FROM `#@__sysconfig` WHERE info LIKE '%{$keywords}%' OR varname LIKE '%{$keywords}%' ORDER BY aid ASC");
        $dsql->Execute();
        while ($row = $dsql->GetArray()) {
        ?>
        <tr>
            <td><?php echo $row['info'];?></td>
            <td>
                <?php
                if ($row['type'] == 'bool') {
                    $c1 = '';
                    $c2 = '';
                    $row['value'] == 'Y' ? $c1 = "checked" : $c2 = "checked";
                    echo "<label><input type='radio' name='edit_{$row['varname']}' value='Y' $c1> 是</label> ";
                    echo "<label><input type='radio' name='edit_{$row['varname']}' value='N' $c2> 否</label> ";
                } else if ($row['type'] == 'bstring') {
                    echo "<textarea name='edit_{$row['varname']}' id='edit_{$row['varname']}' class='admin-textarea-xl'>".dede_htmlspecialchars($row['value'])."</textarea>";
                } else if ($row['type'] == 'number') {
                    echo "<input type='text' name='edit_{$row['varname']}' id='edit_{$row['varname']}' value='{$row['value']}' class='w-75'>";
                } else {
                    echo "<input type='text' name='edit_{$row['varname']}' id='edit_{$row['varname']}' value=\"".dede_htmlspecialchars($row['value'])."\" class='w-75'>";
                }
                ?>
            </td>
            <td><?php echo $row['varname'];?></td>
        </tr>
        <?php }?>
    </tbody>
</table>
<?php exit;}
    echo '</tbody></table>';
    exit;
} else if ($dopost == 'make_encode') {
    $chars = 'abcdefghigklmnopqrstuvwxwyABCDEFGHIGKLMNOPQRSTUVWXWY0123456789';
    $hash = '';
    $length = rand(28, 32);
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    echo $hash;
    exit();
}
include DedeInclude('templets/sys_info.htm');
?>