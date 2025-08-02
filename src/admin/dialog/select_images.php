<?php
/**
 * 选择图片
 *
 * @version        $id:select_images.php 2022-07-01 tianya $
 * @package        DedeX.Dialog
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
include(DEDEDATA.'/mark/inc_photowatermark_config.php');
if (empty($activepath)) {
    $activepath = '';
}
if (empty($imgstick)) {
    $imgstick = '';
}
$noeditor = isset($noeditor) ? $noeditor : '';
$iseditor = isset($iseditor) ? intval($iseditor) : '';
$activepath = str_replace('.', '', $activepath);
$activepath = preg_replace("#\/{1,}#", '/', $activepath);
if (strlen($activepath) < strlen($cfg_image_dir)) {
    $activepath = $cfg_image_dir;
}
$inpath = $cfg_basedir.$activepath;
$activeurl = '..'.$activepath;
if (empty($f)) {
    $f = 'form1.picname';
}
$f = RemoveXSS($f);
if (empty($v)) {
    $v = 'picview';
}
if (empty($comeback)) {
    $comeback = '';
}
$addparm = '';
if (!empty($CKEditor)) {
    $addparm = '&CKEditor='.$CKEditor;
    $f = $CKEditor;
}
if (!empty($CKEditorFuncNum)) {
    $addparm .= '&CKEditorFuncNum='.$CKEditorFuncNum;
}
if (!empty($noeditor)) {
    $addparm .= '&noeditor=yes';
}
if (!empty($iseditor)) {
    $addparm .= '&iseditor='.$iseditor;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
        <title>选择图片</title>
        <link rel="stylesheet" href="/static/web/css/font-awesome.min.css">
        <link rel="stylesheet" href="/static/web/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/web/css/admin.css">
        <script src="/static/web/js/jquery.min.js"></script>
    </head>
    <body class="p-3">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form name="myform" action="select_images_post.php" method="POST" enctype="multipart/form-data">
                    <?php $noeditor = !empty($noeditor) ? "<input type='hidden' name='noeditor' value='yes'>" : ''; echo $noeditor;?>
                    <input type="hidden" name="activepath" value="<?php echo $activepath;?>">
                    <input type="hidden" name="f" value="<?php echo $f;?>">
                    <input type="hidden" name="v" value="<?php echo $v;?>">
                    <input type="hidden" name="iseditor" value="<?php echo $iseditor;?>">
                    <input type="hidden" name="imgstick" value="<?php echo $imgstick;?>">
                    <input type="hidden" name="CKEditorFuncNum" value="<?php echo isset($CKEditorFuncNum) ? $CKEditorFuncNum : 1;?>">
                    <input type="hidden" name="job" value="upload">
                    <input type="file" name="imgfile" class="form-control admin-w-lg">
                    <label><input type="checkbox" name="needwatermark" value="1" <?php if ($photo_markup == '1') echo 'checked';?>> 水印</label>
                    <label><input type="checkbox" name="resize" value="1"> 缩小</label>
                    <label><input type="text" name="iwidth" value="<?php echo $cfg_ddimg_width;?>" class="form-control admin-w-xs"> 宽</label>
                    <label><input type="text" name="iheight" value="<?php echo $cfg_ddimg_height;?>" class="form-control admin-w-xs"> 高</label>
                    <button type="submit" class="btn btn-primary btn-sm">上传</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header">选择图片</div>
            <div class="card-body">
                <?php
                $dh = scandir($inpath);
                $filtered_dh = array_diff($dh, ['.', '..']);
                $fileTimes = array_map(function($file) use ($inpath) {
                    return file_exists("$inpath/$file") ? filemtime("$inpath/$file") : 0;
                }, $filtered_dh);
                array_multisort($fileTimes, SORT_DESC, $filtered_dh);//按照时间戳降序排序
                //处理返回上级目录的链接
                if ($activepath != "") {
                    $tmp = preg_replace("#[\/][^\/]*$#i", "", $activepath);
                    $line = "<div class='d-flex justify-content-between align-items-center mb-3'>
                        <span>当前目录：{$activepath}</span>
                        <a href='select_images.php?imgstick={$imgstick}&v={$v}&f={$f}&activepath=".urlencode($tmp).$addparm."' class='btn btn-primary btn-sm'>返回上级</a>
                    </div>";
                    echo $line;
                }
                echo "<div class='opt-img'>";
                foreach ($filtered_dh as $file) {
                    //计算文件大小和创建时间
                    if (!is_dir("$inpath/$file")) {
                        $filesize = filesize("$inpath/$file") / 1024;
                        if ($filesize < 0.1) {
                            $filesize = number_format($filesize, 2);
                        } else {
                            $filesize = number_format($filesize, 1);
                        }
                        $filetime = MyDate("Y-m-d H:i:s", filemtime("$inpath/$file"));
                    }
                    //判断文件类型并作处理
                    if (is_dir("$inpath/$file")) {
                        if (preg_match("#^_(.*)$#i", $file)) continue;
                        if (preg_match("#^\.(.*)$#i", $file)) continue;
                        $line = "<div class='list dir'>
                            <a href='select_images.php?imgstick={$imgstick}&v={$v}&f={$f}&activepath=".urlencode("$activepath/$file").$addparm."'>
                                <img src='/static/web/img/icon_dir.png'>
                                <span>{$file}</span>
                            </a>
                        </div>";
                        echo $line;
                    } else if (preg_match("#\.(".$cfg_imgtype.")#i", $file)) {
                        $reurl = "$activeurl/$file";
                        $reurl = preg_replace("#^\.\.#", "", $reurl);
                        if ($file == $comeback) $lstyle = "class='text-danger'";
                        else $lstyle = '';
                        $line = "<div class='list'>
                            <a href='{$reurl}' onclick=\"ReturnImg('{$reurl}');\">
                                <img src='{$reurl}' title='{$file}'>
                                <span {$lstyle}>{$file}</span>
                            </a>
                        </div>";
                        echo $line;
                    } else if (preg_match("#\.(jpg)#i", $file)) {
                        $reurl = "$activeurl/$file";
                        $reurl = preg_replace("#^\.\.#", "", $reurl);
                        if ($file == $comeback) $lstyle = "class='text-danger'";
                        else $lstyle = '';
                        $line = "<div class='list'>
                            <a href='{$reurl}' onclick=\"ReturnImg('{$reurl}');\">
                                <img src='{$reurl}' title='{$file}'>
                                <span {$lstyle}>{$file}</span>
                            </a>
                        </div>";
                        echo $line;
                    }
                }
                echo "</div>";
                ?>
            </div>
        </div>
        <script>
        function getUrlParam(paramName) {
            var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i');
            var match = window.location.search.match(reParam);
            return (match && match.length > 1) ? match[1] : '';
        }
        function ReturnImg(reimg) {
            var funcNum = getUrlParam('CKEditorFuncNum');
            var iseditor = parseInt(getUrlParam('iseditor'));
            if (funcNum > 1) {
                var fileUrl = reimg;
                window.opener.CKEDITOR.tools.callFunction(funcNum, fileUrl);
            } else if (iseditor == 1) {
                let addonHTML = `<img src='${reimg}'>`;
                window.opener.CKEDITOR.instances["<?php echo $f;?>"].insertHtml(addonHTML);
            } else if (window.opener.document.<?php echo $f;?> != null) {
                window.opener.document.<?php echo $f;?>.value = reimg;
                if (window.opener.document.getElementById('div<?php echo $v;?>')) {
                    window.opener.document.getElementById('<?php echo $v;?>').src = reimg;
                } else if (window.opener.document.getElementById('litPic')) {
                    window.opener.document.getElementById('litPic').src = reimg;
                } else if (document.all) window.opener = true;
            } else if (typeof window.opener.CKEDITOR.instances["<?php echo $f;?>"] !== "undefined") {
                let addonHTML = `<img src='${reimg}'>`;
                window.opener.CKEDITOR.instances["<?php echo $f;?>"].insertHtml(addonHTML);
            }
            window.close();
        }
        </script>
    </body>
</html>