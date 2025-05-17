<?php
/**
 * 仪表盘
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
} if ($dopost == 'get_articles') {
?>
<div class="table-responsive">
    <table class="table table-borderless">
        <tbody>
        <?php
        $userCatalogSql = '';
        if (count($admin_catalogs) > 0) {
            $admin_catalog = join(',', $admin_catalogs);
            $userCatalogSql = "AND arc.typeid IN($admin_catalog) ";
        }
        $query = "SELECT arc.id, arc.arcrank, arc.title, arc.typeid, arc.mid, arc.pubdate, arc.channel, ch.editcon, tp.typename FROM `#@__archives` arc LEFT JOIN `#@__channeltype` ch ON ch.id = arc.channel LEFT JOIN `#@__arctype` tp ON arc.typeid=tp.id WHERE arc.arcrank<>-2 {$userCatalogSql} AND arc.mid={$cuserLogin->getUserID()} ORDER BY arc.id DESC LIMIT 0,10";
        $arcArr = array();
        $dsql->Execute('m', $query);
        while($row = $dsql->GetArray('m'))
        {
            $arcArr[] = $row;
        }
        ?>
        <?php
        if (count($arcArr) > 0) {
            foreach($arcArr as $row)
            {
                if (trim($row['editcon']) == '') {
                    $row['editcon'] = 'archives_edit.php';
                }
                $rowarcrank = $row['arcrank']==-1 ? '待审核' : '已审核';
                $pubdate = GetDateMk($row['pubdate']);
                $row['title'] = cn_substr($row['title'], 40);
                echo "<tr><td><a href='{$row['editcon']}?aid={$row['id']}&channelid={$row['channel']}'>{$row['title']}</a></td><td width='70'>{$rowarcrank}</td><td width='110'>{$pubdate}</td></tr>";
            }
        ?>
        </tbody>
    </table>
</div>
<?php }?>
<?php
    exit;
} else if ($dopost == 'get_statistics') {
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
    $safemsg = "系统运行模式为安全模式，模板管理、标签管理、数据库管理、模块管理等功能已暂停，如果您需要这些功能，在/system/common.inc.php文件大约第10行代码找到DEDEX_SAFE_MODE后面值TRUE修改为FALSE恢复使用";
    $unsafemsg = "系统运行模式为开发模式，模板管理、标签管理、数据库管理、模块管理等功能已恢复，建议在上线后更改为<span class='text-success'>安全模式</span>，在/system/common.inc.php文件大约第10行代码找到DEDEX_SAFE_MODE后面值FALSE修改为TRUE暂停使用";
    $modeStr = DEDEX_SAFE_MODE? $safemsg : $unsafemsg;
    ShowMsg($modeStr, "javascript:;");
    exit;
}
?>