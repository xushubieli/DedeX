<?php
if (!defined('DEDEINC')) exit('dedebiz');
/**
 * 投票标签
 *
 * @version        $id:vote.lib.php 9:29 2010年7月6日 tianya $
 * @package        DedeBIZ.Taglib
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(DEDEINC.'/dedevote.class.php');
function lib_vote(&$ctag, &$refObj)
{
    global $dsql;
    $attlist = "id|0,lineheight|30,tablewidth|100%,titlebgcolor|#dee2e6,titlebackgroup|,tablebg|#ffffff";
    FillAttsDefault($ctag->CAttribute->Items, $attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    if (empty($id)) $id = 0;
    if ($id == 0) {
        $row = $dsql->GetOne("SELECT aid FROM `#@__vote` ORDER BY aid DESC LIMIT 0,1");
        if (!isset($row['aid'])) return '';
        else $id = $row['aid'];
    }
    $vt = new DedeVote($id);
    return $vt->GetVoteForm($lineheight, $tablewidth, $titlebgcolor, $titlebackgroup, $tablebg);
}
?>