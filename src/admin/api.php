<?php
/**
 * 后台api接口
 *
 * @version        $id:api.php 8:26 2022年11月20日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
define('AJAXLOGIN', TRUE);
define('IS_DEDEAPI', TRUE);
define('DEDEADMIN', str_replace("\\", '/', dirname(__FILE__)));
require_once(DEDEADMIN.'/../system/common.inc.php');
require_once(DEDEINC.'/userlogin.class.php');
@set_time_limit(0);
AjaxHead();
helper('cache');
$action = isset($action) && in_array($action, array('is_need_check_code', 'upload_image')) ? $action  : '';
$curDir = dirname(GetCurUrl());//当前目录
/**
 * 登录鉴权
 *
 * @return void
 */
function checkLogin()
{
    $cuserLogin = new userLogin();
    if ($cuserLogin->getUserID() <= 0 || $cuserLogin->getUserType() != 10) {
        echo json_encode(array(
            "code" => -1,
            "msg" => "此操作需要登录超级管理员权限",
            "data" => null,
        ));
        exit;
    }
}
if ($action === 'is_need_check_code') {
    $cuserLogin = new userLogin();
    $isNeed = $cuserLogin->isNeedCheckCode($userid);
    echo json_encode(array(
        "code" => 0,
        "msg" => "",
        "data" => array(
            "isNeed" => $isNeed,
        ),
    ));
    exit;
} else if ($action === 'upload_image') {
    $cuserLogin = new userLogin();
    if ($cuserLogin->getUserID() <= 0) {
        echo json_encode(array(
            "code" => -1,
            "msg" => "登录系统后才能上传图片",
            "data" => null,
        ));
        exit;
    }
    $imgfile_name = $_FILES["file"]['name'];
    $activepath = $cfg_image_dir;
    $allowedTypes = array("image/pjpeg", "image/jpeg", "image/gif", "image/png", "image/xpng", "image/wbmp", "image/webp");
    $uploadedFile = $_FILES['file']['tmp_name'];
    if (!function_exists('mime_content_type')) {
        echo json_encode(array(
            "code" => -1,
            "uploaded" => 0,
            "error" => array(
                "message" => "系统不支持fileinfo组件，建议php.ini中开启",
            ),
        ));
        exit;
    }
    if (empty($uploadedFile)) {
        echo json_encode(array(
            "code" => -1,
            "msg" => "文件为空",
            "data" => null,
        ));
        exit;
    }
    $fileType = mime_content_type($uploadedFile);
    $imgSize = getimagesize($uploadedFile);
    if (!in_array($fileType, $allowedTypes) || !$imgSize) {
        echo json_encode(array(
            "code" => -1,
            "uploaded" => 0,
            "error" => array(
                "message" => "仅支持图片格式文件",
            ),
        ));
        exit;
    }
    $nowtme = time();
    $mdir = MyDate($cfg_addon_savetype, $nowtme);
    if (!is_dir($cfg_basedir.$activepath."/$mdir")) {
        MkdirAll($cfg_basedir.$activepath."/$mdir", $cfg_dir_purview);
    }
    $cuserLogin = new userLogin();
    $iseditor = isset($iseditor)? intval($iseditor) : 0;
    $filename_name = $cuserLogin->getUserID().'-'.dd2char(MyDate("ymdHis", $nowtme).mt_rand(100, 999));
    $filename = $mdir.'/'.$filename_name;
    $fs = explode('.', $imgfile_name);
    $filename = $filename.'.'.$fs[count($fs) - 1];
    $filename_name = $filename_name.'.'.$fs[count($fs) - 1];
    $fullfilename = $cfg_basedir.$activepath."/".$filename;
    if (preg_match('#\.(php|pl|cgi|asp|aspx|jsp|php5|php4|php3|shtm|shtml|htm)$#i', trim($fullfilename))) {
        echo json_encode(array(
            "code" => -1,
            "uploaded" => 0,
            "error" => array(
                "message" => "文件扩展名已被系统禁止",
            ),
        ));
        exit;
    }
    move_uploaded_file($_FILES["file"]["tmp_name"], $fullfilename) or die(json_encode(array(
        "code" => -1,
        "uploaded" => 0,
        "error" => array(
            "message" => "上传失败",
        ),
    )));
    $info = '';
    $sizes[0] = 0;
    $sizes[1] = 0;
    $sizes = getimagesize($fullfilename, $info);
    $imgwidthValue = $sizes[0];
    $imgheightValue = $sizes[1];
    $imgsize = filesize($fullfilename);
    $inquery = "INSERT INTO `#@__uploads` (arcid,title,url,mediatype,width,height,playtime,filesize,uptime,mid) VALUES ('0','$filename','{$activepath}/{$filename}','1','$imgwidthValue','$imgheightValue','0','{$imgsize}','{$nowtme}','{$cuserLogin->getUserID()}'); ";
    $dsql->ExecuteNoneQuery($inquery);
    $fid = $dsql->GetLastID();
    AddMyAddon($fid, $activepath.'/'.$filename);
    echo json_encode(array(
        "code" => 0,
        "msg" => "上传成功",
        "data" => $activepath."/".$filename,
    ));
}
?>