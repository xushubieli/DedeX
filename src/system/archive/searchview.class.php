<?php
if (!defined('DEDEINC')) exit('dedebiz');
/**
 * 搜索视图
 *
 * @version        $id:searchview.class.php 15:26 2010年7月7日 tianya $
 * @package        DedeBIZ.Libraries
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(DEDEINC."/typelink/typelink.class.php");
require_once(DEDEINC."/dedetag.class.php");
require_once(DEDEINC."/libraries/splitword.class.php");
require_once(DEDEINC."/taglib/hotwords.lib.php");
require_once(DEDEINC."/taglib/channelartlist.lib.php");
require_once(DEDEINC."/taglib/channel.lib.php");
require_once(DEDEINC."/taglib/arclist.lib.php");
@set_time_limit(0);
@ini_set('memory_limit', '512M');
class SearchView
{
    var $dsql;
    var $dtp;
    var $dtp2;
    var $TypeID;
    var $TypeLink;
    var $PageNo;
    var $TotalPage;
    var $TotalResult;
    var $pagesize;
    var $AddTable;
    var $ChannelType;
    var $ChannelTypeid;
    var $TempInfos;
    var $Fields;
    var $PartView;
    var $StartTime;
    var $Keywords;
    var $OrderBy;
    var $SearchType;
    var $mid;
    var $KType;
    var $Keyword;
    var $SearchKeyword;
    var $SearchMax;
    var $SearchMaxRc;
    var $SearchTime;
    var $AddSql;
    var $RsFields;
    /**
     *  php5构造函数
     *
     * @access    public
     * @param     int  $typeid  栏目id
     * @param     string  $keyword  关键词
     * @param     string  $orderby  排序
     * @param     string  $achanneltype  栏目类型
     * @param     string  $searchtype  搜索类型
     * @param     string  $starttime  开始时间
     * @param     string  $upagesize  页数
     * @param     string  $kwtype  关键词类型
     * @param     string  $mid  会员id
     * @return    string
     */
    function __construct(
        $typeid,
        $keyword,
        $orderby,
        $achanneltype = "all",
        $searchtype = '',
        $starttime = 0,
        $upagesize = 20,
        $kwtype = 1,
        $mid = 0
    ) {
        global $cfg_search_max, $cfg_search_maxrc, $cfg_search_time,$envs;
        if (empty($upagesize)) {
            $upagesize = 10;
        }
        $this->TypeID = $typeid;
        $this->Keyword = $keyword;
        $this->OrderBy = $orderby;
        $this->KType = $kwtype;
        $this->pagesize = (int)$upagesize;
        $this->StartTime = $starttime;
        $this->ChannelType = $achanneltype;
        $this->SearchMax = $cfg_search_max;
        $this->SearchMaxRc = $cfg_search_maxrc;
        $this->SearchTime = $cfg_search_time;
        $this->mid = $mid;
        $this->RsFields = '';
        $this->SearchType = $searchtype == '' ? 'titlekeyword' : $searchtype;
        $this->dsql = $GLOBALS['dsql'];
        $this->dtp = new DedeTagParse();
        $this->dtp->SetRefObj($this);
        $this->dtp->SetNameSpace("dede", "{", "}");
        $this->dtp2 = new DedeTagParse();
        $this->dtp2->SetNameSpace("field", "[", "]");
        $this->TypeLink = new TypeLink($typeid);
        //通过分词获取关键词
        $this->SearchKeyword = $keyword;
        $this->Keywords = $this->GetKeywords($keyword);
        //设置一些全局参数的值
        if ($this->TypeID == "0") {
            $this->ChannelTypeid = 1;
        } else {
            $row = $this->dsql->GetOne("SELECT channeltype FROM `#@__arctype` WHERE id={$this->TypeID}");
            $this->ChannelTypeid = $row['channeltype'];
        }
        foreach ($GLOBALS['PubFields'] as $k => $v) {
            $this->Fields[$k] = $v;
        }
        $this->CountRecord();
        $tempfile = $GLOBALS['cfg_basedir'].$GLOBALS['cfg_templets_dir']."/".$GLOBALS['cfg_df_style']."/search.htm";
        if (!file_exists($tempfile) || !is_file($tempfile)) {
            echo "搜索模板文件不存在，无法更新搜索";
            exit();
        }
        $this->dtp->LoadTemplate($tempfile);
        $this->TempInfos['tags'] = $this->dtp->CTags;
        $this->TempInfos['source'] = $this->dtp->SourceString;
        if ($this->pagesize == "") {
            $this->pagesize = 30;
        }
        $this->TotalPage = ceil($this->TotalResult / $this->pagesize);
        if ($this->PageNo == 1) {
            $this->dsql->ExecuteNoneQuery("UPDATE `#@__search_keywords` SET result='".$this->TotalResult."' WHERE keyword='".addslashes($keyword)."';");
        }
        $envs['url_type'] = 3;
        $envs['value'] = $keyword;
    }
    //php4构造函数
    function SearchView(
        $typeid,
        $keyword,
        $orderby,
        $achanneltype = "all",
        $searchtype = "",
        $starttime = 0,
        $upagesize = 20,
        $kwtype = 1,
        $mid = 0
    ) {
        $this->__construct($typeid, $keyword, $orderby, $achanneltype, $searchtype, $starttime, $upagesize, $kwtype, $mid);
    }
    //关闭相关资源
    function Close()
    {
    }
    /**
     *  获得关键词的分词结果，并保存到数据库
     *
     * @access    public
     * @param     string  $keyword  关键词
     * @return    string
     */
    function GetKeywords($keyword)
    {
        global $cfg_soft_lang, $cfg_bizcore_appid, $cfg_bizcore_key;
        $keyword = cn_substr($keyword, 50);
        $row = $this->dsql->GetOne("SELECT spwords FROM `#@__search_keywords` WHERE keyword='".addslashes($keyword)."';");
        if (!is_array($row)) {
            if (strlen($keyword) > 7) {
                if (!empty($cfg_bizcore_appid) && !empty($cfg_bizcore_key)) {
                    $client = new DedeBizClient();
                    $data = $client->Spliteword($keyword);
                    $kvs = explode(",", $data->data);
                    $keywords = $keyword." ";
                    foreach ($kvs as $key => $value) {
                        $keywords .= ' '.$value;
                    }
                    $keywords = preg_replace("/[ ]{1,}/", " ", $keywords);
                    $client->Close();
                } else {
                    $sp = new SplitWord();
                    $sp->SetSource($keyword);
                    $sp->SetResultType(2);
                    $sp->StartAnalysis(TRUE);
                    $keywords = $sp->GetFinallyResult();
                    $idx_keywords = $sp->GetFinallyIndex();
                    ksort($idx_keywords);
                    $keywords = $keyword.' ';
                    foreach ($idx_keywords as $key => $value) {
                        if (strlen($key) < 6) {
                            continue;
                        }
                        $keywords .= ' '.$key;
                    }
                    $keywords = preg_replace("/[ ]{1,}/", " ", $keywords);
                    unset($sp);
                }
            } else {
                $keywords = $keyword;
            }
            $inquery = "INSERT INTO `#@__search_keywords` (`keyword`,`spwords`,`count`,`result`,`lasttime`) VALUES ('".addslashes($keyword)."', '".addslashes($keywords)."', '1', '0', '".time()."'); ";
            $this->dsql->ExecuteNoneQuery($inquery);
        } else {
            $this->dsql->ExecuteNoneQuery("UPDATE `#@__search_keywords` SET count=count+1,lasttime='".time()."' WHERE keyword='".addslashes($keyword)."';");
            $keywords = $row['spwords'];
        }
        return $keywords;
    }
    /**
     *  获得关键词SQL
     *
     * @access    private
     * @return    string
     */
    function GetKeywordSql()
    {
        if ($this->Keywords == '') {
            return '1=2';
        }
        $ks = explode(' ', $this->Keywords);
        $kwsql = '';
        $kwsqls = array();
        foreach ($ks as $k) {
            $k = trim($k);
            if (strlen($k) < 1) {
                continue;
            }
            if (ord($k[0]) > 0x80 && strlen($k) < 2) {
                continue;
            }
            $k = addslashes($k);
            if ($this->ChannelType < 0 || $this->ChannelTypeid < 0) {
                $kwsqls[] = " arc.title LIKE '%$k%' ";
            } else {
                if ($this->SearchType == "title") {
                    $kwsqls[] = " arc.title LIKE '%$k%' ";
                } else {
                    $kwsqls[] = " CONCAT(arc.title,' ',arc.writer,' ',arc.keywords) LIKE '%$k%' ";
                }
            }
        }
        if (!isset($kwsqls[0])) {
            return '';
        } else {
            if ($this->KType == 1) {
                $kwsql = join(' OR ', $kwsqls);
            } else {
                $kwsql = join(' And ', $kwsqls);
            }
            return $kwsql;
        }
    }
    /**
     *  获得相关的关键词
     *
     * @access    public
     * @param     string  $num  关键词数目
     * @return    string
     */
    function GetLikeWords($num = 8)
    {
        $ks = explode(' ', $this->Keywords);
        $lsql = '';
        foreach ($ks as $k) {
            $k = trim($k);
            if (strlen($k) < 2) {
                continue;
            }
            if (ord($k[0]) > 0x80 && strlen($k) < 2) {
                continue;
            }
            $k = addslashes($k);
            if ($lsql == '') {
                $lsql = $lsql." CONCAT(spwords,' ') LIKE '%$k %' ";
            } else {
                $lsql = $lsql." OR CONCAT(spwords,' ') LIKE '%$k %' ";
            }
        }
        if ($lsql == '') {
            return '';
        } else {
            $likeword = '';
            $lsql = "(".$lsql.") AND NOT(keyword like '".addslashes($this->Keyword)."') ";
            $this->dsql->SetQuery("SELECT keyword,count FROM `#@__search_keywords` WHERE $lsql ORDER BY lasttime DESC LIMIT 0,$num;");
            $this->dsql->Execute('l');
            while ($row = $this->dsql->GetArray('l')) {
                if ($row['count'] > 1000) {
                    $fstyle = " style='color:red'";
                } else if ($row['count'] > 300) {
                    $fstyle = " style='color:green'";
                } else {
                    $style = '';
                }
                $likeword .= "<a href='search.php?keyword=".urlencode($row['keyword'])."&searchtype=titlekeyword'".$style.">".$row['keyword']."</a> ";
            }
            return $likeword;
        }
    }
    /**
     *  关键词加粗标红
     *
     * @access    private
     * @param     string  $fstr  关键词字符
     * @return    string
     */
    function GetRedKeyWord($fstr)
    {
        $k = trim($this->SearchKeyword);
        return ($k == '')?  $fstr : str_replace($k, "<strong style='color:red'>$k</strong>", $fstr);
    }
    /**
     *  统计列表里的记录
     *
     * @access    public
     * @return    void
     */
    function CountRecord()
    {
        $this->TotalResult = -1;
        if (isset($GLOBALS['TotalResult'])) {
            $this->TotalResult = $GLOBALS['TotalResult'];
            $this->TotalResult = is_numeric($this->TotalResult) ? $this->TotalResult : "";
        }
        if (isset($GLOBALS['PageNo'])) {
            $this->PageNo = intval($GLOBALS['PageNo']);
        } else {
            $this->PageNo = 1;
        }
        $ksql = $this->GetKeywordSql();
        $ksqls = array();
        if ($this->StartTime > 0) {
            $ksqls[] = " arc.senddate>'".$this->StartTime."' ";
        }
        if ($this->TypeID > 0) {
            $ksqls[] = " typeid IN (".GetSonIds($this->TypeID).") ";
        }
        if ($this->ChannelType > 0) {
            $ksqls[] = " arc.channel='".$this->ChannelType."'";
        }
        if ($this->mid > 0) {
            $ksqls[] = " arc.mid = '".$this->mid."'";
        }
        $ksqls[] = " arc.arcrank > -1 ";
        $this->AddSql = ($ksql == '' ? join(' AND ', $ksqls) : join(' AND ', $ksqls)." AND ($ksql)");
        if ($this->ChannelType < 0 || $this->ChannelTypeid < 0) {
            if ($this->ChannelType == "0") $id = $this->ChannelTypeid;
            else $id = $this->ChannelType;
            $row = $this->dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id=$id");
            $addtable = trim($row['addtable']);
            $this->AddTable = $addtable;
        } else {
            $this->AddTable = "#@__archives";
        }
        $cquery = "SELECT * FROM `{$this->AddTable}` arc WHERE ".$this->AddSql;
        //var_dump($cquery);
        $hascode = md5($cquery);
        $row = $this->dsql->GetOne("SELECT * FROM `#@__arccache` WHERE `md5hash`='".$hascode."' ");
        $uptime = time();
        if (is_array($row) && time() - $row['uptime'] < 3600 * 24) {
            $aids = explode(',', $row['cachedata']);
            $this->TotalResult = count($aids) - 1;
            $this->RsFields = $row['cachedata'];
        } else {
            if ($this->TotalResult == -1) {
                $this->dsql->SetQuery($cquery);
                $this->dsql->Execute();
                $aidarr = array();
                $aidarr[] = 0;
                while ($row = $this->dsql->GetArray()) {
                    if ($this->ChannelType < 0 || $this->ChannelTypeid < 0) $aidarr[] = $row['aid'];
                    else $aidarr[] = $row['id'];
                }
                $nums = count($aidarr) - 1;
                $aids = implode(',', $aidarr);
                $delete = "DELETE FROM `#@__arccache` WHERE uptime<".(time() - 3600 * 24);
                $this->dsql->SetQuery($delete);
                $this->dsql->ExecuteNoneQuery();
                $insert = "INSERT INTO `#@__arccache` (`md5hash`,`uptime`,`cachedata`) VALUES ('$hascode','$uptime','$aids')";
                $this->dsql->SetQuery($insert);
                $this->dsql->ExecuteNoneQuery();
                $this->TotalResult = $nums;
            }
        }
    }
    /**
     *  显示列表
     *
     * @access    public
     * @param     string
     * @return    string
     */
    function Display()
    {
        foreach ($this->dtp->CTags as $tagid => $ctag) {
            $tagname = $ctag->GetName();
            if ($tagname == "list") {
                $limitstart = ($this->PageNo - 1) * $this->pagesize;
                $row = $this->pagesize;
                if (trim($ctag->GetInnerText()) == "") {
                    $InnerText = GetSysTemplets("list_fulllist.htm");
                } else {
                    $InnerText = trim($ctag->GetInnerText());
                }
                $this->dtp->Assign(
                    $tagid,
                    $this->GetArcList(
                        $limitstart,
                        $row,
                        $ctag->GetAtt("col"),
                        $ctag->GetAtt("titlelen"),
                        $ctag->GetAtt("infolen"),
                        $ctag->GetAtt("imgwidth"),
                        $ctag->GetAtt("imgheight"),
                        $this->ChannelType,
                        $this->OrderBy,
                        $InnerText,
                        $ctag->GetAtt("tablewidth")
                    )
                );
            } else if ($tagname == "pagelist") {
                $list_len = trim($ctag->GetAtt("listsize"));
                $ctag->GetAtt("listitem") == "" ? $listitem = "index,pre,pageno,next,end,option" : $listitem = $ctag->GetAtt("listitem");
                if ($list_len == "") {
                    $list_len = 3;
                }
                $this->dtp->Assign($tagid, $this->GetPageListDM($list_len, $listitem));
            } else if ($tagname == "hotwords") {
                $this->dtp->Assign($tagid, lib_hotwords($ctag, $this));
            } else if ($tagname == "channelartlist") {
                $this->dtp->Assign($tagid,lib_channelartlist($ctag, $this));
            } else if ($tagname == "field") {
                //类别的指定字段
                if (isset($this->Fields[$ctag->GetAtt('name')])) {
                    $this->dtp->Assign($tagid, $this->Fields[$ctag->GetAtt('name')]);
                } else {
                    $this->dtp->Assign($tagid, "");
                }
            } else if ($tagname == "channel") {
                //下级栏目列表
                if ($this->TypeID > 0) {
                    $typeid = $this->TypeID;
                    $reid = $this->TypeLink->TypeInfos['reid'];
                } else {
                    $typeid = 0;
                    $reid = 0;
                }
                $GLOBALS['envs']['typeid'] = $typeid;
                $GLOBALS['envs']['reid'] = $typeid;
                $this->dtp->Assign($tagid, lib_channel($ctag, $this));
            } else if ($tagname == "arclist") {
                $this->dtp->Assign($tagid,lib_arclist($ctag, $this));
            } else if ($tagname == "likewords") {
                $this->dtp->Assign($tagid, $this->GetLikeWords($ctag->GetAtt('num')));
            }
        }
        global $keyword, $oldkeyword;
        if (!empty($oldkeyword)) $keyword = $oldkeyword;
        $this->dtp->Display();
    }
    /**
     *  获得文档列表
     *
     * @access    public
     * @param     int  $limitstart  限制开始  
     * @param     int  $row  行数 
     * @param     int  $col  列数
     * @param     int  $titlelen  标题长度
     * @param     int  $infolen  描述长度
     * @param     int  $imgwidth  图片宽度
     * @param     int  $imgheight  图片高度
     * @param     string  $achanneltype  列表类型
     * @param     string  $orderby  排列顺序
     * @param     string  $innertext  底层模板
     * @param     string  $tablewidth  表格宽度
     * @return    string
     */
    function GetArcList(
        $limitstart = 0,
        $row = 10,
        $col = 1,
        $titlelen = 30,
        $infolen = 250,
        $imgwidth = 120,
        $imgheight = 90,
        $achanneltype = "all",
        $orderby = "default",
        $innertext = "",
        $tablewidth = "100"
    ) {
        $typeid = $this->TypeID;
        if ($row == '') $row = 10;
        if ($limitstart == '') $limitstart = 0;
        if ($titlelen == '') $titlelen = 30;
        if ($infolen == '') $infolen = 250;
        if ($imgwidth == '') $imgwidth = 120;
        if ($imgheight = '') $imgheight = 120;
        if ($achanneltype == '') $achanneltype = '0';
        $orderby = $orderby == '' ? 'default' : strtolower($orderby);
        $tablewidth = str_replace("%", "", $tablewidth);
        if ($tablewidth == '') $tablewidth = 100;
        if ($col == '') $col = 1;
        $colWidth = ceil(100 / $col);
        $tablewidth = $tablewidth."%";
        $colWidth = $colWidth."%";
        $innertext = trim($innertext);
        if ($innertext == '') {
            $innertext = GetSysTemplets("list_fulllist.htm");
        }
        //排序方式
        $ordersql = '';
        if ($this->ChannelType < 0 || $this->ChannelTypeid < 0) {
            if ($orderby == "id") {
                $ordersql = "ORDER BY arc.aid DESC";
            } else {
                $ordersql = "ORDER BY arc.senddate DESC";
            }
        } else {
            if ($orderby == "senddate") {
                $ordersql = " ORDER BY arc.senddate DESC";
            } else if ($orderby == "pubdate") {
                $ordersql = " ORDER BY arc.pubdate DESC";
            } else if ($orderby == "id") {
                $ordersql = " ORDER BY arc.id DESC";
            } else {
                $ordersql = " ORDER BY arc.sortrank DESC";
            }
        }
        //搜索
        $query = "SELECT arc.*,act.typedir,act.typename,act.isdefault,act.defaultname,act.namerule,act.namerule2,act.ispart,act.moresite,act.siteurl,act.sitepath,mb.uname,mb.face,mb.userid FROM `{$this->AddTable}` arc LEFT JOIN `#@__arctype` act ON arc.typeid=act.id LEFT JOIN `#@__member` mb on arc.mid = mb.mid WHERE {$this->AddSql} $ordersql LIMIT $limitstart,$row";
        $this->dsql->SetQuery($query);
        $this->dsql->Execute("al");
        $artlist = '';
        if ($col > 1) {
            $artlist = "<table width='$tablewidth'>";
        }
        $this->dtp2->LoadSource($innertext);
        for ($i = 0; $i < $row; $i++) {
            if ($col > 1) {
                $artlist .= "<tr>";
            }
            for ($j = 0; $j < $col; $j++) {
                if ($col > 1) {
                    $artlist .= "<td width='$colWidth'>";
                }
                if ($row = $this->dsql->GetArray("al")) {
                    if ($this->ChannelType < 0 || $this->ChannelTypeid < 0) {
                        $row["id"] = $row["aid"];
                        $row["ismake"] = empty($row["ismake"]) ? "" : $row["ismake"];
                        $row["filename"] = empty($row["filename"]) ? "" : $row["filename"];
                        $row["money"] = empty($row["money"]) ? "" : $row["money"];
                        $row["description"] = empty($row["description "]) ? "" : $row["description"];
                        $row["pubdate"] = empty($row["pubdate  "]) ? $row["senddate"] : $row["pubdate"];
                    }
                    //处理一些特殊字段
                    $row["arcurl"] = GetFileUrl(
                        $row["id"],
                        $row["typeid"],
                        $row["senddate"],
                        $row["title"],
                        $row["ismake"],
                        $row["arcrank"],
                        $row["namerule"],
                        $row["typedir"],
                        $row["money"],
                        $row['filename'],
                        $row["moresite"],
                        $row["siteurl"],
                        $row["sitepath"]
                    );
                    $row["description"] = $this->GetRedKeyWord(cn_substr($row["description"], $infolen));
                    $row["title"] = $this->GetRedKeyWord(cn_substr($row["title"], $titlelen));
                    $row["id"] =  $row["id"];
                    if ($row['litpic'] == '-' || $row['litpic'] == '') {
                        $row['litpic'] = $GLOBALS['cfg_cmspath'].'/static/web/img/thumbnail.jpg';
                    }
                    if (!preg_match("#^(http|https):\/\/#i", $row['litpic']) && $GLOBALS['cfg_multi_site'] == 'Y') {
                        $row['litpic'] = $GLOBALS['cfg_mainsite'].$row['litpic'];
                    }
                    $row['picname'] = $row['litpic'];
                    $row["typeurl"] = GetTypeUrl($row["typeid"], $row["typedir"], $row["isdefault"], $row["defaultname"], $row["ispart"], $row["namerule2"], $row["moresite"], $row["siteurl"], $row["sitepath"]);
                    $row["info"] = $row["description"];
                    $row["filename"] = $row["arcurl"];
                    $row["stime"] = GetDateMK($row["pubdate"]);
                    $row["textlink"] = "<a href='".$row["filename"]."'>".$row["title"]."</a>";
                    $row["typelink"] = "[<a href='".$row["typeurl"]."'>".$row["typename"]."</a>]";
                    $row["imglink"] = "<a href='".$row["filename"]."'><img src='".$row["picname"]."' width='$imgwidth' height='$imgheight'></a>";
                    $row["image"] = "<img src='".$row["picname"]."' width='$imgwidth' height='$imgheight'>";
                    $row['plusurl'] = $row['phpurl'] = $GLOBALS['cfg_phpurl'];
                    $row['memberurl'] = $GLOBALS['cfg_memberurl'];
                    $row['templeturl'] = $GLOBALS['cfg_templeturl'];
                    $row['face'] = empty($row['face'])? $GLOBALS['cfg_mainsite'].'/static/web/img/admin.png' : $row['face'];
                    $row['userurl'] = $GLOBALS['cfg_memberurl'].'/index.php?uid='.$row['userid'];
                    if (is_array($this->dtp2->CTags)) {
                        foreach ($this->dtp2->CTags as $k => $ctag) {
                            if ($ctag->GetName() == 'array') {
                                //传递整个数组，在runphp模式中有特殊作用
                                $this->dtp2->Assign($k, $row);
                            } else {
                                if (isset($row[$ctag->GetName()])) {
                                    $this->dtp2->Assign($k, $row[$ctag->GetName()]);
                                } else {
                                    $this->dtp2->Assign($k, '');
                                }
                            }
                        }
                    }
                    $artlist .= $this->dtp2->GetResult();
                }  else {
                    $artlist .= '';
                }
                if ($col > 1) $artlist .= "</td>";
            }
            if ($col > 1) {
                $artlist .= "</tr>";
            }
        }
        if ($col > 1) {
            $artlist .= "</table>";
        }
        $this->dsql->FreeResult("al");

        return $artlist;
    }
    /**
     *  获取动态的分页列表
     *
     * @access    public
     * @param     string  $list_len  列表宽度
     * @return    string
     */
    function GetPageListDM($list_len, $listitem = "index,end,pre,next,pageno")
    {
        global $oldkeyword;
        $prepage = '';
        $nextpage = '';
        $prepagenum = $this->PageNo - 1;
        $nextpagenum = $this->PageNo + 1;
        if ($list_len == "" || preg_match("/[^0-9]/", $list_len)) {
            $list_len = 3;
        }
        $totalpage = ceil($this->TotalResult / $this->pagesize);
        if ($totalpage <= 1 && $this->TotalResult > 0) {
            return "<li class='page-item disabled'><span class='page-link'>1页{$this->TotalResult}条</span></li>";
        }
        if ($this->TotalResult == 0) {
            return "<li class='page-item disabled'><span class='page-link'>0页{$this->TotalResult}条</span></li>";
        }
        $purl = $this->GetCurUrl();
        $oldkeyword = (empty($oldkeyword) ? $this->Keyword : $oldkeyword);
        //当结果超过限制时，重设结果页数
        if ($this->TotalResult > $this->SearchMaxRc) {
            $totalpage = ceil($this->SearchMaxRc / $this->pagesize);
        }
        $infos = "<li class='page-item disabled'><span class='page-link'>{$totalpage}页{$this->TotalResult}条</span></li>";
        $geturl = "";
        //$geturl = "keyword=".urlencode($oldkeyword)."&searchtype=".$this->SearchType;
        //$geturl .= "&channeltype=".$this->ChannelType."&orderby=".$this->OrderBy;
        //$geturl .= "&kwtype=".$this->KType."&pagesize=".$this->pagesize;
        $geturl .= "typeid=".$this->TypeID."&keyword=".urlencode($oldkeyword)."&";
        $purl .= "?".$geturl;
        //获得上页和下页的链接
        if ($this->PageNo != 1) {
            $prepage .= "<li class='page-item'><a href='{$purl}PageNo={$prepagenum}' class='page-link'>上页</a></li>";
            $indexpage = "<li class='page-item'><a href='{$purl}PageNo=1' class='page-link'>首页</a></li>";
        } else {
            $indexpage = "<li class='page-item'><a class='page-link'>首页</a></li>";
        }
        if ($this->PageNo != $totalpage && $totalpage > 1) {
            $nextpage .= "<li class='page-item'><a href='{$purl}PageNo={$nextpagenum}' class='page-link'>下页</a></li>";
            $endpage = "<li class='page-item'><a href='{$purl}PageNo={$totalpage}' class='page-link'>末页</a></li>";
        } else {
            $endpage = "<li class='page-item'><a class='page-link'>末页</a></li>";
        }
        //获得数字链接
        $listdd = '';
        $total_list = $list_len * 2 + 1;
        if ($this->PageNo >= $total_list) {
            $j = $this->PageNo - $list_len;
            $total_list = $this->PageNo + $list_len;
            if ($total_list > $totalpage) {
                $total_list = $totalpage;
            }
        } else {
            $j = 1;
            if ($total_list > $totalpage) {
                $total_list = $totalpage;
            }
        }
        for ($j; $j <= $total_list; $j++) {
            if ($j == $this->PageNo) {
                $listdd .= "<li class='page-item active'><span class='page-link'>{$j}</span></li>";
            } else {
                $listdd .= "<li class='page-item'><a href='{$purl}PageNo={$j}' class='page-link'>{$j}</a></li>";
            }
        }
		$plist = '';
        $plist .= preg_match('/info/i', $listitem)? $infos : "";
        $plist .= preg_match('/index/i', $listitem)? $indexpage : "";
        $plist .= preg_match('/pre/i', $listitem)? $prepage : "";
        $plist .= preg_match('/pageno/i', $listitem)? $listdd : "";
        $plist .= preg_match('/next/i', $listitem)? $nextpage : "";
        $plist .= preg_match('/end/i', $listitem)? $endpage : "";
        return $plist;
    }
    /**
     *  获得当前的页面文件链接
     *
     * @access    public
     * @return    string
     */
    function GetCurUrl()
    {
        if (!empty($_SERVER["REQUEST_URI"])) {
            $nowurl = $_SERVER["REQUEST_URI"];
            $nowurls = explode("?", $nowurl);
            $nowurl = $nowurls[0];
        } else {
            $nowurl = $_SERVER["PHP_SELF"];
        }
        return $nowurl;
    }
}
?>