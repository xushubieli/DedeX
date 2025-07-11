function GetWinPos(w, h) {
	var dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
	var dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;
	var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
	var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
	var systemZoom = width / window.screen.availWidth;
	var left = (width - w) / 2 / systemZoom + dualScreenLeft;
	var top = (height - h) / 2 / systemZoom + dualScreenTop;
	return {
		left: left,
		top: top
	};
}
function SelectMedia(fname) {
	var pos = GetWinPos(800, 600);
	window.open("./dialog/select_media.php?f=" + fname + "&noeditor=yes", "popUpFlashWin", "scrollbars=yes,resizable=yes,statebar=no,width=800,height=600,left=" + pos.left + ", top=" + pos.top);
}
function SelectSoft(fname) {
	var pos = GetWinPos(800, 600);
	window.open("./dialog/select_soft.php?f=" + fname+ "&noeditor=yes", "popUpImagesWin", "scrollbars=yes,resizable=yes,statebar=no,width=800,height=600,left=" + pos.left + ", top=" + pos.top);
}
function SelectImage(fname, stype, imgsel="") {
	var pos = GetWinPos(800, 600);
	if (!fname) fname = "form1.picname";
	if (imgsel) imgsel = "&noeditor=yes";
	if (!stype) stype = "small";
	window.open("./dialog/select_images.php?f=" + fname + "&noeditor=yes&imgstick=" + stype + imgsel, "popUpImagesWin", "scrollbars=yes,resizable=yes,statebar=no,width=800,height=600,left=" + pos.left + ", top=" + pos.top);
}
function SelectImageN(fname, stype, vname) {
	var pos = GetWinPos(800, 600);
	if (!fname) fname = "form1.picname";
	if (!stype) stype = '';
	window.open("./dialog/select_images.php?f=" + fname + "&imgstick=" + stype + "&v=" + vname, "popUpImagesWin", "scrollbars=yes,resizable=yes,statebar=no,width=800,height=600,left=" + pos.left + ", top=" + pos.top);
}
function SelectKeywords(f) {
	var pos = GetWinPos(800, 600);
	window.open("article_keywords_select.php?f=" + f, "popUpkwWin", "scrollbars=yes,resizable=yes,statebar=no,width=800,height=600,left=" + pos.left + ", top=" + pos.top);
}
function OpenMyWin(surl) {
	var pos = GetWinPos(800, 600);
	window.open(surl, "popUpMyWin", "scrollbars=yes,resizable=yes,statebar=no,width=800,height=600,left=" + pos.left + ", top=" + pos.top);
}
function $Obj(objname) {
	return document.getElementById(objname);
}
function InitPage() {
	var selsource = $Obj("selsource");
	var selwriter = $Obj("selwriter");
	var colorbt = $Obj("color");
	if (selsource) {
		selsource.onmousedown = function(e) {
			SelectSource(e);
		}
	}
	if (selwriter) {
		selwriter.onmousedown = function(e) {
			SelectWriter(e);
		}
	}
}
function ShowObj(objname) {
	var obj = $Obj(objname);
	if (obj == null) return false;
	obj.style.display = "table-row";
}
function ShowObjRow(objname) {
	var obj = $Obj(objname);
	obj.style.display = "table-row";
}
function AddTypeid2() {
	ShowObjRow("typeid2tr");
}
function HideObj(objname) {
	var obj = $Obj(objname);
	if (obj == null) return false;
	obj.style.display = "none";
}
function PutSource(str) {
	var osource = $Obj("source");
	if (osource) osource.value = str;
	$Obj("mysource").style.display = "none";
	ChangeFullDiv("hide");
}
function PutWriter(str) {
	var owriter = $Obj("writer");
	if (owriter) owriter.value = str;
	$Obj("mywriter").style.display = "none";
	ChangeFullDiv("hide");
}
function ClearDivCt(objname) {
	if (!$Obj(objname)) return;
	$Obj(objname).innerHTML = '';
	$Obj(objname).style.display = "none";
	ChangeFullDiv("hide");
}
function ShowHide(objname) {
	var obj = $Obj(objname);
	if (obj.style.display != "none") obj.style.display = "none";
	else obj.style.display = "inline-block";
}
function SelectSource(e) {
	LoadNewDiv(e, "article_select_sw.php?t=source&k=8&rnd=" + Math.random(), "mysource");
}
function SelectWriter(e) {
	LoadNewDiv(e, "article_select_sw.php?t=writer&k=8&rnd=" + Math.random(), "mywriter");
}
function ColorSel(c, oname) {
	var tobj = $Obj(oname);
	if (!tobj) tobj = eval("document.form1." + oname);
	if (!tobj) {
		$Obj("tipslite").style.display = "none";
		return false;
	} else {
		tobj.value = c;
		$Obj("tipslite").style.display = "none";
		return true;
	}
}
function ShowColor(e, o) {
	LoadNewDiv(e, "../theme/system/colornew.htm", "tipslite");
}
function ShowUrlTr() {
	var jumpTest = $Obj("flagsj");
	var jtr = $Obj("redirecturltr");
	var jf = $Obj("redirecturl");
	if (jumpTest.checked) jtr.style.display = "table-row";
	else {
		jf.value = '';
		jtr.style.display = "none";
	}
}
function ShowUrlTrEdit() {
	ShowUrlTr();
	var jumpTest = $Obj("isjump");
	var rurl = $Obj("redirecturl");
	if (!jumpTest.checked) rurl.value = '';
}
function ChangeFullDiv(showhide, screenheigt) {
	var newobj = $Obj("adminmodalbg");
	if (showhide == "show") {
		if (!newobj) {
			newobj = document.createElement("div");
			newobj.id = "adminmodalbg";
			newobj.style.position = "fixed";
			newobj.className = "adminmodalbg";
			document.body.appendChild(newobj);
		} else {
			newobj.style.display = "block";
		}
		document.body.style.overflow = "hidden";
	} else {
		if (newobj) newobj.style.display = "none";
		document.body.style.overflow = "";
	}
}
function LoadNewDiv(e, surl, oname) {
	var pxStr = '';
	var posLeft = e.pageX - 16;
	var posTop = e.pageY - 16;
	pxStr = 'px';
	var newobj = $Obj(oname);
	if (!newobj) {
		newobj = document.createElement("div");
		newobj.id = oname;
		newobj.style.position = "absolute";
		newobj.className = oname;
		newobj.className += " dlgws";
		newobj.style.top = posTop + pxStr;
		newobj.style.left = posLeft + pxStr;
		document.body.appendChild(newobj);
	} else {
		newobj.style.display = "block";
	}
	if (newobj.innerHTML.length < 10) {
		fetch(surl).then(resp => resp.text()).then((d) => {
			newobj.innerHTML = d;
		});
	}
}
function LoadQuickDiv(e, surl, oname, w, h) {
	var newobj = $Obj(oname);
	if (!newobj) {
		newobj = document.createElement("div");
		newobj.id = oname;
		newobj.style.position = "fixed";
		newobj.className = "adminmodal";
		document.body.appendChild(newobj);
	}
	newobj.style.top = "0";
	newobj.style.left = "0";
	newobj.style.display = "block";
	fetch(surl).then(resp => resp.text()).then((d) => {
		newobj.innerHTML = d;
	});
}
function ShowCatMap(e, obj, cid, targetId, oldvalue) {
	LoadQuickDiv(e, "archives_do.php?dopost=getCatMap&targetid=" + targetId + "&channelid=" + cid + "&oldvalue=" + oldvalue + "&rnd=" + Math.random(), "getCatMap", "800px", "600px");
	ChangeFullDiv("show");
}
function getSelCat(targetId) {
	var selBox = document.fastselectbox.seltypeid;
	var targetObj = $Obj(targetId);
	var selvalue = '';
	if (targetId == 'typeid2') {
		var j = 0;
		for (var i = 0; i < selBox.length; i++) {
			if (selBox[i].checked) {
				j++;
				if (j == 10) break;
				selvalue += (selvalue == '' ? selBox[i].value : ',' + selBox[i].value);
			}
		}
		if (targetObj) targetObj.value = selvalue;
	} else {
		if (selBox) {
			for (var i = 0; i < selBox.length; i++) {
				if (selBox[i].checked) selvalue = selBox[i].value;
			}
		}
		if (selvalue == '') {
			showMsg('您没有选中任何栏目');
			return;
		}
		if (targetObj) {
			for (var j = 0; j < targetObj.length; j++) {
				op = targetObj.options[j];
				if (op.value == selvalue) op.selected = true;
			}
		}
	}
	HideObj("getCatMap");
	ChangeFullDiv("hide");
}
//全局消息提示框
function guid() {
	function S4() {
		return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
	}
	return (S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4());
}
var _DedeConfirmFuncs = {};
var _DedeConfirmFuncsClose = {};
function __DedeConfirmRun(modalID) {
	_DedeConfirmFuncs[modalID]();
}
function __DedeConfirmRunClose(modalID) {
	_DedeConfirmFuncsClose[modalID]();
}
function DedeConfirm(content = "", title = "确认提示") {
	let modalID = guid();
	return new Promise((resolve, reject) => {
		_DedeConfirmFuncs[modalID] = ()=> {
			resolve("success");
			CloseModal(`DedeModal${modalID}`);
		}
		_DedeConfirmFuncsClose[modalID] = ()=> {
			reject("cancel");
			CloseModal(`DedeModal${modalID}`);
		}
		let footer = `<button type="button" class="btn btn-primary btn-sm" onclick="__DedeConfirmRun(\'${modalID}\')">确定</button><button type="button" class="btn btn-outline-primary btn-sm" onclick="__DedeConfirmRunClose(\'${modalID}\')">取消</button>`;
		let modal = `<div id="DedeModal${modalID}" class="modal fade" tabindex="-1" aria-labelledby="DedeModalLabel${modalID}" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="DedeModalLabel${modalID}">${title}</h5>`;
		modal += `<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>`;
		modal += `</div><div class="modal-body">${content}</div><div class="modal-footer">${footer}</div></div></div></div>`;
		$("body").append(modal)
		new bootstrap.Modal(document.getElementById(`DedeModal${modalID}`), {
			backdrop: 'static',
			keyboard: false
		}).show();
		$("#DedeModal" + modalID).on('hidden.bs.modal', function(e) {
			$("#DedeModal" + modalID).remove();
		});
	});
}
function ShowMsg(content, ...args) {
	title = "系统提示";
	size = '';
	if (typeof content == "undefined") content = '';
	modalID = guid();
	var footer = `<button type="button" class="btn btn-primary btn-sm" onclick="CloseModal(\'GKModal${modalID}\')">确定</button>`;
	var noClose = false;
	if (args.length == 1) {
		//存在args参数
		if (typeof args[0].title !== 'undefined' && args[0].title != "") {
			title = args[0].title;
		}
		if (typeof args[0].footer !== 'undefined' && args[0].footer != "") {
			footer = args[0].footer;
		}
		if (typeof args[0].size !== 'undefined' && args[0].size != "") {
			size = args[0].size;
		}
		if (typeof args[0].noClose !== 'undefined' && args[0].noClose == true) {
			noClose = true;
		}
	}
	footer = footer.replaceAll("~modalID~", modalID);
	content = content.replaceAll("~modalID~", modalID);
	var modal = `<div id="GKModal${modalID}" class="modal fade" tabindex="-1" aria-labelledby="GKModalLabel${modalID}" aria-hidden="true"><div class="modal-dialog ${size}" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="GKModalLabel${modalID}">${title}</h5>`;
	if (!noClose) {
		modal += `<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>`;
	}
	modal += `</div><div class="modal-body">${content}</div><div class="modal-footer">${footer}</div></div></div></div>`;
	$("body").append(modal)
	new bootstrap.Modal(document.getElementById(`GKModal${modalID}`), {
		backdrop: 'static',
		keyboard: false
	}).show();
	$("#GKModal" + modalID).on('hidden.bs.modal', function(e) {
		$("#GKModal" + modalID).remove();
	});
	return modalID;
}
function CloseModal(modalID) {
	$("#" + modalID).modal('hide');
	$("#" + modalID).on('hidden.bs.modal', function(e) {
		if ($("#" + modalID).length > 0) {
			$("#" + modalID).remove();
		}
	});
}
$(function($) {
	$("#toggleMenu").click(function() {
		var bodyMenu = $("body").attr("class");
		if (bodyMenu === "menu-show") {
			$("body").attr("class", "menu-hide");
			$(this).html('<i class="fa fa-indent"></i>');
		} else {
			$("body").attr("class", "menu-show");
			$(this).html('<i class="fa fa-dedent"></i>');
		}
	});
	function headColour() {
		const hour = new Date().getHours();
		if (hour >= 5 && hour < 7) {
			$('.admin-head').css('background', 'linear-gradient(45deg, #6f00b3, #deb5ff)');
		} else if (hour >= 7 && hour < 9) {
			$('.admin-head').css('background', 'linear-gradient(45deg, #d079ee, #8a88fb)');
		} else if (hour >= 9 && hour < 17) {
			$('.admin-head').css('background', 'linear-gradient(45deg, #4b73ff, #7cf7ff)');
		} else if (hour >= 17 && hour < 19) {
			$('.admin-head').css('background', 'linear-gradient(45deg, #0e2c5e, #5d85a6)');
		} else {
			$('.admin-head').css('background', 'linear-gradient(45deg, #181818, #565656)');
		}
	}
	headColour();
	//setInterval(headColour, 3600000);
	$(".side-menu .menu-item").on("click", function() {
		var $this = $(this);
		$(".side-menu .menu-sub").stop().slideUp();
		$this.siblings(".side-menu .menu-item").removeAttr("id");
		if ($this.attr("id") === "show") {
			$this.removeAttr("id");
		} else {
			$this.attr("id", "show").next().slideDown();
		}
	});
	$(".side-menu .sub-item").click(function() {
		$(".side-menu .sub-item").removeClass("active");
		$(this).addClass("active");
	});
	$("#btnClearAll").click(function(event) {
		litpicImgSrc = '';
		litpicImg = '';
		$("#picname").val(litpicImg);
		$("#litPic").attr("src", "../static/web/img/thumbnail.jpg");
	})
	if ($.fn.daterangepicker) {
		$(".datepicker").daterangepicker({
			"singleDatePicker": true,
			"autoApply": true,
			"showDropdowns": true,
			"linkedCalendars": false,
			"timePicker": true,
			"timePicker24Hour": true,
			"timePickerSeconds": true,
			"showCustomRangeLabel": false,
			"drops": "up",
			ranges: {
				'今日': [moment(), moment()],
				'昨日': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
				'本月': [moment().startOf('month'), moment().startOf('month')],
				'上月': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').startOf('month')]
			},
			"locale": {
				format: 'YYYY-MM-DD HH:mm:ss',
				applyLabel: '确定',
				cancelLabel: '取消',
				daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
				monthNames: ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
				firstDay: 1
			}
		}, function(start) {
			$(this).val(start.format("YYYY-MM-DD HH:mm:ss"));
		});
		$(".datepicker").on("show.daterangepicker", function(ev, picker) {
			if (picker.element.offset().top - $(window).scrollTop() + picker.container.outerHeight() > $(window).height()) {
				picker.drops = "up";
			} else {
				picker.drops = "down";
			}
			picker.move();
		});
	}
});