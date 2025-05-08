<?php
/**
 * 小德AI助手对话框
 *
 * @version        $id:ai_dialog.php 2025 tianya $
 * @package        DedeBIZ.Dialog
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__)."/config.php");
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
        <title>小德AI助手对话框</title>
        <link rel="stylesheet" href="/static/web/css/font-awesome.min.css">
        <link rel="stylesheet" href="/static/web/css/bootstrap.min.css">
        <link rel="stylesheet" href="/static/web/css/admin.css">
        <script src="/static/web/js/jquery.min.js"></script>
        <script src="/static/web/js/bootstrap.min.js"></script>
        <script src="/static/web/js/admin.main.js"></script>
    </head>
    <body class="p-3">
        <div class="card shadow-sm">
            <div class="card-header">小德AI助手：智能处理</div>
            <div class="card-body">
                <div class="form-group">
                    <div class="alert alert-warning mb-0">处理过程中请勿关闭小德AI助手对话框</div>
                </div>
                <div class="form-group">
                    <textarea id="prompt" class="form-control" style="height:160px" placeholder="请输入内容处理要求，例：我需要将内容润色下，希望更专业"></textarea>
                </div>
                <div class="form-group">
                    <label for="modelid" class="form-label">选择模型</label>
                    <select id="modelid" class="form-control">
                        <?php
                        $dsql->SetQuery("SELECT AM.*,A.title as aititle FROM `#@__ai_model` AM LEFT JOIN `#@__ai` A ON A.id = AM.aiid ORDER BY AM.sortrank ASC,AM.id DESC");
                        $dsql->Execute();
                        while ($row = $dsql->GetObject()) {
                        ?>
                            <option value="<?php echo $row->id; ?>" <?php echo $row->isdefault == 1 ? ' selected' : ''; ?>><?php echo $row->model; ?> <?php echo $row->aititle; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
                <button type="button" id="btnAIAction" class="btn btn-success btn-sm">确定</button>
            </div>
        </div>
        <script>
            $("#btnAIAction").click(async function() {
                let body = window.opener.CKEDITOR.instances["<?php echo $f ?>"].getData();
                let prompt = document.getElementById("prompt").value;
                let modelid = document.getElementById("modelid").value;
                let req = await fetch(`api.php?action=get_ai_server&pname=body_edit&modelid=${modelid}&prompt=${prompt}`);
                let resp = await req.json();
                if (resp.code !== 0) {
                    ShowMsg("获取服务器地址失败");
                    return
                }
                let req2 = await fetch(`api.php?action=get_setbody_url`);
                let resp2 = await req2.json();
                if (resp2.code !== 0) {
                    ShowMsg("获取服务器地址失败");
                    return
                }
                let req3 = await fetch(resp2.data, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        body: body
                    })
                });
                let resp3 = await req3.json();
                if (resp3.code !== 0) {
                    ShowMsg("提交原始内容失败");
                    return
                }
                let eventSource = new EventSource(resp.data);
                //新增状态跟踪变量
                let currentKey = null;
                let tagBuffer = "";
                let isClosingTag = false;
                $("#mdlAI").modal('hide');
                window.opener.CKEDITOR.instances["<?php echo $f ?>"].getCommand('openDedeBIZAi').disable();
                $("#btnAIAction").attr("disabled", "disabled");
                $("#prompt").attr("disabled", "disabled");
                $("#modelid").attr("disabled", "disabled");
                prompt = "";
                let bodyHtml = "";
                let lastChar = "";
                eventSource.onmessage = (event) => {
                    const chars = event.data.split('');
                    chars.forEach(char => {
                        if (lastChar === '\\' && char === 'r') {
                            char = '<br>'; //替换为br标签
                            lastChar = ""; //清空追踪字符
                        } else {
                            lastChar = char; //记录当前字符
                        }
                        if (char === '\\') {
                            return; //如果是反斜杠，跳过处理
                        }
                        if (currentKey) {
                            if (char === '{') {
                                isClosingTag = true;
                                tagBuffer = '{';
                                return;
                            }
                            if (isClosingTag) {
                                tagBuffer += char;
                                if (tagBuffer === `{/${currentKey}}`) {
                                    if (currentKey == "content") {
                                        window.opener.CKEDITOR.instances["<?php echo $f ?>"].setReadOnly(false);
                                        bodyHtml = "";
                                    } else {
                                        const input = document.querySelector(`[name="${currentKey}"]`);
                                        if (input) $(input).prop("disabled", false).removeClass("disabled"); //恢复输入状态
                                    }
                                    currentKey = null;
                                    isClosingTag = false;
                                    tagBuffer = "";
                                    return;
                                }
                                if (!`{/${currentKey}}`.startsWith(tagBuffer)) {
                                    if (currentKey == "content") {
                                        bodyHtml += tagBuffer;
                                        console.log(bodyHtml);
                                        window.opener.CKEDITOR.instances["<?php echo $f ?>"].setData(bodyHtml)
                                    } else {
                                        const input = document.querySelector(`[name="${currentKey}"]`);
                                        if (input) input.value += tagBuffer;
                                    }
                                    isClosingTag = false;
                                    tagBuffer = "";
                                }
                            } else {
                                if (currentKey == "content") {
                                    bodyHtml += char;
                                    window.opener.CKEDITOR.instances["<?php echo $f ?>"].setData(bodyHtml)
                                } else {
                                    const input = document.querySelector(`[name="${currentKey}"]`);
                                    if (input) {
                                        input.value += char;
                                        input.scrollTop = input.scrollHeight; //滚动到底部
                                    }
                                }
                            }
                        } else {
                            if (char === '{') {
                                tagBuffer = '{';
                            } else if (tagBuffer.startsWith('{')) {
                                tagBuffer += char;
                                if (char === '}') {
                                    const match = tagBuffer.match(/{([^>]+)}/);
                                    if (match) {
                                        currentKey = match[1];
                                        if (currentKey == "content") {
                                            window.opener.CKEDITOR.instances["<?php echo $f ?>"].setReadOnly(true);
                                        } else {
                                            const input = document.querySelector(`[name="${currentKey}"]`);
                                            if (input) {
                                                $(input).prop("disabled", true).addClass("disabled"); //仅禁用当前输入框
                                                input.value = "";
                                            }
                                        }
                                    }
                                    tagBuffer = "";
                                }
                            }
                        }
                    });
                };
                eventSource.onerror = (error) => {
                    if (error.target.readyState === EventSource.CONNECTING) {
                        ShowMsg("连接失败，请确保您已开启并正确配置了DedeBIZ小德AI助手。 <a class='text-success' href='https://www.dedebiz.com/ai?from=dedebiz' target='_blank'>如何配置？</a>");
                    } else if (typeof error.data !== "undefined" && error.data !== "" && error.target.readyState !== EventSource.CLOSED) {
                        ShowMsg(error.data);
                    }
                    window.opener.CKEDITOR.instances["<?php echo $f ?>"].getCommand('openDedeBIZAi').enable();
                    $("#btnAI").prop("disabled", false);
                    $("#btnAIAction").prop("disabled", false);
                    $("#prompt").prop("disabled", false);
                    $("#modelid").prop("disabled", false);
                    eventSource.close();
                };
                //监听特定事件close
                eventSource.addEventListener('close', (event) => {
                    // console.log('SSE connection closed:', event.data);
                    window.opener.CKEDITOR.instances["<?php echo $f ?>"].getCommand('openDedeBIZAi').enable();
                    $("#btnAIAction").prop("disabled", false);
                    $("#prompt").prop("disabled", false);
                    $("#modelid").prop("disabled", false);
                    eventSource.close(); //关闭连接
                    window.opener.CKEDITOR.instances["<?php echo $f ?>"].setReadOnly(false);
                });
            });
        </script>
    </body>
</html>