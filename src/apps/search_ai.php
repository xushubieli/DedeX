<?php
/**
 * 搜索千问AI
 * 大模型服务平台百炼控制台：https://bailian.console.aliyun.com
 *
 * @version        $id:search_ai.php$
 * @package        DedeX.Site
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/../system/common.inc.php");
header('Content-Type: text/html; charset=utf-8');
$ty_key = isset($cfg_ty_api) ? $cfg_ty_api : '';
$ty_model = 'qwen-max';
//获取关键词
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
//过滤网页实体和标签
$keyword = htmlspecialchars_decode($keyword, ENT_QUOTES);
$keyword = strip_tags($keyword);
$keyword = preg_replace('/[\'"<>\r\n\t]+/', '', $keyword);
//验证关键词
if (empty($keyword) || strlen($keyword) < 2 || strlen($keyword) > 255) {
    echo '<p>请输入有效关键词</p>';
    exit;
}
//检查API密钥
if (empty($ty_key) || strlen($ty_key) < 30) {
    echo '<p>请配置有效API</p>';
    exit;
}
//构建请求
$url = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer '.$ty_key,
];
//提示词
$prompt = "请以搜索引擎结果的形式，回答关于“{$keyword}”的问题。如果是事物或概念，先说明它是什么，再列出关键信息。如果是问题，直接给出答案要点，每条信息单独一行，语言简洁准确。";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //验证书true为启用，false为不启用
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //验主机名2为启用，false为不启用
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => $ty_model,
    'input' => [
        'messages' => [
            ['role' => 'system', 'content' => '你是一个搜索引擎问答系统，擅长用简洁、准确信息回答问题。'],
            ['role' => 'user', 'content' => $prompt],
        ],
    ],
    'parameters' => [
        'result_format' => 'message',
        'max_tokens' => 300,
        'temperature' => 0.1,
        'top_p' => 0.8,
        'enable_search' => true,
    ]
], JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
//判断响应
if ($http_code !== 200) {
    echo '<p>回答响应超时，请稍后再试。</p>';
    exit;
}
$result = json_decode($response, true);
if (isset($result['output']['choices'][0]['message']['content']) && !empty(trim($result['output']['choices'][0]['message']['content']))) {
    $content = $result['output']['choices'][0]['message']['content'];
    $lines = explode("\n", $content);
    $content = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $content .= "<p>".htmlspecialchars($line)."</p>\n";
        }
    }
    $content = str_replace('——', '', $content);
    $content = str_replace('—', '-', $content);
    $content = str_replace('–', '-', $content);
    $content = str_replace('---', '-', $content);
    $content = str_replace('--', '-', $content);
    $content = str_replace('<p>-', '<p>', $content);
    $content = preg_replace('/[*#]+/', '', $content);
    echo trim($content);
} else {
    echo '<p>暂时无法回答这个问题，请换个说法试试。</p>';
}
?>