<?php
/**
 * 模块生成
 *
 * @version        $id:module_make.php 14:17 2010年7月20日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
@set_time_limit(0);
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC."/dedemodule.class.php");
if (DEDEX_SAFE_MODE) {
    die(DedeAlert("系统已启用安全模式，无法使用当前功能", ALERT_DANGER));
}
CheckPurview('sys_module');
if (empty($action)) $action = '';
if ($action == '') {
    $modules = array();
    require_once(dirname(__FILE__)."/templets/module_make.htm");
    exit();
} else if ($action=='gethash') {
    if (!isset($email)) $email = '';
    echo md5($modulname.$email);
    exit();
}
//生成项目
else if ($action == 'make') {
    $filelist = str_replace("\r", "\n", trim($filelist));
    $filelist = trim(preg_replace("#[\n]{1,}#", "\n", $filelist));
    if ($filelist == '') {
        ShowMsg("模块创建失败，请重新填写模块文件列表", "-1");
        exit();
    }
    //去除转义
    foreach ($_POST as $k => $v) $$k = stripslashes($v);
    if (!isset($autosetup)) $autosetup = 0;
    if (!isset($autodel)) $autodel = 0;
    if (!isset($email)) $email = '';
    $mdir = DEDEDATA.'/module';
    $hashcode = md5($modulname.$email);
    $moduleFilename = $mdir.'/'.$hashcode.'.xml';
    $menustring = base64_encode($menustring);
    $indexurl = str_replace('=', '**', $indexurl);
    $dm = new DedeModule($mdir);
    if ($dm->HasModule($hashcode)) {
        $dm->Clear();
        ShowMsg("已存在同名模块，更新该模块先删除：module/{$hashcode}.xml", "-1");
        exit();
    }
    $readmef = $setupf = $uninstallf = '';
    if (empty($readmetxt)) {
        $readme_tmp = isset($_FILES['readme']['tmp_name']) ? $_FILES['readme']['tmp_name'] : (isset($readme) ? $readme : '');
        move_uploaded_file($readme_tmp, $mdir."/{$hashcode}-r.html") or die("请重新填写使用说明文件");
        $readmef = $dm->GetEncodeFile($mdir."/{$hashcode}-r.html", TRUE);
    } else {
        $readmetxt = preg_replace("#[\r\n]{1,}#", "<br>\r\n", $readmetxt);
        $readmef = base64_encode(trim($readmetxt));
    }
    if ($autosetup == 0) {
        $setup_tmp = isset($_FILES['setup']['tmp_name']) ? $_FILES['setup']['tmp_name'] : (isset($setup) ? $setup : '');
        move_uploaded_file($setup_tmp, $mdir."/{$hashcode}-s.php") or die("请重新填写程序安装");
        $setupf = $dm->GetEncodeFile($mdir."/{$hashcode}-s.php", TRUE);
    }
    if ($autodel == 0) {
        $uninstall_tmp = isset($_FILES['uninstall']['tmp_name']) ? $_FILES['uninstall']['tmp_name'] : (isset($uninstall) ? $uninstall : '');
        move_uploaded_file($uninstall_tmp, $mdir."/{$hashcode}-u.php") or die("请重新填写删除程序");
        $uninstallf = $dm->GetEncodeFile($mdir."/{$hashcode}-u.php", TRUE);
    }
    if (trim($setupsql40) == '') $setupsql40 = '';
    else $setupsql40 = base64_encode(trim($setupsql40));
    //if (trim($setupsql41)=='') $setupsql41 = '';
    //else $setupsql41 = base64_encode(trim($setupsql41));
    if (trim($delsql) == '') $delsql = '';
    else $delsql = base64_encode(trim($delsql));
    if (!isset($devInfo)) $devInfo = array('dev_id' => '', 'pub_key' => '');
    if (!isset($moduleInfo)) $moduleInfo = '';
    $pub_key = base64url_encode($devInfo['pub_key']);
    $modulinfo = "<module>
<baseinfo>
name={$modulname}
dev_id={$devInfo['dev_id']}
pubkey={$pub_key}
info={$moduleInfo}
time={$mtime}
hash={$hashcode}
indexname={$indexname}
indexurl={$indexurl}
ismember={$ismember}
autosetup={$autosetup}
autodel={$autodel}
lang=utf-8
moduletype={$moduletype}
</baseinfo>
<systemfile>
<menustring>
{$menustring}
</menustring>
<readme>
{$readmef}
</readme>
<setupsql40>
{$setupsql40}
</setupsql40>
<delsql>
{$delsql}
</delsql>
<setup>
{$setupf}
</setup>
<uninstall>
{$uninstallf}
</uninstall>
<oldfilelist>
{$filelist}
</oldfilelist>
</systemfile>
";
    $filelists = explode("\n", $filelist);
    foreach ($filelists as $v) {
        $v = trim($v);
        if (!empty($v)) $dm->MakeEncodeFileTest(dirname(__FILE__), $v);
    }
    //测试无误后编译安装包
    $fp = fopen($moduleFilename, 'w');
    fwrite($fp, $modulinfo);
    fwrite($fp, "<modulefiles>\r\n");
    foreach ($filelists as $v) {
        $v = trim($v);
        if (!empty($v)) $dm->MakeEncodeFile(dirname(__FILE__), $v, $fp);
    }
    fwrite($fp, "</modulefiles>\r\n");
    fwrite($fp, "</module>\r\n");
    fclose($fp);
    ShowMsg("成功更新一个模块插件", "module_main.php");
    exit();
}
//修改项目
else if ($action == 'edit') {
    $filelist = str_replace("\r", "\n", trim($filelist));
    $filelist = trim(preg_replace("#[\n]{1,}#", "\n", $filelist));
    if ($filelist=="") {
        ShowMsg("模块创建失败，请重新填写模块文件列表", "-1");
        exit();
    }
    //已经去除转义
    foreach ($_POST as $k => $v) $$k = stripslashes($v);
    if (!isset($autosetup)) $autosetup = 0;
    if (!isset($autodel)) $autodel = 0;
    $mdir = DEDEDATA.'/module';
    $hashcode = $hash;
    $moduleFilename = $mdir.'/'.$hashcode.'.xml';
    $modulname = str_replace('=', '', $modulname);
    $indexurl = str_replace('=', '**', $indexurl);
    $menustring = base64_encode($menustring);
    $dm = new DedeModule($mdir);
    $readmef = base64_encode($readmetxt);
    $setupf = $uninstallf = '';
    //编译setup文件
    $setup_tmp = isset($_FILES['setup']['tmp_name']) ? $_FILES['setup']['tmp_name'] : (isset($setup) ? $setup : '');
    if (is_uploaded_file($setup_tmp)) {
        move_uploaded_file($setup_tmp, $mdir."/{$hashcode}-s.php") or die("您没上传，或系统无法把setup文件移动到模块目录");
        $setupf = $dm->GetEncodeFile($mdir."/{$hashcode}-s.php", TRUE);
    } else {
        if ($autosetup == 0) $setupf = base64_encode($dm->GetSystemFile($hashcode, 'setup'));
    }
    //编译uninstall文件
    $uninstall_tmp = isset($_FILES['uninstall']['tmp_name']) ? $_FILES['uninstall']['tmp_name'] : (isset($uninstall) ? $uninstall : '');
    if (is_uploaded_file($uninstall_tmp)) {
        move_uploaded_file($uninstall_tmp, $mdir."/{$hashcode}-u.php") or die("您没上传，或系统无法把uninstall文件移动到模块目录");
        $uninstallf = $dm->GetEncodeFile($mdir."/{$hashcode}-u.php", true);
    } else {
        if ($autodel == 0) $uninstallf = base64_encode($dm->GetSystemFile($hashcode, 'uninstall'));
    }
    if (trim($setupsql40) == '') $setupsql40 = '';
    else $setupsql40 = base64_encode(htmlspecialchars_decode(trim($setupsql40)));
    //if (trim($setupsql41)=='') $setupsql41 = '';
    //else $setupsql41 = base64_encode(trim($setupsql41));
    if (trim($delsql) == '') $delsql = '';
    else $delsql = base64_encode(strip_tags(trim($delsql)));
    if (!isset($devInfo)) $devInfo = array('dev_id' => '', 'pub_key' => '');
    if (!isset($moduleInfo)) $moduleInfo = '';
    $pub_key = base64url_encode($devInfo['pub_key']);
    $modulinfo = "<module>
<baseinfo>
name={$modulname}
dev_id={$devInfo['dev_id']}
pubkey={$pub_key}
info={$moduleInfo}
time={$mtime}
hash={$hashcode}
indexname={$indexname}
indexurl={$indexurl}
ismember={$ismember}
autosetup={$autosetup}
autodel={$autodel}
lang=utf-8
moduletype={$moduletype}
</baseinfo>
<systemfile>
<menustring>
{$menustring}
</menustring>
<readme>
{$readmef}
</readme>
<setupsql40>
{$setupsql40}
</setupsql40>
<delsql>
{$delsql}
</delsql>
<setup>
{$setupf}
</setup>
<uninstall>
{$uninstallf}
</uninstall>
<oldfilelist>
{$filelist}
</oldfilelist>
</systemfile>
";
    if ($rebuild == 'yes') {
        $filelists = explode("\n", $filelist);
        foreach ($filelists as $v) {
            $v = trim($v);
            if (!empty($v)) $dm->MakeEncodeFileTest(dirname(__FILE__), $v);
        }
        //测试无误后编译安装包
        $fp = fopen($moduleFilename, 'w');
        fwrite($fp, $modulinfo."\r\n");
        fwrite($fp, "<modulefiles>\r\n");
        foreach ($filelists as $v) {
            $v = trim($v);
            if (!empty($v)) $dm->MakeEncodeFile(dirname(__FILE__), $v, $fp);
        }
        fwrite($fp, "</modulefiles>\r\n");
        fwrite($fp, "</module>\r\n");
        fclose($fp);
    } else {
        $fxml = $dm->GetFileXml($hashcode);
        $fp = fopen($moduleFilename, 'w');
        fwrite($fp, $modulinfo."\r\n");
        fwrite($fp, $fxml);
        fclose($fp);
    }
    ShowMsg("成功更新一个模块插件", "module_main.php");
    exit();
}
?>