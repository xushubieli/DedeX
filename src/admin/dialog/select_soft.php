<?php
/**
 * 选择软件
 *
 * @version        $id:select_soft.php 9:43 2010年7月8日 tianya $
 * @package        DedeX.Dialog
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
if (empty($activepath)) {
    $activepath = '';
}
$noeditor = isset($noeditor) ? $noeditor : '';
$activepath = str_replace('.', '', $activepath);
$activepath = preg_replace("#\/{1,}#", '/', $activepath);
if (strlen($activepath) < strlen($cfg_soft_dir)) {
    $activepath = $cfg_soft_dir;
}
$inpath = $cfg_basedir.$activepath;
$activeurl = '..'.$activepath;
if (!is_dir($inpath)) {
    die('无效路径');
}
if (empty($f)) {
    $f = 'form1.enclosure';
}
if (empty($comeback)) {
    $comeback = '';
}
$addparm = '';
if (!empty($CKEditor)) {
    $addparm = '&CKEditor='.$CKEditor;
}
if (!empty($CKEditorFuncNum)) {
    $addparm .= '&CKEditorFuncNum='.$CKEditorFuncNum;
}
if (!empty($noeditor)) {
    $addparm .= '&noeditor=yes';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no">
        <title>选择软件</title>
        <link rel="stylesheet" href="/static/web/css/font-awesome.min.css">
        <link rel="stylesheet" href="/static/web/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/web/css/admin.css">
    </head>
    <body class="p-3">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form name="myform" action="select_soft_post.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="activepath" value="<?php echo $activepath;?>">
                    <?php $noeditor = !empty($noeditor) ? "<input type='hidden' name='noeditor' value='yes'>" : ''; echo $noeditor;?>
                    <input type="hidden" name="f" value="<?php echo $f;?>">
                    <input type="hidden" name="job" value="upload">
                    <input type="file" name="uploadfile" class="form-control admin-w-lg">
                    <label>重命名：<input type="text" name="newname" class="form-control admin-w-sm"></label>
                    <button type="submit" class="btn btn-primary btn-sm">保存</button>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header">选择软件</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless icon">
                        <thead>
                            <tr>
                                <td scope="col">文件名称</td>
                                <td scope="col">文件大小</td>
                                <td scope="col">修改时间</td>
                            </tr>
                        </thead>
                        <tbody>
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
                                $line = "<tr>
                                    <td colspan='2'>当前目录：{$activepath}</td>
                                    <td align='right'><a href='select_soft.php?f={$f}&activepath=".urlencode($tmp).$addparm."'><img src='/static/web/img/icon_dir2.png'> 返回上级</a></td>
                                </tr>";
                                echo $line;
                            }
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
                                if (is_dir("$inpath/$file")) {
                                    if (preg_match("#^_(.*)$#i", $file)) continue;
                                    if (preg_match("#^\.(.*)$#i", $file)) continue;
                                    $line = "<tr>
                                        <td colspan='3'><a href=select_soft.php?f={$f}&activepath=".urlencode("$activepath/$file").$addparm."><img src='/static/web/img/icon_dir.png'> {$file}</a></td>
                                    </tr>";
                                    echo $line;
                                } else if (preg_match("#\.(zip|rar|tgr.gz)#i", $file)) {
                                    $reurl = "$activeurl/$file";
                                    $reurl = preg_replace("#^\.\.#", "", $reurl);
                                    if ($file == $comeback) $lstyle = "class='text-danger'";
                                    else $lstyle = '';
                                    $line = "<tr>
                                        <td><a href=\"javascript:ReturnValue('{$reurl}');\" {$lstyle}><img src='/static/web/img/icon_zip.png'> {$file}</a></td>
                                        <td>{$filesize} KB</td>
                                        <td>{$filetime}</td>
                                    </tr>";
                                    echo $line;
                                } else {
                                    $reurl = "$activeurl/$file";
                                    $reurl = preg_replace("#^\.\.#", "", $reurl);
                                    if ($file == $comeback) $lstyle = "class='text-danger'";
                                    else $lstyle = '';
                                    $line = "<tr>
                                        <td><a href=\"javascript:ReturnValue('{$reurl}');\" {$lstyle}><img src='/static/web/img/icon_exe.png'> {$file}</a></td>
                                        <td>{$filesize} KB</td>
                                        <td>{$filetime}</td>
                                    </tr>";
                                    echo $line;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
        function ReturnValue(reimg) {
            if (typeof window.opener.CKEDITOR.instances["<?php echo $f;?>"] !== "undefined") {
                let addonHTML = `<a href='${reimg}' target='_blank'><img src='<?php echo $cfg_cmspath;?>/static/web/img/icon_addon.png'>附件：${reimg}</a>`;
                window.opener.CKEDITOR.instances["<?php echo $f;?>"].insertHtml(addonHTML);
            }
            window.opener.document.<?php echo $f;?>.value = reimg;
            window.close();
        }
        </script>
    </body>
</html>