<?php
/**
 * @version        $id:article_select_sw.php 8:26 2010年7月12日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require(dirname(__FILE__)."/config.php");
header("Pragma:no-cache");
header("Cache-Control:no-cache");
header("Expires:0");
//来源列表
if ($t == 'source') {
    $m_file = DEDEDATA."/admin/source.txt";
    $allsources = file($m_file);
    echo "<div class='card shadow-sm'><div class='card-header'><a href=\"javascript:OpenMyWin('article_source_edit.php');ClearDivCt('admin-source');\" class='btn btn-primary btn-sm'>设置</a><a href=\"javascript:HideObj('admin-source');ChangeFullDiv('hide');\" class='btn btn-primary btn-sm'>关闭</a></div>\r\n";
    echo "<div class='card-body'>\r\n";
    foreach ($allsources as $v) {
        $v = trim($v);
        if ($v != "") {
            echo "<a href=\"javascript:PutSource('{$v}');\">{$v}</a> ";
        }
    }
    echo "</div></div>";
} else {
    //作者列表
    $m_file = DEDEDATA."/admin/writer.txt";
    echo "<div class='card shadow-sm'><div class='card-header'><a href=\"javascript:OpenMyWin('article_writer_edit.php');ClearDivCt('admin-writer');\" class='btn btn-primary btn-sm'>设置</a><a href=\"javascript:HideObj('admin-writer');ChangeFullDiv('hide');\" class='btn btn-primary btn-sm'>关闭</a></div>\r\n";
    echo "<div class='card-body'>\r\n";
    if (filesize($m_file) > 0) {
        $fp = fopen($m_file, 'r');
        $str = fread($fp, filesize($m_file));
        fclose($fp);
        $strs = explode(',', $str);
        foreach ($strs as $str) {
            $str = trim($str);
            if ($str != "") {
                echo "<a href='javascript:PutWriter(\"{$str}\");'>{$str}</a> ";
            }
        }
    }
    echo "</div></div>";
}
?>