<?php
/**
 * 模块插件
 *
 * @version        $id:inc_menu_module.php 10:32 2010年7月21日 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__)."/../config.php");
//载入模块菜单
$moduleset = '';
$dsql->SetQuery("SELECT * FROM `#@__sys_module` ORDER BY id DESC");
$dsql->Execute('mm');
while ($row = $dsql->GetObject('mm')) {
    $moduleset .= $row->menustring."\r\n";
}
//载入插件菜单
$plusset = '';
$dsql->SetQuery("SELECT * FROM `#@__plus` WHERE isshow=1 ORDER BY aid ASC");
$dsql->Execute();
while ($row = $dsql->GetObject()) {
    $row->menustring = str_replace('', '', $row->menustring);
    $plusset .= $row->menustring."\r\n";
}
$adminMenu = '';
if ($cuserLogin->getUserType() >= 10) {
    $adminMenu = DEDEBIZ_SAFE_MODE ? "" : "<m:top item='6_' name='模块管理' icon='fa fa-database' data-target='module'>
    <m:item name='模块管理' link='module_main.php' rank='sys_module' target='main' />
    <m:item name='模块打包' link='module_make.php' rank='sys_module' target='main' />
    </m:top>";
}
$menusMoudle = "
$adminMenu
<m:top item='7_' name='插件管理' icon='fa fa-plug' data-target='plus'>
    <m:item name='插件列表' link='plus_main.php' rank='10' target='main' />
    <m:item name='上传插件' link='module_upload.php' rank='sys_module' target='main' />
    $plusset
</m:top>
$moduleset
";