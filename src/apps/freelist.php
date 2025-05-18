<?php
/**
 * 自由列表
 *
 * @version        $id:freelist.php$
 * @package        DedeX.Site
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/../system/common.inc.php");
require_once(DEDEINC."/archive/freelist.class.php");
if (!empty($lid)) $tid = $lid;
$tid = (isset($tid) && is_numeric($tid) ? $tid : 0);
if ($tid == 0) die("dedex");
$fl = new FreeList($tid);
$fl->Display();
?>