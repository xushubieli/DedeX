function LoadSuns(ctid, tid) {
	if (document.getElementById(ctid).innerHTML.length < 10) {
		document.getElementById('icon' + tid).className = 'fa fa-minus-square';
		fetch('catalog_do.php?dopost=GetSunLists&cid=' + tid).then(resp => resp.text()).then(d => {
			document.getElementById(ctid).innerHTML = d;
		});
	} else {
		showHide(ctid, tid);
	}
}
function showHide(objname, tid) {
	var element = document.getElementById(objname);
	if (element.style.display === "none") {
		document.getElementById('icon' + tid).className = 'fa fa-minus-square';
		element.style.display = "";
	} else {
		document.getElementById('icon' + tid).className = 'fa fa-plus-square';
		element.style.display = "none";
	}
}