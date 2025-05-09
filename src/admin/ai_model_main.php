<?php
/**
 * 模型版本管理
 *
 * @version        $id:ai_model_main.php 2025 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2025 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/datalistcp.class.php');
CheckPurview('ai_ModelList');
$sql = "SELECT AM.*,A.title as aititle FROM `#@__ai_model` AM LEFT JOIN `#@__ai` A ON A.id = AM.aiid WHERE 1=1 ORDER BY AM.sortrank ASC,AM.id DESC";
$dlist = new DataListCP();
$dlist->SetTemplet(DEDEADMIN.'/templets/ai_model_main.htm');
$dlist->SetSource($sql);
$dlist->display();
?>