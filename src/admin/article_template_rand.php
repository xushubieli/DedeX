<?php
/**
 * 文档随机模板
 *
 * @version        $Id: article_template_rand.php 1 14:31 2010年7月12日Z tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/libraries/oxwindow.class.php');
CheckPurview('sys_StringMix');
if(empty($dopost)) $dopost = '';
$templates = empty($templates) ? '' : stripslashes($templates);
$m_file = DEDEDATA.'/template.rand.php';
//保存配置
if ($dopost == 'save') {
    CheckCSRF();
    $fp = fopen($m_file, 'w');
    flock($fp, 3);
    fwrite($fp, $templates);
    fclose($fp);
    ShowMsg("成功保存文档随机模板", "article_template_rand.php");
    exit();
}
//对旧文档进行随机模板处理
else if ($dopost == 'makeold') {
    CheckCSRF();
    set_time_limit(3600);
    if (!file_exists($m_file)) {
        ShowMsg("文档随机模板文件不存在", "article_template_rand.php");
        exit();
    }
    require_once($m_file);
    if ($cfg_tamplate_rand == 0) {
        ShowMsg("未启用文档随机模板", "article_template_rand.php");
        exit();
    }
    $totalTmp = count($cfg_tamplate_arr);
    if ($totalTmp < 2) {
        ShowMsg("文档模板数量必须至少2个", "article_template_rand.php");
        exit();
    }
    for ($i = 0; $i < 10; $i++) {
        $temp = $cfg_tamplate_arr[mt_rand(0, $totalTmp - 1)];
        $dsql->ExecuteNoneQuery("UPDATE `#@__addonarticle` SET templet='$temp' WHERE RIGHT(aid, 1)='$i' ");
    }
    ShowMsg("启用文档随机模板", "article_template_rand.php");
    exit();
}
//清除全部的指定模板
else if ($dopost == 'clearold') {
    CheckCSRF();
    $dsql->ExecuteNoneQuery("UPDATE `#@__addonarticle` SET templet='' ");
    $dsql->ExecuteNoneQuery("OPTIMIZE TABLE `#@__addonarticle` ");
    ShowMsg("取消文档随机模板成功", "article_template_rand.php");
    exit();
}
//读出
if (empty($templates) && filesize($m_file) > 0) {
    $fp = fopen($m_file, 'r');
    $templates = fread($fp, filesize($m_file));
    fclose($fp);
}
make_hash();
include DedeInclude('templets/article_template_rand.htm');
?>