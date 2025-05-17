<?php
if (!defined('DEDEINC')) exit('dedex');
/**
 * 下载说明标签
 *
 * @version        $id:softmsg.lib.php 9:29 2010年7月6日 tianya $
 * @package        DedeX.Taglib
 * @license        GNU GPL v2 (/license.txt)
 */
function lib_softmsg(&$ctag, &$refObj)
{
    global $dsql;
    $revalue = '';
    $row = $dsql->GetOne(" SELECT * FROM `#@__softconfig`");
    if (is_array($row)) $revalue = $row['downmsg'];
    return $revalue;
}
?>