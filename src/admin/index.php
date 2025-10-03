<?php
/**
 * 后台管理主页
 *
 * @version        $id:index.php 11:06 2010年7月13日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
if (strpos($_SERVER['SERVER_SOFTWARE'], 'PHP') === 0 && $_SERVER['REQUEST_URI'] === dirname($_SERVER['SCRIPT_NAME'])) {
    header("Location: {$_SERVER['REQUEST_URI']}/", true, 301);
    exit;
}
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC.'/dedetag.class.php');
require(DEDEADMIN.'/inc/inc_menu.php');
require(DEDEADMIN.'/inc/inc_menu_func.php');
$openitem = (empty($openitem) ? 1 : $openitem);
include(DEDEADMIN.'/templets/index.htm');
exit();
?>