<?php
/**
 * 模块管理
 *
 * @version        $id:module_main.php 14:17 2010年7月20日 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__)."/config.php");
if (DEDEBIZ_SAFE_MODE) {
  die(DedeAlert("系统已启用安全模式，无法使用当前功能",ALERT_DANGER));
}
CheckPurview('sys_module');
require_once(DEDEINC."/dedemodule.class.php");
require_once(DEDEINC."/libraries/oxwindow.class.php");
if (empty($action)) $action = '';
$mdir = DEDEDATA.'/module';
$mdurl = '';
function TestWriteAble($d)
{
    $tfile = '_dedet.txt';
    $d = preg_replace("#\/$#", '', $d);
    $fp = @fopen($d.'/'.$tfile, 'w');
    if (!$fp) return FALSE;
    else {
        fclose($fp);
        $rs = @unlink($d.'/'.$tfile);
        if ($rs) return TRUE;
      else return FALSE;
    }
}
function ReWriteConfigAuto()
{
    global $dsql;
    $configfile = DEDEDATA.'/config.cache.inc.php';
    if (!is_writeable($configfile)) {
        echo "配置文件{$configfile}不支持写入，无法修改系统配置参数";
        exit();
    }
    $fp = fopen($configfile, 'w');
    flock($fp, 3);
    fwrite($fp, "<"."?php\r\n");
    $dsql->SetQuery("SELECT `varname`,`type`,`value`,`groupid` FROM `#@__sysconfig` ORDER BY aid ASC ");
    $dsql->Execute();
    while ($row = $dsql->GetArray()) {
        if (empty($row['value']) && $row['type'] == 'number') $row['value'] = 0;
        if ($row['type'] == 'number') fwrite($fp, "\${$row['varname']} = ".$row['value'].";\r\n");
        else fwrite($fp, "\${$row['varname']} = '".str_replace("'", '', $row['value'])."';\r\n");
      }
      fwrite($fp, "?".">");
      fclose($fp);
}
if ($action == '') {
    $types = array('soft' => '模块', 'templets' => '模板', 'plus' => '小插件', 'patch' => '补丁');
    $dm = new DedeModule($mdir);
    if (empty($moduletype)) $moduletype = '';
    $modules_remote = $dm->GetModuleUrlList($moduletype, $mdurl);
    $modules = array();
    $modules = $dm->GetModuleList($moduletype);
    is_array($modules) || $modules = array();
    if (is_array($modules_remote) && count($modules_remote) > 0) {
        $modules = array_merge($modules, $modules_remote);
    }
    require_once(dirname(__FILE__)."/templets/module_main.htm");
    $dm->Clear();
    exit();
} else if ($action == 'view_developoer') {
    //检验贡献者信息
    $dm = new DedeModule($mdir);
    $info = $dm->GetModuleInfo($hash);
    if ($info == null) {
        ShowMsg("获取模块信息错误，模块文件错误", -1);
        exit;
    }
    $dev_id = $info['dev_id'];
    $devURL = DEDECDNURL."/developers/$dev_id.json";
    $dhd = new DedeHttpDown();
    $dhd->OpenUrl($devURL);
    $devContent = $dhd->GetHtml();
    $devInfo = (array)json_decode($devContent);
    $offUrl = '';
    if ($devInfo['dev_type'] == 1) {
        $offUrl = "官方网址：<code>{$devInfo['offurl']}</code><br>";
    }
    $authAt = date("Y-m-d", $devInfo['auth_at']);
    if (!isset($info['dev_id'])) {
        $devInfo['dev_name'] = $info['team']."<span class='btn btn-warning btn-sm'>未认证</span>";
        $authAt = "<span class='btn btn-warning btn-sm'>未知</span>";
    }
    ShowMsg("贡献者名称：{$devInfo['dev_name']}<br>贡献者id：{$devInfo['dev_id']}<br>认证于：{$authAt}", "-1");
    exit;
} else if ($action == 'setup') {
    $dm = new DedeModule($mdir);
    $infos = $dm->GetModuleInfo($hash);
    if ($infos == null) {
        ShowMsg("获取模块信息错误，模块文件错误", -1);
        exit;
    }
    $alertMsg = ($infos['lang'] == $cfg_soft_lang ? '' : '<br>该模块的语言编码与您系统的编码不一致，请向贡献者确认它的兼容性');
    $filelists = (array)$dm->GetFileLists($hash);
    $filelist = '';
    $prvdirs = array();
    $incdir = array();
    foreach ($filelists as $v) {
        if (empty($v['name'])) continue;
        if ($v['type'] == 'dir') {
            $v['type'] = '目录';
            $incdir[] = $v['name'];
        } else {
            $v['type'] = '文件';
        }
        $filelist .= "{$v['type']}|{$v['name']}\r\n";
    }
    //检测需要的目录权限
    foreach ($filelists as $v) {
        $prvdir = preg_replace("#\/([^\/]*)$#", '/', $v['name']);
        if (!preg_match("#^\.#", $prvdir)) $prvdir = './';
        $n = TRUE;
        foreach ($incdir as $k => $v) {
            if (preg_match("#^".$v."#i", $prvdir)) {
                $n = FALSE;
                break;
            }
        }
        if (!isset($prvdirs[$prvdir]) && $n && is_dir($prvdir)) {
            $prvdirs[$prvdir][0] = 1;
            $prvdirs[$prvdir][1] = TestWriteAble($prvdir);
        }
    }
    $prvdir = "<table>\r\n";
    $prvdir .= "<tr><td width='260'>目录</td><td align='center'>可写</td></tr>\r\n";
    foreach ($prvdirs as $k => $v) {
        if ($v) $cw = "<span class='text-success'><i class='fa fa-check'></i></span>";
        else $cw = "<span class='text-danger'><i class='fa fa-times'></i></span>";
        $prvdir .= "<tr><td>$k</td><td align='center'>$cw</td></tr>";
    }
    $prvdir .= "</table>";
    $win = new OxWindow();
    $win->Init("module_main.php", "/static/web/js/admin.blank.js", "post");
    $wintitle = "安装{$infos['name']}";
    $devURL = DEDECDNURL."/developers/{$infos['dev_id']}.json";
    $dhd = new DedeHttpDown();
    $dhd->OpenUrl($devURL);
    $devContent = $dhd->GetHtml();
    $devInfo = (array)json_decode($devContent);
    $s = "未认证";
    if (($devInfo['dev_id'] == $infos['dev_id']) && !empty($devInfo['dev_id'])) {
      $s = "已认证";
    }
    $win->AddHidden("hash", $hash);
    $win->AddHidden("action", 'setupstart');
    $msg = "<tr>
    </tr>
    <tr>
        <td width='260'>模块名称</td>
        <td>{$infos['name']}</td>
    </tr>
    <tr>
        <td>语言</td>
        <td>{$infos['lang']} {$alertMsg}</td>
    </tr>
    <tr>
        <td>文件大小</td>
        <td>{$infos['filesize']}</td>
    </tr>
    <tr>
        <td>贡献者id</td>
        <td>{$infos['dev_id']} <a href='{$cfg_biz_dedebizUrl}/developer?dev_id={$infos['dev_id']}' target='_blank' class='btn btn-success btn-sm'>{$s}</a></td>
    </tr>
    <tr>
        <td>发布时间</td>
        <td>{$infos['time']}</td>
    </tr>
    <tr>
        <td>使用协议</td>
        <td><a href='module_main.php?action=showreadme&hash={$hash}' target='_blank' class='btn btn-success btn-sm'>浏览</a></td>
    </tr>
    <tr>
        <td>目录权限说明<br>/为根目录<br>./表示当前目录</td>
        <td>$prvdir</td>
    </tr>
    <tr>
        <td>模块包含的所有文件列表</td>
        <td><textarea name='filelists' id='filelists' class='admin-textarea-xl'>{$filelist}</textarea></td>
    </tr>
    <tr>
        <td>对于已存在文件处理方法</td>
        <td>
            <label><input type='radio' name='isreplace' value='1' checked> 覆盖</label>
            <label><input type='radio' name='isreplace' value='3'> 覆盖，保留副本</label>
            <label><input type='radio' name='isreplace' value='0'> 保留旧文件</label>
        </td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("ok", "");
    $win->Display();
    $dm->Clear();
    exit();
} else if ($action == 'setupstart') {
    if (!is_writeable($mdir)) {
        ShowMsg("目录{$mdir}不支持写入，这导致程序安装没法正常创建", "-1");
        exit();
    }
    $dm = new DedeModule($mdir);
    $minfos = (array)$dm->GetModuleInfo($hash);
    extract($minfos, EXTR_SKIP);
    $menustring = addslashes($dm->GetSystemFile($hash, 'menustring'));
    $indexurl = str_replace('**', '=', $indexurl);
    $query = "INSERT INTO `#@__sys_module` (`hashcode`,`modname`,`indexname`,`indexurl`,`ismember`,`menustring` ) VALUES ('$hash','$name','$indexname','$indexurl','$ismember','$menustring' ) ";
    $rs = $dsql->ExecuteNoneQuery("DELETE FROM `#@__sys_module` WHERE hashcode LIKE '$hash' ");
    $rs = $dsql->ExecuteNoneQuery($query);
    if (!$rs) {
        ShowMsg('保存数据库信息失败，无法完成安装'.$dsql->GetError(), 'javascript:;');
        exit();
    }
    $dm->WriteFiles($hash, $isreplace);
    $filename = '';
    if (!isset($autosetup) || $autosetup == 0) $filename = $dm->WriteSystemFile($hash, 'setup');
    if (!isset($autodel) || $autodel == 0) $dm->WriteSystemFile($hash, 'uninstall');
    $dm->WriteSystemFile($hash, 'readme');
    $dm->Clear();
    //用模块的程序安装
    if (!isset($autosetup) || $autosetup == 0) {
        include(DEDEDATA.'/module/'.$filename);
        exit();
    }
    //系统自动安装
    else {
        $mysql_version = $dsql->GetVersion(TRUE);
        //默认使用MySQL 4.1 以下版本的SQL语句，对大于4.1版本采用替换处理 TYPE=MyISAM ==> ENGINE=MyISAM DEFAULT CHARSET=#~lang~#
        $setupsql = $dm->GetSystemFile($hash, 'setupsql40');
        $setupsql = preg_replace("#ENGINE=MyISAM#i", 'TYPE=MyISAM', $setupsql);
        $sql41tmp = 'ENGINE=MyISAM DEFAULT CHARSET='.$cfg_db_language;
        if ($mysql_version >= 4.1) {
            $setupsql = preg_replace("#TYPE=MyISAM#i", $sql41tmp, $setupsql);
        }
        //_ROOTURL_
        if ($cfg_cmspath == '/') $cfg_cmspath = '';
        $rooturl = $cfg_basehost.$cfg_cmspath;
        $setupsql = preg_replace("#_ROOTURL_#i", $rooturl, $setupsql);
        $setupsql = preg_replace("#[\r\n]{1,}#", "\n", $setupsql);
        $sqls = preg_split('/;[ \t]{0,}\n/', $setupsql);
        foreach ($sqls as $sql) {
            if (trim($sql) != '') $dsql->ExecuteNoneQuery($sql);
        }
        ReWriteConfigAuto();
        $rflwft = "<script>\r\n";
        $rflwft .= "if (window.navigator.userAgent.indexOf('MSIE')>=1) top.document.frames.menu.location = 'index_menu.php';\r\n";
        $rflwft .= "else top.document.getElementsByName('menu').src = 'index_menu.php';\r\n";
        $rflwft .= "</script>";
        echo $rflwft;
        UpDateCatCache();
        ShowMsg('模块安装完成', 'module_main.php');
        exit();
    }
} else if ($action == 'del') {
    $dm = new DedeModule($mdir);
    $infos = $dm->GetModuleInfo($hash);
    $alertMsg = ($infos['lang'] == $cfg_soft_lang ? '' : '<br>该模块的语言编码与您系统的编码不一致，请向贡献者确认它的兼容性');
    $dev_id = empty($infos['dev_id'])? "<a href='{$cfg_biz_dedebizUrl}/developer' target='_blank' class='btn btn-warning btn-sm'>未认证</a>" : "{$infos['dev_id']} <a href='{$cfg_biz_dedebizUrl}/developer?dev_id={$infos['dev_id']}' target='_blank' class='btn btn-success btn-sm'>已认证</a>";
    $win = new OxWindow();
    $win->Init("module_main.php", "/static/web/js/admin.blank.js", "post");
    $wintitle = "删除{$infos['name']}";
    $win->AddHidden("hash", $hash);
    $win->AddHidden("action", "delok");
    $msg = "<tr>
        <td colspan='2'>
            <div class='alert alert-warning'>删除模块仅删除模块安装后文件，用<a href='module_main.php?hash={$hash}&action=uninstall'>卸载程序</a>来删除</div>
        </td>
    </tr>
    <tr>
        <td width='260'>模块名称</td>
        <td>{$infos['name']}</td>
    </tr>
    <tr>
        <td>语言</td>
        <td>{$infos['lang']} {$alertMsg}</td>
    </tr>
    <tr>
        <td>文件大小</td>
        <td>{$infos['filesize']}</td>
    </tr>
    <tr>
        <td>贡献者id</td>
        <td>{$dev_id}</td>
    </tr>
    <tr>
        <td>发布时间</td>
        <td>{$infos['time']}</td>
    </tr>
    <tr>
        <td>使用协议</td>
        <td><a href='module_main.php?action=showreadme&hash={$hash}' target='_blank' class='btn btn-success btn-sm'>浏览</a></td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("ok", "");
    $win->Display();
    $dm->Clear();
    exit();
    } else if ($action == 'delok') {
    $dm = new DedeModule($mdir);
    $modfile = $mdir."/".$dm->GetHashFile($hash);
    unlink($modfile) or die("删除文件{$modfile}失败");
    ShowMsg("成功删除一个模块文件", "module_main.php");
    exit();
} else if ($action == 'uninstall') {
    $dm = new DedeModule($mdir);
    $infos = $dm->GetModuleInfo($hash);
    if ($infos['url'] == '') $infos['url'] = ' ';
    $alertMsg = ($infos['lang'] == $cfg_soft_lang ? '' : '<br>该模块的语言编码与您系统的编码不一致，请向贡献者确认它的兼容性');
    $filelists = (array)$dm->GetFileLists($hash);
    $filelist = '';
    foreach ($filelists as $v) {
        if (empty($v['name'])) continue;
        if ($v['type'] == 'dir') $v['type'] = '目录';
        else $v['type'] = '文件';
        $filelist .= "{$v['type']}|{$v['name']}\r\n";
    }
    $dev_id = empty($infos['dev_id'])? "<a href='{$cfg_biz_dedebizUrl}/developer' target='_blank' class='btn btn-warning btn-sm'>未认证</a>" : "{$infos['dev_id']} <a href='{$cfg_biz_dedebizUrl}/developer?dev_id={$infos['dev_id']}' target='_blank' class='btn btn-success btn-sm'>已认证</a>";
    $win = new OxWindow();
    $win->Init("module_main.php", "/static/web/js/admin.blank.js", "post");
    $wintitle = "卸载{$infos['name']}";
    $win->AddHidden("hash", $hash);
    $win->AddHidden("action", 'uninstallok');
    $msg = "<tr>
        <td width='260'>模块名称</td>
        <td>{$infos['name']}</td>
    </tr>
    <tr>
        <td>语言</td>
        <td>{$infos['lang']} {$alertMsg}</td>
    </tr>
    <tr>
        <td>文件大小</td>
        <td>{$infos['filesize']}</td>
    </tr>
    <tr>
        <td>贡献者id</td>
        <td>{$dev_id}</td>
    </tr>
    <tr>
        <td>发布时间</td>
        <td>{$infos['time']}</td>
    </tr>
    <tr>
        <td>使用协议</td>
        <td><a href='module_main.php?action=showreadme&hash={$hash}' target='_blank' class='btn btn-success btn-sm'>浏览</a></td>
    </tr>
    <tr>
        <td>模块文件</td>
        <td><textarea name='filelists' id='filelists' class='admin-textarea-xl'>{$filelist}</textarea></td>
    </tr>
    <tr>
        <td>对于模块的文件处理方法</td>
        <td>
            <label><input type='radio' name='isreplace' value='0' checked> 手工删除文件，仅运行卸载程序</label>
            <label><input type='radio' name='isreplace' value='2'> 删除模块的所有文件</label>
        </td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("ok", "");
    $win->Display();
    $dm->Clear();
    exit();
} else if ($action == 'uninstallok') {
    $dsql->ExecuteNoneQuery("DELETE FROM `#@__sys_module` WHERE hashcode LIKE '$hash' ");
    $dm = new DedeModule($mdir);
    $minfos = (array)$dm->GetModuleInfo($hash);
    extract($minfos, EXTR_SKIP);
    if (!isset($moduletype) || $moduletype != 'patch') {
        $dm->DeleteFiles($hash, $isreplace);
    }
    @$dm->DelSystemFile($hash, 'readme');
    @$dm->DelSystemFile($hash, 'setup');
    $dm->Clear();
    if (!isset($autodel) || $autodel == 0) {
        include(DEDEDATA."/module/{$hash}-uninstall.php");
        @unlink(DEDEDATA."/module/{$hash}-uninstall.php");
        exit();
    } else {
        @$dm->DelSystemFile($hash, 'uninstall');
        $delsql = $dm->GetSystemFile($hash, 'delsql');
        if (trim($delsql) != '') {
            $sqls = explode(';', $delsql);
            foreach ($sqls as $sql) {
                if (trim($sql) != '') $dsql->ExecuteNoneQuery($sql);
            }
        }
        ReWriteConfigAuto();
        $rflwft = "<script>\r\n";
        $rflwft .= "if (window.navigator.userAgent.indexOf('MSIE')>=1) top.document.frames.menu.location = 'index_menu.php';\r\n";
        $rflwft .= "else top.document.getElementsByName('menu').src = 'index_menu.php';\r\n";
        $rflwft .= "</script>";
        echo $rflwft;
        ShowMsg('模块卸载完成', 'module_main.php');
        exit();
    }
} else if ($action == 'showreadme') {
    $dm = new DedeModule($mdir);
    $msg = $dm->GetSystemFile($hash, 'readme');
    $msg = preg_replace("/(.*)<body/isU", "", $msg);
    $msg = preg_replace("/<\/body>(.*)/isU", "", $msg);
    $dm->Clear();
    $win = new OxWindow();
    $win->Init("module_main.php", "/static/web/js/admin.blank.js", "post");
    $wintitle = "使用说明";
    $win->AddMsgItem("<tr><td>$msg</td></tr>");
    $winform = $win->GetWindow("hand");
    $win->Display();
    exit();
} else if ($action == 'view') {
    $dm = new DedeModule($mdir);
    $infos = $dm->GetModuleInfo($hash);
    if ($infos['url'] == '') $infos['url'] = ' ';
    $alertMsg = ($infos['lang'] == $cfg_soft_lang ? '' : '<br>该模块的语言编码与您系统的编码不一致，请向贡献者确认它的兼容性');
    $filelists = (array)$dm->GetFileLists($hash);
    $filelist = '';
    $setupinfo = '';
    $devURL = DEDECDNURL."/developers/{$infos['dev_id']}.json";
    $dhd = new DedeHttpDown();
    $dhd->OpenUrl($devURL);
    $devContent = $dhd->GetHtml();
    $devInfo = (array)json_decode($devContent);
    $s = "未认证";
    if (($devInfo['dev_id'] == $infos['dev_id']) && !empty($devInfo['dev_id'])) {
      $s = "已认证";
    }
    foreach ($filelists as $v) {
        if (empty($v['name'])) continue;
        if ($v['type'] == 'dir') $v['type'] = '目录';
        else $v['type'] = '文件';
        $filelist .= "{$v['type']}|{$v['name']}\r\n";
    }
    if (file_exists(DEDEDATA."/module/{$hash}-readme.php")) {
        $setupinfo = "已安装 <a href='module_main.php?action=uninstall&hash={$hash}' class='btn btn-danger btn-sm'>卸载</a>";
    } else {
        $setupinfo = "未安装 <a href='module_main.php?action=setup&hash={$hash}' class='btn btn-success btn-sm'>安装</a>";
    }
    $dev_id = empty($infos['dev_id'])? "<a href='module_main.php?action=setup&hash={$hash}' class='btn btn-success btn-sm'>安装</a><a href='{$cfg_biz_dedebizUrl}/developer' target='_blank' class='btn btn-success btn-sm'>{$s}</a>" : "{$infos['dev_id']} <a href='module_main.php?action=setup&hash={$hash}' class='btn btn-success btn-sm'>安装</a><a href='{$cfg_biz_dedebizUrl}/developer?dev_id={$infos['dev_id']}' target='_blank' class='btn btn-success btn-sm'>{$s}</a>";
    $win = new OxWindow();
    $win->Init("", "/static/web/js/admin.blank.js", "");
    $wintitle = "{$infos['name']}";
    $msg = "<tr>
        <td width='260'>模块名称</td>
        <td>{$infos['name']}</td>
    </tr>
    <tr>
        <td>语言</td>
        <td>{$infos['lang']} {$alertMsg}</td>
    </tr>
    <tr>
        <td>文件大小</td>
        <td>{$infos['filesize']}</td>
    </tr>
    <tr>
        <td>贡献者id</td>
        <td>{$dev_id}</td>
    </tr>
    <tr>
        <td>发布时间</td>
        <td>{$infos['time']}</td>
    </tr>
    <tr>
        <td>使用协议</td>
        <td><a href='module_main.php?action=showreadme&hash={$hash}' target='_blank' class='btn btn-success btn-sm'>浏览</a></td>
    </tr>
    <tr>
        <td colspan='2'><textarea name='filelists' id='filelists' class='admin-textarea-xl'>{$filelist}</textarea></td>
    </tr>";
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", false);
    $win->Display();
    $dm->Clear();
    exit();
} else if ($action == 'edit') {
    $dm = new DedeModule($mdir);
    $minfos = (array)$dm->GetModuleInfo($hash);
    extract($minfos, EXTR_SKIP);
    if (!isset($lang)) $lang = 'gb2312';
    if (!isset($moduletype)) $moduletype = 'soft';
    $menustring = $dm->GetSystemFile($hash, 'menustring');
    $setupsql40 = dede_htmlspecialchars($dm->GetSystemFile($hash, 'setupsql40'));
    $readmetxt = $dm->GetSystemFile($hash, 'readme');
    $delsql = $dm->GetSystemFile($hash, 'delsql');
    $filelist = $dm->GetSystemFile($hash, 'oldfilelist', false);
    $indexurl = str_replace('**', '=', $indexurl);
    $dm->Clear();
    require_once(dirname(__FILE__).'/templets/module_edit.htm');
    exit();
} else if ($action == 'download') {
    ShowMsg("不支持模块下载功能", "javascript:;");
}
?>