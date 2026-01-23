<?php
/**
 * 防采集字符串
 *
 * @version        $Id: article_string_mix.php 1 14:31 2010年7月12日Z tianya $
 * @package        DedeX.Helpers
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__).'/config.php');
CheckPurview('sys_StringMix');
if(empty($dopost)) $dopost = '';
if(empty($allsource)) $allsource = '';
else $allsource = stripslashes($allsource);
$m_file = DEDEDATA."/downmix.data.php";
//保存
if ($dopost == "save") {
    CheckCSRF();
    $fp = fopen($m_file, 'w');
    flock($fp, 3);
    fwrite($fp, $allsource);
    fclose($fp);
    ShowMsg("成功保存防采集字符串", "article_string_mix.php");
    exit();
}
//读出
if (empty($allsource) && filesize($m_file)>0) {
    $fp = fopen($m_file, 'r');
    $allsource = fread($fp, filesize($m_file));
    fclose($fp);
}
include DedeInclude('templets/article_string_mix.htm');
?>