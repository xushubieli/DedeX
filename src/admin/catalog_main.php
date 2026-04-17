<?php
/**
 * 栏目管理
 *
 * @version        $id:catalog_main.php 14:31 2010年7月12日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC."/typelink/typeunit.class.admin.php");
$userChannel = $cuserLogin->getUserChannel();
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
include DedeInclude('templets/catalog_main.htm');
?>