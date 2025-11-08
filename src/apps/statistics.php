<?php
/**
 * 流量统计
 *
 * @version        $id:statistics.php$
 * @package        DedeX.Site
 * @license        GNU GPL v2 (/license.txt)
 */
define('IS_DEDEAPI', TRUE);
require_once(dirname(__FILE__)."/../system/common.inc.php");
require_once(DEDEINC."/libraries/statistics.class.php");
if (empty($dopost)) $dopost = '';
if ($dopost == "stat") {
    header('Content-Type: application/json; charset=utf-8');
} else {
    header('Content-Type: application/javascript; charset=utf-8');
}
$stat = new DedeStatistics;
if ($dopost == "stat") {
    $rs = $stat->Record();
    $result = array(
        "code" => $rs ? 200 : 500,
        "data" => $rs ? "success" : "record failed",
    );
    echo json_encode($result);
    exit;
}
$v = $stat->GetStat();
echo $v;
?>