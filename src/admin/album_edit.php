<?php
/**
 * 修改图片模型
 *
 * @version        $id:album_edit.php 8:26 2010年7月12日 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('a_Edit,a_AccEdit,a_MyEdit');
require_once(DEDEINC."/customfields.func.php");
require_once(DEDEADMIN."/inc/inc_archives_functions.php");
if (empty($dopost)) $dopost = '';
if ($dopost != 'save') {
    require_once(DEDEADMIN."/inc/inc_catalog_options.php");
    require_once(DEDEINC."/dedetag.class.php");
    ClearMyAddon();
    $aid = intval($aid);
    //读取归档信息
    $arcQuery = "SELECT ch.typename as channelname,ar.membername as rankname,arc.* FROM `#@__archives` arc LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel LEFT JOIN `#@__arcrank` ar ON ar.`rank`=arc.arcrank WHERE arc.id='$aid' ";
    $arcRow = $dsql->GetOne($arcQuery);
    if (!is_array($arcRow)) {
        ShowMsg("读取文档信息出错", "-1");
        exit();
    }
    $query = "SELECT * FROM `#@__channeltype` WHERE id='".$arcRow['channel']."'";
    $cInfos = $dsql->GetOne($query);
    if (!is_array($cInfos)) {
        ShowMsg("读取栏目信息出错", "javascript:;");
        exit();
    }
    $addtable = $cInfos['addtable'];
    $addRow = $dsql->GetOne("SELECT * FROM `$addtable` WHERE aid='$aid'");
    $channelid = $arcRow['channel'];
    $imgurls = $addRow["imgurls"];
    $maxwidth = $addRow["maxwidth"];
    $pagestyle = $addRow["pagestyle"];
    $irow = $addRow["row"];
    $icol = $addRow["col"];
    $isrm = $addRow["isrm"];
    $body = $addRow["body"];
    $ddmaxwidth = $addRow["ddmaxwidth"];
    $pagepicnum = $addRow["pagepicnum"];
    $tags = GetTags($aid);
    $arcRow = XSSClean($arcRow);
    $addRow = XSSClean($addRow);
    include DedeInclude("templets/album_edit.htm");
    exit();
} else if ($dopost == 'save') {
    require_once(DEDEINC.'/image.func.php');
    require_once(DEDEINC.'/libraries/oxwindow.class.php');
    $flag = isset($flags) ? join(',', $flags) : '';
    $notpost = isset($notpost) && $notpost == 1 ? 1 : 0;
    if (empty($typeid2)) $typeid2 = 0;
    if (!isset($autokey)) $autokey = 0;
    if (!isset($remote)) $remote = 0;
    if (!isset($dellink)) $dellink = 0;
    if (!isset($autolitpic)) $autolitpic = 0;
    if (!isset($albums)) $albums = '';
    if (!isset($formzip)) $formzip = 0;
    if (!isset($ddisfirst)) $ddisfirst = 0;
    if (!isset($delzip)) $delzip = 0;
    if (trim($title) == '') {
        ShowMsg("文档标题不能为空", "-1");
        exit();
    }
    if (empty($typeid)) {
        ShowMsg("请选择文档栏目", "-1");
        exit();
    }
    if (empty($channelid)) {
        ShowMsg("文档为非指定类型，请检查您发布文档是否正确", "-1");
        exit();
    }
    if (!CheckChannel($typeid, $channelid)) {
        ShowMsg("您所选择的栏目与当前模型不相符，请重新选择", "-1");
        exit();
    }
    if (!TestPurview('a_Edit')) {
        CheckCatalog($typeid, "您没有操作栏目{$typeid}文档权限");
    }
    //对保存的文档进行处理
    $pubdate = GetMkTime($pubdate);
    $senddate = GetMkTime($senddate);
    $sortrank = AddDay($pubdate, $sortup);
    $ismake = $ishtml == 0 ? -1 : 0;
    $title = cn_substrR($title, $cfg_title_maxlen);
    $shorttitle = cn_substrR($shorttitle, 255);
    $color =  cn_substrR($color, 7);
    $writer =  cn_substrR($writer, 255);
    $source = cn_substrR($source, 255);
    $description = cn_substrR($description, 255);
    $keywords = trim(cn_substrR($keywords, 255));
    $filename = trim(cn_substrR($filename, 50));
    $isremote  = 0;
    $serviterm = empty($serviterm) ? "" : $serviterm;
    if (!TestPurview('a_Check,a_AccCheck,a_MyCheck')) {
        $arcrank = -1;
    }
    $adminid = $cuserLogin->getUserID();
    //处理上传的缩略图
    if (empty($ddisremote)) {
        $ddisremote = 0;
    }
    $litpic = GetDDImage('none', $picname, $ddisremote);
    //分析body里的文档
    $body = AnalyseHtmlBody($body, $description, $litpic, $keywords, 'htmltext');
    //处理图片文档的自定义属性
    if ($litpic != '' && !preg_match("#p#", $flag)) {
        $flag = ($flag == '' ? 'p' : $flag.',p');
    }
    if ($redirecturl != '' && !preg_match("#j#", $flag)) {
        $flag = ($flag == '' ? 'j' : $flag.',j');
    }
    //跳转网址的文档强制为动态
    if (preg_match("#j#", $flag)) $ismake = -1;

    $imgurls = "{dede:pagestyle maxwidth='$maxwidth' pagepicnum='$pagepicnum' ddmaxwidth='$ddmaxwidth' row='$row' col='$col' value='$pagestyle'/}\r\n";
    $hasone = false;
    //检查旧的图片是否有更新，并保存
    for ($i = 1; $i <= 120; $i++) {
        if (!isset(${'imgurl'.$i})) continue;
        $info = '';
        $iinfo = str_replace("'", "`", stripslashes(${'imgmsg'.$i}));
        $iurl = stripslashes(${'imgurl'.$i});
        $ddurl = stripslashes(${'imgddurl'.$i});
        if (preg_match("#swfupload#i", $ddurl)) $ddurl = '';
        $imgfile = $cfg_basedir.$iurl;
        $litimgfile = $cfg_basedir.$ddurl;
        //有上传文件的情况
        if (isset(${'imgfile'.$i}) && is_uploaded_file(${'imgfile'.$i})) {
            $tmpFile = ${'imgfile'.$i};
            //检测上传的图片，如果类型不对，保留原来图片
            $imginfos = @GetImageSize($tmpFile, $info);
            if (!is_array($imginfos)) {
                $imginfos = @GetImageSize($imgfile, $info);
                $imgurls .= "{dede:img ddimg='$ddurl' text='$iinfo' width='".$imginfos[0]."' height='".$imginfos[1]."'} $iurl {/dede:img}\r\n";
                continue;
            }
            $mime = get_mime_type($tmpFile);
            if (preg_match("#^unknow#", $mime)) {
                ShowMsg("系统不支持fileinfo组件，建议php.ini中开启", -1);
                exit;
            }
            if (!preg_match("#^image#i", $mime)) {
                ShowMsg("非图片格式文件，无法正常上传", -1);
                exit;
            }
            move_uploaded_file($tmpFile, $imgfile);
            $imginfos = @GetImageSize($imgfile, $info);
            if ($ddurl == $iurl) {
                $litpicname = $pagestyle > 2 ? GetImageMapDD($iurl, $cfg_ddimg_width) : $iurl;
                $litimgfile = $cfg_basedir.$litpicname;
            } else {
                ImageResizeNew($imgfile, $cfg_ddimg_width, $cfg_ddimg_height, $litimgfile);
                $litpicname = $ddurl;
            }
            $imgurls .= "{dede:img ddimg='$litpicname' text='$iinfo' width='".$imginfos[0]."' height='".$imginfos[1]."'} $iurl {/dede:img}\r\n";
            if ($autolitpic == 1 && empty($litpic)) {
                $litpic = $litpicname;
            }
        }
        //没上传图片，只修改msg信息
        else {
            $iinfo = str_replace("'", "`", stripslashes(${'imgmsg'.$i}));
            $iurl = stripslashes(${'imgurl'.$i});
            $ddurl = stripslashes(${'imgddurl'.$i});
            if (preg_match("#swfupload#i", $ddurl)) {
                $ddurl = $pagestyle > 2 ? GetImageMapDD($iurl, $cfg_ddimg_width) : $iurl;
            }
            $imginfos = @GetImageSize($imgfile, $info);
            $imgurls .= "{dede:img ddimg='$ddurl' text='$iinfo' width='".$imginfos[0]."' height='".$imginfos[1]."'} $iurl {/dede:img}\r\n";
            if ($autolitpic == 1 && empty($litpic)) {
                $litpic = $ddurl;
            }
        }
    }
    //更新数据库的SQL语句
    $query = "
    UPDATE `#@__archives` SET typeid='$typeid',typeid2='$typeid2',sortrank='$sortrank',flag='$flag',click='$click',ismake='$ismake',arcrank='$arcrank',money='$money',title='$title',color='$color',source='$source',writer='$writer',litpic='$litpic',pubdate='$pubdate',senddate='$senddate',notpost='$notpost',description='$description',keywords='$keywords',shorttitle='$shorttitle',filename='$filename',dutyadmin='$adminid' WHERE id='$id'; ";
    if (!$dsql->ExecuteNoneQuery($query)) {
        ShowMsg("数据保存到数据库文档主表出错，请检查数据库字段".$dsql->GetError(), "javascript:;");
        exit();
    }
    if ($albums !== "") {
        $albumsArr  = json_decode(stripslashes($albums), true);
        for ($i = 0; $i <= count($albumsArr) - 1; $i++) {
            $album = $albumsArr[$i];
            if (strpos($data[0], "data:image") > 0) {
                $data = explode(',', $album['img']);
                $ext = ".png";
                if (strpos($data[0], "data:image/jpeg") === 0){
                    $ext = ".jpg";
                } elseif (strpos($data[0], "data:image/gif") === 0) {
                    $ext = ".gif";
                } elseif (strpos($data[0], "data:image/webp") === 0) {
                    $ext = ".webp";
                } elseif (strpos($data[0], "data:image/bmp") === 0) {
                    $ext = ".bmp";
                }
                $ntime = time();
                $savepath = $cfg_image_dir.'/'.MyDate($cfg_addon_savetype, $ntime);
                CreateDir($savepath);
                $fullUrl = $savepath.'/'.dd2char(MyDate('mdHis', $ntime).$cuserLogin->getUserID().mt_rand(1000, 9999));
                $fullUrl = $fullUrl.$ext;
                file_put_contents($cfg_basedir.$fullUrl, base64_decode($data[1]));
                $info = '';
                $imginfos = GetImageSize($cfg_basedir.$fullUrl, $info);
                $v = $fullUrl;
            } else {
                $v = $album['img'];
                $info = '';
                $imginfos = GetImageSize($cfg_basedir.$v, $info);
            }
            $imginfo =  !empty($album['txt']) ? $album['txt'] : '';
            $imgurls .= "{dede:img ddimg='$v' text='$imginfo' width='".$imginfos[0]."' height='".$imginfos[1]."'} $v {/dede:img}\r\n";
        }
    }
    $imgurls = addslashes($imgurls);
    //分析处理附加表数据
    $inadd_f = '';
    $inadd_v = '';
    if (!empty($dede_addonfields)) {
        $addonfields = explode(';', $dede_addonfields);
        $inadd_f = '';
        $inadd_v = '';
        if (is_array($addonfields)) {
            foreach ($addonfields as $v) {
                if ($v == '') {
                    continue;
                }
                $vs = explode(',', $v);
                if ($vs[1] == 'htmltext' || $vs[1] == 'textdata') //网页文本特殊处理
                {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]}, $description, $litpic, $keywords, $vs[1]);
                } else {
                    if (!isset(${$vs[0]})) {
                        ${$vs[0]} = '';
                    }
                    ${$vs[0]} = GetFieldValueA(${$vs[0]}, $vs[1], $id);
                }
                $inadd_f .= ",`{$vs[0]}` = '".${$vs[0]}."'";
            }
        }
    }
    //更新附加表
    $cts = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
    $addtable = trim($cts['addtable']);
    if ($addtable != '') {
        $useip = GetIP();
        $query = "UPDATE `$addtable` SET typeid='$typeid',pagestyle='$pagestyle',body='$body',maxwidth='$maxwidth',ddmaxwidth='$ddmaxwidth',pagepicnum='$pagepicnum',imgurls='$imgurls',`row`='$row',col='$col',isrm='$isrm'{$inadd_f},redirecturl='$redirecturl',userip='$useip' WHERE aid='$id'; ";
        if (!$dsql->ExecuteNoneQuery($query)) {
            ShowMsg("数据保存到数据库附加表出错，请检查数据库字段".$dsql->GetError(), "javascript:;");
            exit();
        }
    }
    //生成网页
    UpIndexKey($id, $arcrank, $typeid, $sortrank, $tags);
    $arcUrl = MakeArt($id, true, true, $isremote);
    if ($arcUrl == '') {
        $arcUrl = $cfg_phpurl."/view.php?aid=$id";
    }
    ClearMyAddon($id, $title);
    //自动更新关联文档
    if (is_array($automake)) {
        foreach ($automake as $key => $value) {
            if (isset(${$key}) && !empty(${$key})) {
                $ids = explode(",", ${$key});
                foreach ($ids as $id) {
                    MakeArt($id, true, true, $isremote);
                }
            }
        }
    }
    //返回成功信息
    $msg = "<tr>
        <td align='center'><a href='$arcUrl' target='_blank' class='btn btn-success btn-sm'>浏览文档</a><a href='album_add.php?cid=$typeid' class='btn btn-success btn-sm'>发布文档</a><a href='archives_do.php?aid=".$id."&dopost=editArchives' class='btn btn-success btn-sm'>修改文档</a><a href='catalog_do.php?cid=$typeid&dopost=listArchives' class='btn btn-success btn-sm'>返回文档列表</a></td>
    </tr>";
    $wintitle = "成功修改图片文档";
    $win = new OxWindow();
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand", false);
    $win->Display();
}
?>