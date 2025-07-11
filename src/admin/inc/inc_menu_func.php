<?php
/**
 * 后台侧边菜单函数
 *
 * @version        $id:inc_menu_func.php 10:32 2010年7月21日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/../config.php");
require_once(DEDEINC."/dedetag.class.php");
$headTemplet = '<div class="menu-item"><a class="menu-link"><i class="~icon~"></i>~channelname~</a><i class="fa fa-angle-down"></i></div><ul class="menu-sub">';
$footTemplet = '</ul>';
$itemTemplet = '<li class="sub-item">~link~</li>';
function GetMenus($userrank, $topos = 'main')
{
    global $showitem, $headTemplet, $footTemplet, $itemTemplet;
    if ($topos == 'main') {
        $showitem = (empty($showitem) ? 1 : $showitem);
        $menus = $GLOBALS['menusMain'];
    } else if ($topos == 'module') {
        $showitem = 100;
        $menus = $GLOBALS['menusMoudle'];
    }
    $dtp = new DedeTagParse();
    $dtp->SetNameSpace('m', '<', '>');
    $dtp->LoadSource($menus);
    $dtp2 = new DedeTagParse();
    $dtp2->SetNameSpace('m', '<', '>');
    foreach ($dtp->CTags as $i => $ctag) {
        if ($ctag->GetName() == 'top' && ($ctag->GetAtt('rank') == '' || TestPurview($ctag->GetAtt('rank')))) {
            if ($showitem != 999 && !preg_match("#".$showitem.'_'."#", $ctag->GetAtt('item')) && $showitem != 100) continue;
            $htmp = str_replace("~channelname~", $ctag->GetAtt("name"), $headTemplet);
            $icon = 'fa fa-plug';
            if ($ctag->GetAtt('icon') != '') {
                $icon = $ctag->GetAtt('icon');
            }
            $htmp = str_replace('~icon~', $icon, $htmp);
            echo $htmp;
            $dtp2->LoadSource($ctag->InnerText);
            foreach ($dtp2->CTags as $j => $ctag2) {
                $ischannel = trim($ctag2->GetAtt('ischannel'));
                if ($ctag2->GetName() == 'item' && ($ctag2->GetAtt('rank') == '' || TestPurview($ctag2->GetAtt('rank')))) {
                    $link = "<a href='".$ctag2->GetAtt('link')."' target='".$ctag2->GetAtt('target')."'>".$ctag2->GetAtt('name')."</a>";
                    if ($ischannel == '1') {
                        if ($ctag2->GetAtt('addico') != '') {
                            $addico = $ctag2->GetAtt('addico');
                        } else {
                            $addico = 'fa fa-plus-circle';
                        }
                        $link = "$link<a href='".$ctag2->GetAtt('linkadd')."' class='submenu-right' target='".$ctag2->GetAtt('target')."'><span class='$addico'></span></a>";
                    } else {
                        $link .= '';
                    }
                    $itemtmp = str_replace('~link~', $link, $itemTemplet);
                    echo $itemtmp;
                }
            }
            echo $footTemplet;
        }
    }
}
?>