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
header('Content-Type: application/javascript; charset=utf-8');
if (empty($dopost)) $dopost = '';
$stat = new DedeStatistics;
if ($dopost == "stat") {
    $rs = $stat->Record();
    $result = array(
        "code" => 200,
        "data" => "success",
    );
    echo json_encode($result);
    exit;
}
$v = $stat->GetStat();
echo $v;
?>