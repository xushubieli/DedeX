<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
/**
 * 防采集字符串助手
 *
 * @version        $Id: downmix.helper.php 1 9:14 2010年7月6日Z tianya $
 * @package        DedeX.Helpers
 * @license        GNU GPL v2 (/license.txt)
 */
/**
 *  防采集字符串内容
 *
 * @access    public
 * @param     string  $body  内容
 * @return    string
 */
if (!function_exists('RndString')) {
    function RndString(&$body)
    {
        //加入混淆字串次数
        $max = mt_rand(1, 2);
        $cnt = 0;
        //最大间隔距离，检测不到p标记的情况下最大间隔距离
        $maxpos = 2048;
        $fontColor = "transparent";
        //div、span、p随机样式
        $st1 = chr(mt_rand(ord('A'),ord('Z'))).chr(mt_rand(ord('a'),ord('z'))).chr(mt_rand(ord('a'),ord('z'))).mt_rand(100, 999);
        $st2 = chr(mt_rand(ord('A'),ord('Z'))).chr(mt_rand(ord('a'),ord('z'))).chr(mt_rand(ord('a'),ord('z'))).mt_rand(100, 999);
        $st3 = chr(mt_rand(ord('A'),ord('Z'))).chr(mt_rand(ord('a'),ord('z'))).chr(mt_rand(ord('a'),ord('z'))).mt_rand(100, 999);
        $st4 = chr(mt_rand(ord('A'),ord('Z'))).chr(mt_rand(ord('a'),ord('z'))).chr(mt_rand(ord('a'),ord('z'))).mt_rand(100, 999);
        $rndstyle[1]['value'] = ".{$st1} {display:none;}";
        $rndstyle[1]['name'] = $st1;
        $rndstyle[2]['value'] = ".{$st2} {display:none;}";
        $rndstyle[2]['name'] = $st2;
        $rndstyle[3]['value'] = ".{$st3} {display:none;}";
        $rndstyle[3]['name'] = $st3;
        $rndstyle[4]['value'] = ".{$st4} {display:none;}";
        $rndstyle[4]['name'] = $st4;
        $mdd = mt_rand(1, 4);
        $rndstyleValue = $rndstyle[$mdd]['value'];
        $rndstyleName = $rndstyle[$mdd]['name'];
        $reString = "<style>{$rndstyleValue}</style>";
        //附机标记
        $rndem[1] = 'font';
        $rndem[2] = 'div';
        $rndem[3] = 'span';
        $rndem[4] = 'p';
        //读取字符串数据
        $fp = fopen(DEDEDATA.'/downmix.data.php', 'r');
        $start = 0;
        $totalitem = 0;
        while (!feof($fp)) {
            $v = trim(fgets($fp, 128));
            if ($start == 1) {
                if(preg_match("/#end#/i", $v)) {
                    break;
                }
                if ($v != '') {
                    $totalitem++; $rndstring[$totalitem] = preg_replace("/#,/", "", $v);
                }
            }
            if (preg_match("/#start#/i", $v)) {
                $start = 1;
            }
        }
        fclose($fp);
        //处理要防采集的字段
        $bodylen = strlen($body) - 1;
        $prepos = 0;
        for ($i=0; $i<=$bodylen; $i++) {
            if ($i+2 >= $bodylen || $i<50) {
                $reString .= $body[$i];
            } else {
                $ntag = @strtolower($body[$i].$body[$i+1].$body[$i+2]);
                if ($ntag=='</p' || ($ntag=='<br' && $i-$prepos>$maxpos)) {
                    if ($cnt >= $max) {
                        $reString .= $body[$i];
                    } else {
                        $dd = mt_rand(1, 4);
                        $emname = $rndem[$dd];
                        $dd = mt_rand(1, $totalitem);
                        $rnstr = $rndstring[$dd];
                        if ($emname != 'font') {
                            $rnstr = "<{$emname} class='{$rndstyleName}'>{$rnstr}</{$emname}>";
                        } else {
                            $rnstr = "<span style='color:{$fontColor}'>{$rnstr}</span>";
                        }
                        $reString .= $rnstr.$body[$i];
                        $cnt++;
                        $prepos = $i;
                    }
                } else {
                    $reString .= $body[$i];
                }
            }
        }
        return $reString;
    }
}
?>