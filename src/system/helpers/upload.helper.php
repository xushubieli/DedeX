<?php
if (!defined('DEDEINC')) exit('dedebiz');
/**
 * 上传处理助手
 *
 * @version        $id:upload.helper.php 2010-07-05 11:43:09 tianya $
 * @package        DedeBIZ.Helpers
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
/**
 *  管理员上传文件的通用函数
 *
 * @access    public
 * @param     string  $uploadname  上传名称
 * @param     string  $ftype  文件类型
 * @param     string  $rnddd  后缀数字
 * @param     bool    $watermark  是否水印
 * @param     string  $filetype image、media、addon $file_type='' 对于swfupload上传的文件，因为没有filetype，所以需指定，并且有些特殊之处不同
 * @return    int     -1没选定上传文件，0文件类型不允许，-2保存失败
 */
if (!function_exists('AdminUpload')) {
    function AdminUpload($uploadname, $ftype = 'image', $rnddd = 0, $watermark = TRUE, $filetype = '')
    {
        global $dsql, $cuserLogin, $cfg_addon_savetype, $cfg_dir_purview, $cfg_basedir, $cfg_image_dir, $cfg_soft_dir, $cfg_other_medias, $cfg_imgtype, $cfg_softtype, $cfg_mediatype;
        if ($watermark) include_once(DEDEINC.'/image.func.php');
        $file_tmp = isset($GLOBALS[$uploadname]) ? $GLOBALS[$uploadname] : '';
        if ($file_tmp == '' || !is_uploaded_file($file_tmp)) {
            return -1;
        }
        $file_tmp = $GLOBALS[$uploadname];
        $file_size = filesize($file_tmp);
        $file_type = $filetype == '' ? strtolower(trim($GLOBALS[$uploadname.'_type'])) : $filetype;
        $file_name = isset($GLOBALS[$uploadname.'_name']) ? $GLOBALS[$uploadname.'_name'] : '';
        $file_snames = explode('.', $file_name);
        $file_sname = strtolower(trim($file_snames[count($file_snames) - 1]));
        if ($ftype == 'image' || $ftype == 'imagelit') {
            $filetype = '1';
            $sparr = array('image/pjpeg', 'image/jpeg', 'image/gif', 'image/png', 'image/xpng', 'image/wbmp');
            if (!in_array($file_type, $sparr)) return 0;
            if ($file_sname == '') {
                if ($file_type == 'image/gif') $file_sname = 'jpg';
                else if ($file_type == 'image/png' || $file_type == 'image/xpng') $file_sname = 'png';
                else if ($file_type == 'image/wbmp') $file_sname = 'bmp';
                else $file_sname = 'jpg';
            }
            $filedir = $cfg_image_dir.'/'.MyDate($cfg_addon_savetype, time());
        } else if ($ftype == 'media') {
            $filetype = '3';
            if (!preg_match('/'.$cfg_mediatype.'/', $file_sname)) return 0;
            $filedir = $cfg_other_medias.'/'.MyDate($cfg_addon_savetype, time());
        } else {
            $filetype = '4';
            $cfg_softtype .= '|'.$cfg_mediatype.'|'.$cfg_imgtype;
            $cfg_softtype = str_replace('||', '|', $cfg_softtype);
            if (!preg_match('/'.$cfg_softtype.'/', $file_sname)) return 0;
            $filedir = $cfg_soft_dir.'/'.MyDate($cfg_addon_savetype, time());
        }
        if (!is_dir(DEDEROOT.$filedir)) {
            MkdirAll($cfg_basedir.$filedir, $cfg_dir_purview);
        }
        $filename = $cuserLogin->getUserID().'-'.dd2char(MyDate('ymdHis', time())).$rnddd;
        if ($ftype == 'imagelit') $filename .= '-L';
        if (file_exists($cfg_basedir.$filedir.'/'.$filename.'.'.$file_sname)) {
            for ($i = 50; $i <= 5000; $i++) {
                if (!file_exists($cfg_basedir.$filedir.'/'.$filename.'-'.$i.'.'.$file_sname)) {
                    $filename = $filename.'-'.$i;
                    break;
                }
            }
        }
        $fileurl = $filedir.'/'.$filename.'.'.$file_sname;
        $mime = get_mime_type($file_tmp);
        if (!preg_match("#^image#i", $mime)) {
            return -1;
        }
        $rs = move_uploaded_file($file_tmp, $cfg_basedir.$fileurl);
        if (!$rs) return -2;
        if ($ftype == 'image' && $watermark) {
            WaterImg($cfg_basedir.$fileurl, 'up');
        }
        //保存信息到数据库
        $title = $filename.'.'.$file_sname;
        $inquery = "INSERT INTO `#@__uploads`(title,url,mediatype,width,height,playtime,filesize,uptime,mid) VALUES ('$title','$fileurl','$filetype','0','0','0','".filesize($cfg_basedir.$fileurl)."','".time()."','".$cuserLogin->getUserID()."'); ";
        $dsql->ExecuteNoneQuery($inquery);
        $fid = $dsql->GetLastID();
        AddMyAddon($fid, $fileurl);
        return $fileurl;
    }
}
//前台会员通用上传函数：$upname是文件上传框的表单名，而不是表单的变量；$handname允许会员手工指定网址情况下的网址
if (!function_exists('MemberUploads')) {
    function MemberUploads($upname, $handname, $userid = 0, $utype = 'image', $exname = '', $maxwidth = 0, $maxheight = 0, $water = false, $isadmin = false)
    {
        global $cfg_imgtype, $cfg_mb_addontype, $cfg_mediatype, $cfg_user_dir, $cfg_basedir, $cfg_dir_purview;
        //当为游客投稿的情况下，这个id为0
        if (empty($userid)) $userid = 0;
        if (!is_dir($cfg_basedir.$cfg_user_dir."/$userid")) {
            MkdirAll($cfg_basedir.$cfg_user_dir."/$userid", $cfg_dir_purview);
        }
        //有上传文件
        $allAllowType = str_replace('||', '|', $cfg_imgtype.'|'.$cfg_mediatype.'|'.$cfg_mb_addontype);
        if (!empty($GLOBALS[$upname]) && is_uploaded_file($GLOBALS[$upname])) {
            $nowtme = time();

            $GLOBALS[$upname.'_name'] = trim(preg_replace("#[ \r\n\t\*\%\\\/\?><\|\":]{1,}#", '', $GLOBALS[$upname.'_name']));
            //源文件类型检查
            if ($utype == 'image') {
                if (!preg_match("/\.(".$cfg_imgtype.")$/", $GLOBALS[$upname.'_name'])) {
                    ShowMsg("您上传的图片类型错误，请上传{$cfg_imgtype}类型", "-1");
                    exit();
                }
                $sparr = array("image/pjpeg", "image/jpeg", "image/gif", "image/png", "image/xpng", "image/wbmp");
                $imgfile_type = strtolower(trim($GLOBALS[$upname.'_type']));
                if (!in_array($imgfile_type, $sparr)) {
                    ShowMsg("您上传的图片格式错误，请使用jpg、png、gif、wbmp格式其中一种", "-1");
                    exit();
                }
            } else if ($utype == 'flash' && !preg_match("/\.swf$/", $GLOBALS[$upname.'_name'])) {
                ShowMsg("上传的文件必须为flash文件", "-1");
                exit();
            } else if ($utype == 'media' && !preg_match("/\.(".$cfg_mediatype.")$/", $GLOBALS[$upname.'_name'])) {
                ShowMsg("您上传的文件类型必须为：$cfg_mediatype", "-1");
                exit();
            } else if (!preg_match("/\.(".$allAllowType.")$/", $GLOBALS[$upname.'_name'])) {
                ShowMsg("您上传的文件类型不被允许", '-1');
                exit();
            }
            //再次严格检测文件扩展名是否符合系统定义的类型
            $fs = explode('.', $GLOBALS[$upname.'_name']);
            $sname = $fs[count($fs) - 1];
            $alltypes = explode('|', $allAllowType);
            if (!in_array(strtolower($sname), $alltypes)) {
                ShowMsg('您上传的文件类型不被允许', '-1');
                exit();
            }
            //强制禁止的文件类型
            if (preg_match("/(asp|php|pl|cgi|shtm|js|htm)$/", $sname)) {
                ShowMsg("您上传的文件已被系统禁止", '-1');
                exit();
            }
            if ($exname == '') {
                $filename = $cfg_user_dir."/$userid/".dd2char($nowtme.'-'.mt_rand(1000, 9999)).'.'.$sname;
            } else {
                $filename = $cfg_user_dir."/{$userid}/{$exname}.".$sname;
            }
            $mime = get_mime_type($GLOBALS[$upname]);
            if (preg_match("#^unknow#", $mime)) {
                ShowMsg("系统不支持fileinfo组件，建议php.ini中开启", -1);
                exit;
            }
            if (!preg_match("#^(image|video|audio|application)#i", $mime)) {
                ShowMsg("仅支持媒体文件及应用程序上传", -1);
                exit;
            }
            move_uploaded_file($GLOBALS[$upname], $cfg_basedir.$filename) or die("上传文件到{$filename}失败");
            @unlink($GLOBALS[$upname]);
            if (@filesize($cfg_basedir.$filename) > $GLOBALS['cfg_mb_upload_size'] * 1024) {
                @unlink($cfg_basedir.$filename);
                ShowMsg("您上传的文件超出系统大小限制", "-1");
                exit();
            }
            //加水印或缩小图片
            if ($utype == 'image') {
                include_once(DEDEINC.'/image.func.php');
                if ($maxwidth > 0 || $maxheight > 0) {
                    ImageResize($cfg_basedir.$filename, $maxwidth, $maxheight);
                } else if ($water) {
                    WaterImg($cfg_basedir.$filename);
                }
            }
            return $filename;
        }
        //没有上传文件
        else {
            //强制禁止的文件类型
            if ($handname == '') {
                return $handname;
            } else if (preg_match("/\.(asp|php|pl|cgi|shtm|js|htm)$/", $handname)) {
                exit('Not allow filename for not safe!');
            } else if (!preg_match("/\.(".$allAllowType.")$/", $handname)) {
                exit('Not allow filename for filetype!');
            } else if (!preg_match('#^(http|https):#', $handname) && !preg_match('#^'.$cfg_user_dir.'/'.$userid."#", $handname) && !$isadmin) {
                exit('Not allow filename for not userdir!');
            }
            return $handname;
        }
    }
}
?>