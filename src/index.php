<?php
/**
 * 首页
 * 
 * @version        $id:index.php 9:23 2022-05-16 tianya $
 * @package        DedeBIZ.Site
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
define("DEDEINDEX", true);
if (!file_exists(dirname(__FILE__).'/data/common.inc.php')) {
    header('Location:install/index.php');
    exit();
}
if (isset($_GET['upcache']) || !file_exists('index.html')) {
    require_once(dirname(__FILE__)."/system/common.inc.php");
    if (DEBUG_LEVEL == TRUE) {
        $ttt1 = ExecTime();
    }
    require_once DEDEINC."/archive/partview.class.php";
    $GLOBALS['_arclistEnv'] = 'index';
    $row = $dsql->GetOne("SELECT * FROM `#@__homepageset`");
    $row['templet'] = MfTemplet($row['templet']);
    $pv = new PartView();
    $pv->SetTemplet($cfg_basedir.$cfg_templets_dir."/".$row['templet']);
    $row['showmod'] = isset($row['showmod']) ? $row['showmod'] : 0;
    if ($row['showmod'] == 1) {
        $pv->SaveToHtml(dirname(__FILE__).'/index.html');
        if (DEBUG_LEVEL == TRUE) {
            $queryTime = ExecTime() - $ttt1;
            if (PHP_SAPI === 'cli') {
                echo '首页：生成花费时间：'.$queryTime."\r\n";
            } else {
                echo DedeAlert("页面加载总消耗时间：{$queryTime}", ALERT_DANGER);
            }
        }
        include(dirname(__FILE__).'/index.html');
        exit();
    } else {
        $pv->Display();
        if (DEBUG_LEVEL == TRUE) {
            $queryTime = ExecTime() - $ttt1;
            if (PHP_SAPI === 'cli') {
                echo '首页：加载花费时间：'.$queryTime."\r\n";
            } else {
                echo DedeAlert("页面加载总消耗时间：{$queryTime}", ALERT_DANGER);
            }
        }
        exit();
    }
} else {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location:index.html');
}
?>