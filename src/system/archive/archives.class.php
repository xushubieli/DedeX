<?php
if (!defined('DEDEINC')) exit('dedebiz');
/**
 * 文档
 *
 * @version        $id:archives.class.php 4 15:13 2010年7月7日 tianya $
 * @package        DedeBIZ.Libraries
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(DEDEINC."/typelink/typelink.class.php");
require_once(DEDEINC."/channelunit.class.php");
@set_time_limit(0);
class Archives
{
    var $TypeLink;
    var $ChannelUnit;
    var $dsql;
    var $Fields;
    var $dtp;
    var $ArcID;
    var $SplitPageField;
    var $SplitFields;
    var $NowPage;
    var $TotalPage;
    var $NameFirst;
    var $ShortName;
    var $FixedValues;
    var $TempSource;
    var $IsError;
    var $SplitTitles;
    var $PreNext;
    var $addTableRow;
    var $remoteDir;
    /**
     *  php5构造函数
     *
     * @access    public
     * @param     int  $aid  文档id
     * @return    string
     */
    function __construct($aid)
    {
        global $dsql, $envs;
        $this->IsError = FALSE;
        $this->ArcID = $aid;
        $this->PreNext = array();
        $this->dsql = $dsql;
        $query = "SELECT channel,typeid FROM `#@__arctiny` WHERE id='$aid' ";
        $arr = $this->dsql->GetOne($query);
        $envs['url_type'] = 2;
        if (!is_array($arr)) {
            $this->IsError = TRUE;
        } else {
            if ($arr['channel'] == 0) $arr['channel'] = 1;
            $this->ChannelUnit = new ChannelUnit($arr['channel'], $aid);
            $this->TypeLink = new TypeLink($arr['typeid']);
            if ($this->ChannelUnit->ChannelInfos['issystem'] != -1) {
                //如果当前文档不是系统模型，为自定义模型
                $query = "SELECT arc.*,tp.reid,tp.typedir,ch.addtable,mb.uname,mb.face,mb.userid,mb.sex FROM `#@__archives` arc LEFT JOIN `#@__arctype` tp on tp.id=arc.typeid LEFT JOIN `#@__channeltype` as ch on arc.channel = ch.id LEFT JOIN `#@__member` mb on arc.mid = mb.mid WHERE arc.id='$aid' ";
                $this->Fields = $this->dsql->GetOne($query);
                if (!is_array($this->Fields)) {
                    echo DedeAlert('id:'.$aid.'的文档数据不存在',ALERT_DANGER);
                }
            } else {
                $this->Fields['title'] = '';
                $this->Fields['money'] = $this->Fields['arcrank'] = 0;
                $this->Fields['senddate'] = $this->Fields['pubdate'] = $this->Fields['mid'] = $this->Fields['adminid'] = 0;
                $this->Fields['ismake'] = 1;
                $this->Fields['filename'] = '';
            }
            if ($this->TypeLink->TypeInfos['corank'] > 0 && $this->Fields['arcrank'] == 0) {
                $this->Fields['arcrank'] = $this->TypeLink->TypeInfos['corank'];
            }
            $this->Fields['tags'] = GetTags($aid);
            $this->dtp = new DedeTagParse();
            $this->dtp->SetRefObj($this);
            $this->SplitPageField = $this->ChannelUnit->SplitPageField;
            $this->SplitFields = '';
            $this->TotalPage = 1;
            $this->NameFirst = '';
            $this->ShortName = 'html';
            $this->FixedValues = '';
            $this->TempSource = '';
            $this->remoteDir = '';
            if (empty($GLOBALS['PageNo'])) {
                $this->NowPage = 1;
            } else {
                $this->NowPage = $GLOBALS['PageNo'];
            }
            //特殊的字段数据处理
            $this->Fields['aid'] = $aid;
            $this->Fields['id'] = $aid;
            $this->Fields['position'] = $this->TypeLink->GetPositionLink(TRUE);
            $this->Fields['typeid'] = $arr['typeid'];
            //设置一些全局参数的值
            foreach ($GLOBALS['PubFields'] as $k => $v) {
                $this->Fields[$k] = $v;
            }
            //为了减少重复查询，这里直接把附加表查询记录放在$this->addTableRow中，在ParAddTable()不再查询
            if ($this->ChannelUnit->ChannelInfos['addtable'] != '') {
                if ($this->ChannelUnit->ChannelID < -1) {
                    $query = "SELECT tb.*,mb.uname,mb.face,mb.userid,mb.sex FROM `{$this->ChannelUnit->ChannelInfos['addtable']}` tb LEFT JOIN `#@__member` mb on tb.mid = mb.mid WHERE tb.`aid` = '$aid'";
                } else {
                    $query = "SELECT * FROM `{$this->ChannelUnit->ChannelInfos['addtable']}` WHERE `aid` = '$aid'";
                }
                $this->addTableRow = $this->dsql->GetOne($query);
            }
            //issystem==-1表示自定义模型，自定义模型不支持redirecturl这类参数，因此限定文档普通模型才进行下面查询
            if ($this->ChannelUnit->ChannelInfos['addtable'] != '' && $this->ChannelUnit->ChannelInfos['issystem'] != -1) {
                if (is_array($this->addTableRow)) {
                    $this->Fields['redirecturl'] = $this->addTableRow['redirecturl'];
                    $this->Fields['templet'] = $this->addTableRow['templet'];
                    $this->Fields['userip'] = $this->addTableRow['userip'];
                }
                $this->Fields['templet'] = (empty($this->Fields['templet']) ? '' : trim($this->Fields['templet']));
                $this->Fields['redirecturl'] = (empty($this->Fields['redirecturl']) ? '' : trim($this->Fields['redirecturl']));
                $this->Fields['userip'] = (empty($this->Fields['userip']) ? '' : trim($this->Fields['userip']));
            } else {
                $this->Fields['templet'] = $this->Fields['redirecturl'] = '';
                $this->Fields['uname'] = $this->addTableRow['uname'];
            }
            $this->Fields['face'] = empty($this->Fields['face'])? $GLOBALS['cfg_mainsite'].'/static/web/img/admin.png' : $this->Fields['face'];
            $this->Fields['userurl'] = $GLOBALS['cfg_memberurl'].'/index.php?uid='.$this->Fields['userid'];
        } //!error
    }
    //php4构造函数
    function Archives($aid)
    {
        $this->__construct($aid);
    }
    /**
     *  解析附加表的文档
     *
     * @access    public
     * @return    void
     */
    function ParAddTable()
    {
        //读取附加表信息，并把附加表的资料经过编译处理后导入到$this->Fields中，以方便在模板中用“{dede:field name='fieldname'/}”标记统一调用
        if ($this->ChannelUnit->ChannelInfos['addtable'] != '') {
            $row = $this->addTableRow;
            if ($this->ChannelUnit->ChannelInfos['issystem'] == -1) {
                $this->Fields['title'] = $row['title'];
                $this->Fields['senddate'] = $this->Fields['pubdate'] = $row['senddate'];
                $this->Fields['mid'] = $this->Fields['adminid'] = $row['mid'];
                $this->Fields['ismake'] = 1;
                $this->Fields['arcrank'] = 0;
                $this->Fields['money'] = 0;
                $this->Fields['filename'] = '';
            }
            if (is_array($row)) {
                foreach ($row as $k => $v) $row[strtolower($k)] = stripcslashes($v);
            }
            if (is_array($this->ChannelUnit->ChannelFields) && !empty($this->ChannelUnit->ChannelFields)) {
                foreach ($this->ChannelUnit->ChannelFields as $k => $arr) {
                    if (isset($row[$k])) {
                        if (!empty($arr['rename'])) {
                            $nk = $arr['rename'];
                        } else {
                            $nk = $k;
                        }
                        $cobj = $this->GetCurTag($k);
                        if (is_object($cobj)) {
                            foreach ($this->dtp->CTags as $ctag) {
                                if ($ctag->GetTagName() == 'field' && $ctag->GetAtt('name') == $k) {
                                    //带标识的专题节点
                                    if ($ctag->GetAtt('noteid') != '') {
                                        $this->Fields[$k.'_'.$ctag->GetAtt('noteid')] = $this->ChannelUnit->MakeField($k, $row[$k], $ctag);
                                    }
                                    //带类型的字段节点
                                    else if ($ctag->GetAtt('type') != '') {
                                        $this->Fields[$k.'_'.$ctag->GetAtt('type')] = $this->ChannelUnit->MakeField($k, $row[$k], $ctag);
                                    }
                                    //其它字段
                                    else {
                                        $this->Fields[$nk] = $this->ChannelUnit->MakeField($k, $row[$k], $ctag);
                                    }
                                }
                            }
                        } else {
                            $this->Fields[$nk] = $row[$k];
                        }
                        if ($arr['type'] == 'htmltext' && $GLOBALS['cfg_keyword_replace'] == 'Y' && !empty($this->Fields['keywords'])) {
                            $this->Fields[$nk] = $this->ReplaceKeyword($this->Fields['keywords'], $this->Fields[$nk]);
                        }
                    }
                }
            }
            //设置全局环境变量
            $this->Fields['typename'] = $this->TypeLink->TypeInfos['typename'];
            @SetSysEnv($this->Fields['typeid'], $this->Fields['typename'], $this->Fields['id'], $this->Fields['title'], 'archives');
        }
        //完成附加表信息读取
        unset($row);
        //处理要分页显示的字段
        $this->SplitTitles = array();
        if ($this->SplitPageField != '' && $GLOBALS['cfg_arcsptitle'] = 'Y' && isset($this->Fields[$this->SplitPageField])) {
            $this->SplitFields = explode("#p#", $this->Fields[$this->SplitPageField]);
            $i = 1;
            foreach ($this->SplitFields as $k => $v) {
                $tmpv = cn_substr($v, 50);
                $pos = strpos($tmpv, '#e#');
                if ($pos > 0) {
                    $st = trim(cn_substr($tmpv, $pos));
                    if ($st == "" || $st == "副标题" || $st == "分页标题") {
                        $this->SplitFields[$k] = preg_replace("/^(.*)#e#/is", "", $v);
                        continue;
                    } else {
                        $this->SplitFields[$k] = preg_replace("/^(.*)#e#/is", "", $v);
                        $this->SplitTitles[$k] = $st;
                    }
                } else {
                    continue;
                }
                $i++;
            }
            $this->TotalPage = count($this->SplitFields);
            $this->Fields['totalpage'] = $this->TotalPage;
        }
        //处理默认缩略图等
        if (isset($this->Fields['litpic'])) {
            if ($this->Fields['litpic'] == '-' || $this->Fields['litpic'] == '') {
                $this->Fields['litpic'] = $GLOBALS['cfg_cmspath'].'/static/web/img/thumbnail.jpg';
            }
            if (!preg_match("/^(http|https):\/\//i", $this->Fields['litpic']) && $GLOBALS['cfg_multi_site'] == 'Y') {
                $this->Fields['litpic'] = $GLOBALS['cfg_mainsite'].$this->Fields['litpic'];
            }
            $this->Fields['picname'] = $this->Fields['litpic'];
            //模板里直接使用{dede:field name='image'/}获取缩略图
            $this->Fields['image'] = (!preg_match('/jpg|gif|png/i', $this->Fields['picname']) ? '' : "<img src='{$this->Fields['picname']}'>");
        }
        //处理投票选项
        if (isset($this->Fields['voteid']) && !empty($this->Fields['voteid'])) {
            $this->Fields['vote'] = '';
            $voteid = $this->Fields['voteid'];
            $this->Fields['vote'] = "<script src='{$GLOBALS['cfg_cmspath']}/data/vote/vote_{$voteid}.js'></script>";
            if ($GLOBALS['cfg_multi_site'] == 'Y') {
                $this->Fields['vote'] = "<script src='{$GLOBALS['cfg_mainsite']}/data/vote/vote_{$voteid}.js'></script>";
            }
        }
        if (isset($this->Fields['goodpost']) && isset($this->Fields['badpost'])) {
            //digg
            if ($this->Fields['goodpost'] + $this->Fields['badpost'] == 0) {
                $this->Fields['goodper'] = $this->Fields['badper'] = 0;
            } else {
                $this->Fields['goodper'] = number_format($this->Fields['goodpost'] / ($this->Fields['goodpost'] + $this->Fields['badpost']), 3) * 100;
                $this->Fields['badper'] = 100 - $this->Fields['goodper'];
            }
        }
    }
    //获得当前字段参数
    function GetCurTag($fieldname)
    {
        if (!isset($this->dtp->CTags)) {
            return '';
        }
        foreach ($this->dtp->CTags as $ctag) {
            if ($ctag->GetTagName() == 'field' && $ctag->GetAtt('name') == $fieldname) {
                return $ctag;
            } else {
                continue;
            }
        }
        return '';
    }
    /**
     *  生成静态网页
     *
     * @access    public
     * @param     int    $isremote  是否远程
     * @return    string
     */
    function MakeHtml($isremote = 0)
    {
        global $fileFirst, $cfg_basehost;
        if ($this->IsError) {
            return '';
        }
        $this->Fields["displaytype"] = "st";
        //预编译$th
        $this->LoadTemplet();
        $this->ParAddTable();
        $this->ParseTempletsFirst();
        $this->Fields['senddate'] = empty($this->Fields['senddate']) ? '' : $this->Fields['senddate'];
        $this->Fields['title'] = empty($this->Fields['title']) ? '' : $this->Fields['title'];
        $this->Fields['arcrank'] = empty($this->Fields['arcrank']) ? 0 : $this->Fields['arcrank'];
        $this->Fields['ismake'] = empty($this->Fields['ismake']) ? 0 : $this->Fields['ismake'];
        $this->Fields['money'] = empty($this->Fields['money']) ? 0 : $this->Fields['money'];
        $this->Fields['filename'] = empty($this->Fields['filename']) ? '' : $this->Fields['filename'];
        //分析要创建的文件名称
        $filename = GetFileNewName(
            $this->ArcID,
            $this->Fields['typeid'],
            $this->Fields['senddate'],
            $this->Fields['title'],
            $this->Fields['ismake'],
            $this->Fields['arcrank'],
            $this->TypeLink->TypeInfos['namerule'],
            $this->TypeLink->TypeInfos['typedir'],
            $this->Fields['money'],
            $this->Fields['filename']
        );
        $filenames  = explode(".", $filename);
        $this->ShortName = $filenames[count($filenames) - 1];
        if ($this->ShortName == '') $this->ShortName = 'html';
        $fileFirst = preg_replace("/\.".$this->ShortName."$/i", "", $filename);
        $this->Fields['namehand'] = basename($fileFirst);
        $filenames  = explode("/", $filename);
        $this->NameFirst = preg_replace("/\.".$this->ShortName."$/i", "", $filenames[count($filenames) - 1]);
        if ($this->NameFirst == '') {
            $this->NameFirst = $this->ArcID;
        }
        //获得当前文档的全名
        $filenameFull = GetFileUrl(
            $this->ArcID,
            $this->Fields['typeid'],
            $this->Fields["senddate"],
            $this->Fields["title"],
            $this->Fields["ismake"],
            $this->Fields["arcrank"],
            $this->TypeLink->TypeInfos['namerule'],
            $this->TypeLink->TypeInfos['typedir'],
            $this->Fields["money"],
            $this->Fields['filename'],
            $this->TypeLink->TypeInfos['moresite'],
            $this->TypeLink->TypeInfos['siteurl'],
            $this->TypeLink->TypeInfos['sitepath']
        );
        $this->Fields['arcurl'] = $this->Fields['fullname'] = $filenameFull;
        //对于已设置不生成网页的文档直接返回网址
        if (
            $this->Fields['ismake'] == -1 || $this->Fields['arcrank'] != 0 || $this->Fields['money'] > 0 || ($this->Fields['typeid'] == 0 && $this->Fields['channel'] != -1)) {
            return $this->GetTrueUrl($filename);
        }
        //循环生成网页文件
        else {
            $seoUrls = array();
            for ($i = 1; $i <= $this->TotalPage; $i++) {
                if ($this->TotalPage > 1) {
                    $this->Fields['tmptitle'] = (empty($this->Fields['tmptitle']) ? $this->Fields['title'] : $this->Fields['tmptitle']);
                    if ($i > 1) $this->Fields['title'] = $this->Fields['tmptitle']."-第"."$i"."页";
                }
                if ($i > 1) {
                    $TRUEfilename = $this->GetTruePath().$fileFirst."-".$i.".".$this->ShortName;
                    $URLFilename = $fileFirst."-".$i.".".$this->ShortName;
                } else {
                    $TRUEfilename = $this->GetTruePath().$filename;
                    $URLFilename = $filename;
                }
                $seoUrls = array_merge($seoUrls, array($cfg_basehost.$URLFilename));
                $this->ParseDMFields($i, 1);
                $this->dtp->SaveTo($TRUEfilename);
            }
        }
        $this->dsql->ExecuteNoneQuery("UPDATE `#@__archives` SET ismake=1 WHERE id='".$this->ArcID."'");
        return $this->GetTrueUrl($filename);
    }
    /**
     *  获得真实连接路径
     *
     * @access    public
     * @param     string    $nurl  连接
     * @return    string
     */
    function GetTrueUrl($nurl)
    {
        return GetFileUrl(
            $this->Fields['id'],
            $this->Fields['typeid'],
            $this->Fields['senddate'],
            $this->Fields['title'],
            $this->Fields['ismake'],
            $this->Fields['arcrank'],
            $this->TypeLink->TypeInfos['namerule'],
            $this->TypeLink->TypeInfos['typedir'],
            $this->Fields['money'],
            $this->Fields['filename'],
            $this->TypeLink->TypeInfos['moresite'],
            $this->TypeLink->TypeInfos['siteurl'],
            $this->TypeLink->TypeInfos['sitepath']
        );
    }
    /**
     *  获得站点的真实根路径
     *
     * @access    public
     * @return    string
     */
    function GetTruePath()
    {
        $TRUEpath = $GLOBALS["cfg_basedir"];
        return $TRUEpath;
    }
    /**
     *  获得指定键值的字段
     *
     * @access    public
     * @param     string  $fname  键名称
     * @param     object  $ctag  标记
     * @return    string
     */
    function GetField($fname, $ctag)
    {
        //所有Field数组OR普通Field
        if ($fname == 'array') {
            return $this->Fields;
        }
        //指定了id的节点
        else if ($ctag->GetAtt('noteid') != '') {
            if (isset($this->Fields[$fname.'_'.$ctag->GetAtt('noteid')])) {
                return $this->Fields[$fname.'_'.$ctag->GetAtt('noteid')];
            }
        }
        //指定了type的节点
        else if ($ctag->GetAtt('type') != '') {
            if (isset($this->Fields[$fname.'_'.$ctag->GetAtt('type')])) {
                return $this->Fields[$fname.'_'.$ctag->GetAtt('type')];
            }
        } else if (isset($this->Fields[$fname])) {
            return $this->Fields[$fname];
        }
        return '';
    }
    /**
     *  获得模板文件位置
     *
     * @access    public
     * @return    string
     */
    function GetTempletFile()
    {
        global $cfg_basedir, $cfg_templets_dir, $cfg_df_style;
        $cid = $this->ChannelUnit->ChannelInfos['nid'];
        if (!empty($this->Fields['templet'])) {
            $filetag = MfTemplet($this->Fields['templet']);
            if (!preg_match("#\/#", $filetag)) $filetag = $GLOBALS['cfg_df_style'].'/'.$filetag;
        } else {
            $filetag = MfTemplet($this->TypeLink->TypeInfos["temparticle"]);
        }
        $tid = $this->Fields['typeid'];
        $filetag = str_replace('{cid}', $cid, $filetag);
        $filetag = str_replace('{tid}', $tid, $filetag);
        $tmpfile = $cfg_basedir.$cfg_templets_dir.'/'.$filetag;
        if ($cid == 'spec') {
            if (!empty($this->Fields['templet'])) {
                $tmpfile = $cfg_basedir.$cfg_templets_dir.'/'.$filetag;
            } else {
                $tmpfile = $cfg_basedir.$cfg_templets_dir."/{$cfg_df_style}/article_spec.htm";
            }
        }
        if (!file_exists($tmpfile)) {
            $tmpfile = $cfg_basedir.$cfg_templets_dir."/{$cfg_df_style}/".($cid == 'spec' ? 'article_spec.htm' : 'article_default.htm');
        }
        if (!preg_match("#.htm$#", $tmpfile)) return FALSE;
        return $tmpfile;
    }
    /**
     *  动态输出结果
     *
     * @access    public
     * @return    void
     */
    function display()
    {
        global $htmltype;
        if ($this->IsError) {
            return '';
        }
        $this->Fields["displaytype"] = "dm";
        if ($this->NowPage > 1) $this->Fields["title"] = $this->Fields["title"]."-第"."{$this->NowPage}"."页";
        //预编译
        $this->LoadTemplet();
        $this->ParAddTable();
        $this->ParseTempletsFirst();
        //跳转网址
        $this->Fields['flag'] = empty($this->Fields['flag']) ? "" : $this->Fields['flag'];
        if (preg_match("#j#", $this->Fields['flag']) && $this->Fields['redirecturl'] != '') {
            if ($GLOBALS['cfg_jump_once'] == 'N') {
                $pageHtml = "<html>\r\n<head>\r\n<meta charset=".$GLOBALS['cfg_soft_lang']."\">\r\n<title>".$this->Fields['title']."</title>\r\n";
                $pageHtml .= "<meta http-equiv=\"refresh\" content=\"3;URL=".$this->Fields['redirecturl']."\">\r\n</head>\r\n<body>\r\n";
                $pageHtml .= "<p>正在前往文档：".$this->Fields['title']."<p>\r\n<p>文档描述：".$this->Fields['description']."</p>\r\n</body>\r\n</html>";
                echo $pageHtml;
            } else {
                header("location:{$this->Fields['redirecturl']}");
            }
            exit();
        }
        $pageCount = $this->NowPage;
        $this->ParseDMFields($pageCount, 0);
        $this->dtp->display();
    }
    /**
     *  载入模板
     *
     * @access    public
     * @return    void
     */
    function LoadTemplet()
    {
        if ($this->TempSource == '') {
            $tempfile = $this->GetTempletFile();
            if (!file_exists($tempfile) || !is_file($tempfile)) {
                echo "文档id：{$this->Fields['id']}，标题：{$this->Fields['title']}，主题模板文件不存在，无法更新文档";
                exit();
            }
            $this->dtp->LoadTemplate($tempfile);
            $this->TempSource = $this->dtp->SourceString;
        } else {
            $this->dtp->LoadSource($this->TempSource);
        }
    }
    /**
     *  解析模板，对固定的标记进行初始给值
     *
     * @access    public
     * @return    void
     */
    function ParseTempletsFirst()
    {
        if (empty($this->Fields['keywords'])) {
            $this->Fields['keywords'] = '';
        }
        if (empty($this->Fields['reid'])) {
            $this->Fields['reid'] = 0;
        }
        $GLOBALS['envs']['tags'] = $this->Fields['tags'];

        if (isset($this->TypeLink->TypeInfos['reid'])) {
            $GLOBALS['envs']['reid'] = $this->TypeLink->TypeInfos['reid'];
        }
        $GLOBALS['envs']['keyword'] = $this->Fields['keywords'];
        $GLOBALS['envs']['typeid'] = $this->Fields['typeid'];
        $GLOBALS['envs']['topid'] = GetTopid($this->Fields['typeid']);
        $GLOBALS['envs']['aid'] = $GLOBALS['envs']['id'] = $this->Fields['id'];
        $GLOBALS['envs']['adminid'] = $GLOBALS['envs']['mid'] = isset($this->Fields['mid']) ? $this->Fields['mid'] : 1;
        $GLOBALS['envs']['channelid'] = $this->TypeLink->TypeInfos['channeltype'];
        if ($this->Fields['reid'] > 0) {
            $GLOBALS['envs']['typeid'] = $this->Fields['reid'];
        }
        MakeOneTag($this->dtp, $this, 'N');
    }
    /**
     *  解析模板，对文档里的变动进行赋值
     *
     * @access    public
     * @param     string  $PageNo  页码数
     * @param     string  $ismake  是否生成
     * @return    string
     */
    function ParseDMFields($PageNo, $ismake = 1)
    {
        $this->NowPage = $PageNo;
        $this->Fields['nowpage'] = $this->NowPage;
        if ($this->SplitPageField != '' && isset($this->Fields[$this->SplitPageField])) {
            $this->Fields[$this->SplitPageField] = $this->SplitFields[$PageNo - 1];
            if ($PageNo > 1) $this->Fields['description'] = trim(preg_replace("/[\r\n\t]/", ' ', cn_substr(html2text($this->Fields[$this->SplitPageField]), 200)));
        }
        //解析模板
        if (is_array($this->dtp->CTags)) {
            foreach ($this->dtp->CTags as $i => $ctag) {
                if ($ctag->GetName() == 'field') {
                    $this->dtp->Assign($i, $this->GetField($ctag->GetAtt('name'), $ctag));
                } else if ($ctag->GetName() == 'pagebreak') {
                    if ($ismake == 0) {
                        $this->dtp->Assign($i, $this->GetPagebreakDM($this->TotalPage, $this->NowPage, $this->ArcID));
                    } else {
                        $this->dtp->Assign($i, $this->GetPagebreak($this->TotalPage, $this->NowPage, $this->ArcID));
                    }
                } else if ($ctag->GetName() == 'pagetitle') {
                    if ($ismake == 0) {
                        $this->dtp->Assign($i, $this->GetPageTitlesDM($ctag->GetAtt("style"), $PageNo));
                    } else {
                        $this->dtp->Assign($i, $this->GetPageTitlesST($ctag->GetAtt("style"), $PageNo));
                    }
                } else if ($ctag->GetName() == 'prenext') {
                    $this->dtp->Assign($i, $this->GetPreNext($ctag->GetAtt('get')));
                }
                //添加上篇下篇标签{dede:prenextdiy get='pre'}{/dede:prenextdiy}{dede:prenextdiy get='next'}{/dede:prenextdiy}
                else if ($ctag->GetName()=='prenextdiy')
                {
                    $innertext = trim($ctag->GetInnerText());
                    if ($innertext) {
                        $get = $ctag->GetAtt('get');
                        $diys['diy'] = $this->GetPreNext('diy');
                        $revalue = '';
                        $dtp2 = new DedeTagParse();
                        $dtp2->SetNameSpace('field','[',']');
                        $dtp2->LoadSource($innertext);
                        foreach($diys as $row)
                        {
                            foreach($dtp2->CTags as $tid=>$ctag2)
                            {
                                if (isset($row[$get][$ctag2->GetName()])) {
                                    $dtp2->Assign($tid,$row[$get][$ctag2->GetName()]);
                                }
                            }
                            $revalue .= $dtp2->GetResult();
                        }
                        if ($row[$get]['id']) $this->dtp->Assign($i,$revalue);
                    }
                }
                else if ($ctag->GetName() == 'fieldlist') {
                    $innertext = trim($ctag->GetInnerText());
                    if ($innertext == '') $innertext = GetSysTemplets('tag_fieldlist.htm');
                    $dtp2 = new DedeTagParse();
                    $dtp2->SetNameSpace('field', '[', ']');
                    $dtp2->LoadSource($innertext);
                    $oldSource = $dtp2->SourceString;
                    $oldCtags = $dtp2->CTags;
                    $res = '';
                    if (is_array($this->ChannelUnit->ChannelFields) && is_array($dtp2->CTags)) {
                        foreach ($this->ChannelUnit->ChannelFields as $k => $v) {
                            if (isset($v['autofield']) && empty($v['autofield'])) {
                                continue;
                            }
                            $dtp2->SourceString = $oldSource;
                            $dtp2->CTags = $oldCtags;
                            $fname = $v['itemname'];
                            foreach ($dtp2->CTags as $tid => $ctag2) {
                                if ($ctag2->GetName() == 'name') {
                                    $dtp2->Assign($tid, $fname);
                                } else if ($ctag2->GetName() == 'tagname') {
                                    $dtp2->Assign($tid, $k);
                                } else if ($ctag2->GetName() == 'value') {
                                    if (isset($this->Fields[$k])) {
                                        $this->Fields[$k] = $this->ChannelUnit->MakeField($k, $this->Fields[$k], $ctag2);
                                        @$dtp2->Assign($tid, $this->Fields[$k]);
                                    }
                                }
                            }
                            $res .= $dtp2->GetResult();
                        }
                    }
                    $this->dtp->Assign($i, $res);
                }
            }
        }
    }
    /**
     *  关闭所占用的资源
     *
     * @access    public
     * @return    void
     */
    function Close()
    {
        $this->FixedValues = '';
        $this->Fields = '';
    }
    /**
     *  获取上篇和下篇链接
     *
     * @access    public
     * @param     string  $gtype  pre为上篇，preimg为上篇图片，next为下篇，nextimg为下篇图片
     * @return    string
     */
    function GetPreNext($gtype = '')
    {
        $rs = '';
        if (count($this->PreNext) < 2) {
            $aid = $this->ArcID;
            $preR =  $this->dsql->GetOne("SELECT id FROM `#@__arctiny` WHERE id<$aid And arcrank>-1 And typeid='{$this->Fields['typeid']}' ORDER BY id DESC");
            $nextR = $this->dsql->GetOne("SELECT id FROM `#@__arctiny` WHERE id>$aid And arcrank>-1 And typeid='{$this->Fields['typeid']}' ORDER BY id ASC");
            $next = (is_array($nextR) ? " WHERE arc.id={$nextR['id']} " : ' WHERE 1>2 ');
            $pre = (is_array($preR) ? " WHERE arc.id={$preR['id']} " : ' WHERE 1>2 ');
            $query = "SELECT arc.id,arc.title,arc.shorttitle,arc.typeid,arc.ismake,arc.senddate,arc.arcrank,arc.money,arc.filename,arc.litpic,t.typedir,t.typename,t.namerule,t.namerule2,t.ispart,t.moresite,t.siteurl,t.sitepath FROM `#@__archives` arc LEFT JOIN `#@__arctype` t on arc.typeid=t.id ";
            $nextRow = $this->dsql->GetOne($query.$next);
            $preRow = $this->dsql->GetOne($query.$pre);
            if (is_array($preRow)) {
                $mlink = GetFileUrl(
                    $preRow['id'],
                    $preRow['typeid'],
                    $preRow['senddate'],
                    $preRow['title'],
                    $preRow['ismake'],
                    $preRow['arcrank'],
                    $preRow['namerule'],
                    $preRow['typedir'],
                    $preRow['money'],
                    $preRow['filename'],
                    $preRow['moresite'],
                    $preRow['siteurl'],
                    $preRow['sitepath']
                );
                $preRow['litpic'] = (empty($preRow['litpic'])) ? $GLOBALS['cfg_cmspath'].'/static/web/img/thumbnail.jpg' : $preRow['litpic'];
                $this->PreNext['diy']['pre']['id'] = $preRow['id'];
                $this->PreNext['diy']['pre']['arcurl'] = $mlink;
                $this->PreNext['diy']['pre']['title'] = $preRow['title'];
                $this->PreNext['diy']['pre']['litpic'] = $preRow['litpic'];
                $this->PreNext['diy']['pre']['pubdate'] = $preRow['senddate'];
                $this->PreNext['pre'] = "上篇：<a href='$mlink'>{$preRow['title']}</a>";
                $this->PreNext['preimg'] = "<a href='$mlink'><img src=\"{$preRow['litpic']}\" alt=\"{$preRow['title']}\" title=\"{$preRow['title']}\"></a> ";
            } else {
                $this->PreNext['pre'] = "上篇：暂无";
                $this->PreNext['preimg'] = '';
            }
            if (is_array($nextRow)) {
                $mlink = GetFileUrl(
                    $nextRow['id'],
                    $nextRow['typeid'],
                    $nextRow['senddate'],
                    $nextRow['title'],
                    $nextRow['ismake'],
                    $nextRow['arcrank'],
                    $nextRow['namerule'],
                    $nextRow['typedir'],
                    $nextRow['money'],
                    $nextRow['filename'],
                    $nextRow['moresite'],
                    $nextRow['siteurl'],
                    $nextRow['sitepath']
                );
                $nextRow['litpic'] = (empty($nextRow['litpic'])) ? $GLOBALS['cfg_cmspath'].'/static/web/img/thumbnail.jpg' : $nextRow['litpic'];
                $this->PreNext['diy']['next']['id'] = $nextRow['id'];
                $this->PreNext['diy']['next']['arcurl'] = $mlink;
                $this->PreNext['diy']['next']['title'] = $nextRow['title'];
                $this->PreNext['diy']['next']['litpic'] = $nextRow['litpic'];
                $this->PreNext['diy']['next']['pubdate'] = $nextRow['senddate'];
                $this->PreNext['next'] = "下篇：<a href='$mlink'>{$nextRow['title']}</a> ";
                $this->PreNext['nextimg'] = "<a href='$mlink'><img src=\"{$nextRow['litpic']}\" alt=\"{$nextRow['title']}\" title=\"{$nextRow['title']}\"></a> ";
            } else {
                $this->PreNext['next'] = "下篇：暂无";
                $this->PreNext['nextimg'] = '';
            }
        }
        if ($gtype=='diy') {
            return $this->PreNext['diy'];
        } if ($gtype=='pre') {
            $rs =  $this->PreNext['pre'];
        } else if ($gtype == 'preimg') {
            $rs =  $this->PreNext['preimg'];
        } else if ($gtype == 'next') {
            $rs =  $this->PreNext['next'];
        } else if ($gtype == 'nextimg') {
            $rs =  $this->PreNext['nextimg'];
        } else {
            $rs =  $this->PreNext['pre']." &nbsp; ".$this->PreNext['next'];
        }
        return $rs;
    }
    /**
     *  获得静态文档分页标题
     *
     * @access    public
     * @param     string  $styleName  类型名称
     * @param     string  $PageNo  页码数
     * @return    string
     */
    function GetPageTitlesST($styleName, $PageNo)
    {
        if ($this->TotalPage == 1) {
            return "";
        }
        if (count($this->SplitTitles) == 0) {
            return "";
        }
        $i = 1;
        if ($styleName == 'link') {
            $revalue = '';
            foreach ($this->SplitTitles as $k => $v) {
                if ($i == 1) {
                    $revalue .= "<a href='{$this->NameFirst}.{$this->ShortName}'>{$v}</a>";
                } else {
                    if ($PageNo == $i) {
                        $revalue .= "$v";
                    } else {
                        $revalue .= "<a href='{$this->NameFirst}-{$i}.{$this->ShortName}'>{$v}</a>";
                    }
                }
                $i++;
            }
        } else {
            $revalue = "<select class='form-control w-25' onchange='location.href=this.options[this.selectedIndex].value;'>";
            foreach ($this->SplitTitles as $k => $v) {
                if ($i == 1) {
                    $revalue .= "<option value='{$this->NameFirst}.{$this->ShortName}'>{$i}、{$v}</option>";
                } else {
                    if ($PageNo == $i) {
                        $revalue .= "<option value='{$this->NameFirst}-{$i}.{$this->ShortName}' selected>{$i}、{$v}</option>";
                    } else {
                        $revalue .= "<option value='{$this->NameFirst}-{$i}.{$this->ShortName}'>{$i}、{$v}</option>";
                    }
                }
                $i++;
            }
            $revalue .= "</select>";
        }
        return $revalue;
    }
    /**
     *  获得静态文档分页列表，感谢：乖乖女
     *
     * @access    public
     * @param     int   $totalPage  总页数
     * @param     int   $nowPage  当前页数
     * @param     int   $aid  文档id
     * @return    string
     */
    function GetPagebreak($totalPage, $nowPage, $aid)
    {
        if ($totalPage == 1) {
            return "";
        }
        $PageList = "<li class='page-item disabled'><span class='page-link'>{$totalPage}页</span></li>";
        $nPage = $nowPage - 1;
        $lPage = $nowPage + 1;
        //上一页
        if ($nowPage == 1) {
            $PageList .= "<li class='page-item disabled'><span class='page-link'>上页</span></li>";
        } else {
            $prevHref = ($nPage == 1) ? "{$this->NameFirst}.{$this->ShortName}" : "{$this->NameFirst}-{$nPage}.{$this->ShortName}";
            $PageList .= "<li class='page-item'><a class='page-link' href='{$prevHref}'>上页</a></li>";
        }
        //显示第一页
        if ($nowPage == 1) {
            $PageList .= "<li class='page-item active'><span class='page-link'>1</span></li>";
        } else {
            $PageList .= "<li class='page-item'><a class='page-link' href='{$this->NameFirst}.{$this->ShortName}'>1</a></li>";
        }
        //省略前部分
        if ($nowPage > 4 && $totalPage > 7) {
            $PageList .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        //中间页码
        $start = max(2, $nowPage - 2);
        $end = min($totalPage - 1, $nowPage + 2);
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $nowPage) {
                $PageList .= "<li class='page-item active'><span class='page-link'>{$i}</span></li>";
            } else {
                $PageList .= "<li class='page-item'><a class='page-link' href='{$this->NameFirst}-{$i}.{$this->ShortName}'>{$i}</a></li>";
            }
        }
        //省略后部分
        if ($nowPage < $totalPage - 3 && $totalPage > 7) {
            $PageList .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        //显示最后一页
        if ($totalPage > 1) {
            if ($nowPage == $totalPage) {
                $PageList .= "<li class='page-item active'><span class='page-link'>{$totalPage}</span></li>";
            } else {
                $PageList .= "<li class='page-item'><a class='page-link' href='{$this->NameFirst}-{$totalPage}.{$this->ShortName}'>{$totalPage}</a></li>";
            }
        }
        //下一页
        if ($nowPage >= $totalPage) {
            $PageList .= "<li class='page-item'><span class='page-link'>下页</span></li>";
        } else {
            $nextHref = "{$this->NameFirst}-{$lPage}.{$this->ShortName}";
            $PageList .= "<li class='page-item'><a class='page-link' href='{$nextHref}'>下页</a></li>";
        }
        return $PageList;
    }
    /**
     *  获得动态文档分页标题
     *
     * @access    public
     * @param     string  $styleName  类型名称
     * @param     string  $PageNo  页码数
     * @return    string
     */
    function GetPageTitlesDM($styleName, $PageNo)
    {
        global $cfg_rewrite;
        if ($this->TotalPage == 1) {
            return "";
        }
        if (count($this->SplitTitles) == 0) {
            return "";
        }
        $i = 1;
        $aid = $this->ArcID;
        if ($styleName == 'link') {
            $revalue = '';
            foreach ($this->SplitTitles as $k => $v) {
                if ($i == 1) {
                    if ($cfg_rewrite == 'Y') {
                        $revalue .= "<a href='/article/{$aid}-{$i}.html'>{$v}</a>";
                    } else {
                        $revalue .= "<a href='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$i}'>{$v}</a>";
                    }
                } else {
                    if ($PageNo == $i) {
                        $revalue .= "$v";
                    } else {
                        if ($cfg_rewrite == 'Y') {
                            $revalue .= "<a href='/article/{$aid}-{$i}.html'>{$v}</a>";
                        } else {
                            $revalue .= "<a href='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$i}'>{$v}</a>";
                        }
                        
                    }
                }
                $i++;
            }
        } else {
            $revalue = "<select class='form-control w-25' onchange='location.href=this.options[this.selectedIndex].value;'>";
            foreach ($this->SplitTitles as $k => $v) {
                if ($i == 1) {
                    if ($cfg_rewrite == 'Y') {
                        $revalue .= "<option value='/article/{$aid}-{$i}.html'>{$i}、{$v}</option>";
                    } else {
                        $revalue .= "<option value='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$i}'>{$i}、{$v}</option>";
                    }
                } else {
                    if ($PageNo == $i) {
                        if ($cfg_rewrite == 'Y') {
                            $revalue .= "<option value='/article/{$aid}-{$i}.html' selected>{$i}、{$v}</option>";
                        } else {
                            $revalue .= "<option value='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$i}' selected>{$i}、{$v}</option>";
                        }
                    } else {
                        if ($cfg_rewrite == 'Y') {
                            $revalue .= "<option value='/article/{$aid}-{$i}.html'>{$i}、{$v}</option>";
                        } else {
                            $revalue .= "<option value='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$i}'>{$i}、{$v}</option>";
                        }
                    }
                }
                $i++;
            }
            $revalue .= "</select>";
        }
        return $revalue;
    }
    /**
     *  获得动态文档分页列表，感谢：乖乖女
     *
     * @access    public
     * @param     int   $totalPage  总页数
     * @param     int   $nowPage  当前页数
     * @param     int   $aid  文档id
     * @return    string
     */
    function GetPagebreakDM($totalPage, $nowPage, $aid)
    {
        global $cfg_rewrite, $cfg_cmsurl;
        if ($totalPage == 1) {
            return "";
        }
        $PageList = "<li class='page-item disabled'><span class='page-link'>{$totalPage}页</span></li>";
        $nPage = $nowPage - 1;
        $lPage = $nowPage + 1;
        //上一页
        if ($nowPage == 1) {
            $PageList .= "<li class='page-item disabled'><span class='page-link'>上页</span></li>";
        } else {
            if ($nPage == 1) {
                if ($cfg_rewrite == 'Y') {
                    $PageList .= "<li class='page-item'><a class='page-link' href='".$cfg_cmsurl."/article/{$aid}.html'>上页</a></li>";
                } else {
                    $PageList .= "<li class='page-item'><a class='page-link' href='".$this->Fields['phpurl']."/view.php?aid={$aid}'>上页</a></li>";
                }
            } else {
                if ($cfg_rewrite == 'Y') {
                    $PageList .= "<li class='page-item'><a class='page-link' href='".$cfg_cmsurl."/article/{$aid}-{$nPage}.html'>上页</a></li>";
                } else {
                    $PageList .= "<li class='page-item'><a class='page-link' href='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$nPage}'>上页</a></li>";
                }
            }
        }
        //第一页
        if ($nowPage != 1) {
            if ($cfg_rewrite == 'Y') {
                $PageList .= "<li class='page-item'><a class='page-link' href='".$cfg_cmsurl."/article/{$aid}.html'>1</a></li>";
            } else {
                $PageList .= "<li class='page-item'><a class='page-link' href='".$this->Fields['phpurl']."/view.php?aid={$aid}'>1</a></li>";
            }
        } else {
            $PageList .= "<li class='page-item active'><span class='page-link'>1</span></li>";
        }
        //省略前部分
        if ($nowPage > 4 && $totalPage > 7) {
            $PageList .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        //中间页码（最多显示3个）
        $start = max(2, $nowPage - 2);
        $end = min($totalPage - 1, $nowPage + 2);
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $nowPage) {
                $PageList .= "<li class='page-item active'><span class='page-link'>{$i}</span></li>";
            } else {
                if ($cfg_rewrite == 'Y') {
                    $PageList .= "<li class='page-item'><a class='page-link' href='".$cfg_cmsurl."/article/{$aid}-{$i}.html'>{$i}</a></li>";
                } else {
                    $PageList .= "<li class='page-item'><a class='page-link' href='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$i}'>{$i}</a></li>";
                }
            }
        }
        //省略后部分
        if ($nowPage < $totalPage - 3 && $totalPage > 7) {
            $PageList .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        //最后一页
        if ($nowPage != $totalPage) {
            if ($cfg_rewrite == 'Y') {
                $PageList .= "<li class='page-item'><a class='page-link' href='".$cfg_cmsurl."/article/{$aid}-{$totalPage}.html'>{$totalPage}</a></li>";
            } else {
                $PageList .= "<li class='page-item'><a class='page-link' href='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$totalPage}'>{$totalPage}</a></li>";
            }
        } else {
            $PageList .= "<li class='page-item active'><span class='page-link'>{$totalPage}</span></li>";
        }
        //下一页
        if ($lPage <= $totalPage) {
            if ($cfg_rewrite == 'Y') {
                $PageList .= "<li class='page-item'><a class='page-link' href='".$cfg_cmsurl."/article/{$aid}-{$lPage}.html'>下页</a></li>";
            } else {
                $PageList .= "<li class='page-item'><a class='page-link' href='".$this->Fields['phpurl']."/view.php?aid={$aid}&PageNo={$lPage}'>下页</a></li>";
            }
        } else {
            $PageList .= "<li class='page-item'><span class='page-link'>下页</span></li>";
        }
        return $PageList;
    }
    /**
     * 高亮问题修正，排除alt、title、a标签直接字符替换
     *
     * @param string $kw
     * @param string $body
     * @return string
     */
    function ReplaceKeyword($kw,&$body)
    {
        $karr = $kaarr = $GLOBALS['replaced'] = array();
        //暂时屏蔽超链接
        $body = preg_replace("#(<a(.*))(>)(.*)(<)(\/a>)#isU", '\\1-]-\\4-[-\\6', $body);
        $query = "SELECT * FROM `#@__keywords` WHERE rpurl<>'' ORDER BY `rank` DESC";
        $this->dsql->SetQuery($query);
        $this->dsql->Execute();
        while ($row = $this->dsql->GetArray()) {
            $key = trim($row['keyword']);
            $key_url = trim($row['rpurl']);
            $karr[] = $key;
            $kaarr[] = "<a href='$key_url' target='_blank'>$key</a>";
        }
        $GLOBALS['_dd_karr'] = $karr;
        $GLOBALS['_dd_kaarr'] = $kaarr;
        $body = preg_replace_callback("#(^|>)([^<]+)(?=<|$)#sU", "_highlightkeywords", $body);
        //恢复超链接
        $body = preg_replace("#(<a(.*))-\]-(.*)-\[-(\/a>)#isU", '\\1>\\3<\\4', $body);
        return $body;
    }
}
function _highlightkeywords($matches)
{
    return _highlight($matches[2], $GLOBALS['_dd_karr'], $GLOBALS['_dd_kaarr'], $matches[1]);
}
//高亮专用
function _highlight($string, $words, $result, $pre)
{
    global $cfg_replace_num;
    $string = str_replace('\"', '"', $string);
    if ($cfg_replace_num > 0) {
        foreach ($words as $key => $word) {
            if ($GLOBALS['replaced'][$word] == 1) {
                continue;
            }
            $string = preg_replace("#".preg_quote($word)."#", $result[$key], $string, $cfg_replace_num);
            if (strpos($string, $word) !== FALSE) {
                $GLOBALS['replaced'][$word] = 1;
            }
        }
    } else {
        $string = str_replace($words, $result, $string);
    }
    return $pre.$string;
}
?>