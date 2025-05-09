<?php
if (!defined('DEDEINC')) exit('dedebiz');
/**
 * 任意表调用标签
 *
 * @version        $id:loop.lib.php 9:29 2010年7月6日 tianya $
 * @package        DedeBIZ.Taglib
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
function lib_loop(&$ctag, &$refObj)
{
    global $dsql;
    $attlist = "table|,tablename|,row|10,sort|,if|,ifcase|,orderway|desc";
    FillAttsDefault($ctag->CAttribute->Items, $attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    $innertext = trim($ctag->GetInnertext());
    $revalue = '';
    if (!empty($table)) $tablename = $table;
    if ($tablename == '' || $innertext == '') return '';
    if ($if != '') $ifcase = $if;
    if ($sort != '') $sort = " ORDER BY $sort $orderway ";
    if ($ifcase != '') $ifcase = " WHERE $ifcase ";
    $dsql->SetQuery("SELECT * FROM $tablename $ifcase $sort LIMIT 0,$row");
    $dsql->Execute();
    $ctp = new DedeTagParse();
    $ctp->SetNameSpace("field", "[", "]");
    $ctp->LoadSource($innertext);
    $GLOBALS['autoindex'] = 0;
    while ($row = $dsql->GetArray()) {
        $GLOBALS['autoindex']++;
        foreach ($ctp->CTags as $tagid => $ctag) {
            if ($ctag->GetName() == 'array') {
                $ctp->Assign($tagid, $row);
            } else {
                if (!empty($row[$ctag->GetName()])) $ctp->Assign($tagid, $row[$ctag->GetName()]);
            }
        }
        $revalue .= $ctp->GetResult();
    }
    return $revalue;
}
?>