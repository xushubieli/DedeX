CKEDITOR.plugins.add("dedebizai", {
    icons: "dedebizai",
    init: function (a) {
        a.addCommand("openDedeBIZAi",
            {
                exec: function (a) {
                    var w = 800;
                    var h = 600;
                    var dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
                    var dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;
                    var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
                    var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
                    var systemZoom = width / window.screen.availWidth;
                    var posLeft = (width - w) / 2 / systemZoom + dualScreenLeft;
                    var posTop = (height - h) / 2 / systemZoom + dualScreenTop;
                    window.open("./ai_dialog.php?f=" + a.name + "&noeditor=yes", "popUpImagesWin", "scrollbars=yes,resizable=yes,statebar=no,width=800,height=460,left=" + posLeft + ", top=" + posTop);
                }
            });
        a.ui.addButton("DedeBIZAi",
            {
                label: "AI助手",
                command: "openDedeBIZAi",
                toolbar: "insert"
            })
    }
});