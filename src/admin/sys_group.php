<?php
/**
 * 会员组管理
 *
 * @version        $id:sys_group.php 22:28 2010年7月20日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_Group');
if (empty($dopost)) $dopost = '';
include DedeInclude('templets/sys_group.htm');
?>