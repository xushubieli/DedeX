<?php
/**
 * 选择多媒体操作
 *
 * @version        $id:select_media_post.php 9:43 2010年7月8日 tianya $
 * @package        DedeX.Dialog
 * @license        GNU GPL v2 (/license.txt)
 */
include_once(dirname(__FILE__).'/config.php');
$cfg_softtype = $cfg_mediatype."|mp4";
$cfg_soft_dir = $cfg_other_medias;
$bkurl = 'select_media.php';
$uploadmbtype = "多媒体文件类型";
if (empty($activepath)) {
    $activepath = '';
    $activepath = str_replace('.', '', $activepath);
    $activepath = preg_replace("#\/{1,}#", '/', $activepath);
    if (strlen($activepath) < strlen($cfg_other_medias)) {
        $activepath = $cfg_other_medias;
    }
}
require_once(dirname(__FILE__)."/select_soft_post.php");
?>