<?php
/**
 * 后台面板
 *
 * @version        $id:index_body.php 11:06 2010年7月13日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/image.func.php');
require_once(DEDEINC.'/dedetag.class.php');
if (empty($dopost)) {
    include DedeInclude('templets/index_body.htm');
    exit();
} if ($dopost == 'get_statistics') {
    require_once(DEDEINC."/libraries/statistics.class.php");
    $sdate = empty($sdate) ? 0 : intval($sdate);
    $stat = new DedeStatistics;
    $rs = $stat->GetInfoByDate($sdate);
    echo json_encode(array(
        "code" => 200,
        "msg" => "",
        "result" => $rs,
    ));
    exit;
} else if ($dopost == 'get_statistics_multi') {
    require_once(DEDEINC."/libraries/statistics.class.php");
    $sdates = empty($sdates) ? array() : explode(",",preg_replace("[^\d\,]","",$sdates)) ;
    $stat = new DedeStatistics;
    $rs = $stat->GetInfoByDateMulti($sdates);
    echo json_encode(array(
        "code" => 200,
        "msg" => "",
        "result" => $rs,
    ));
    exit;
} else if ($dopost == 'safe_mode') {
    $safemsg = "目前系统运行安全模式，模板管理、数据库管理、模块管理等高危功能已暂停，启用功能，找到/system/common.inc.php文件，根据注释DEDEX_SAFE_MODE后面值为FALSE";
    $unsafemsg = "目前系统运行开发模式，建议网站上线后运行<span class='text-success'>安全模式</span>，找到/system/common.inc.php文件，根据注释DEDEX_SAFE_MODE后面值为TRUE";
    $modeStr = DEDEX_SAFE_MODE ? $safemsg : $unsafemsg;
    showmsg($modeStr, 'index_body.php', 0, 5000);
    exit;
}
?>