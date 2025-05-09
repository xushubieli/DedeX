<?php
/**
 * @version        $id:index.php 2010-06-30 11:43:09 tianya $
 * @package        DedeBIZ.Site
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__)."/../../system/common.inc.php");
require_once(DEDEINC."/archive/specview.class.php");
if (strlen($art_shortname) > 6) exit("art_shortname too long!");
$specfile = dirname(__FILE__)."spec_1".$art_shortname;
//如果已经编译静态列表，则直接引入第一个文件
if (file_exists($specfile)) {
  include($specfile);
  exit();
} else {
  $sp = new SpecView();
  $sp->Display();
}
?>