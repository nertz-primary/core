// Орбработчик изменения значения select-ов
function form_select_change(name, visibles)
{
	// Небольшой финт ушами чтобы не делать несколько функций
	if (!name) {
		for (var i in visibles) {
			form_select_change(i, visibles);
		}
		return;
	}
	var value = $_(name).value;
	if (visibles && visibles[name])	{
		var lvisibles = visibles[name];
		for (var i in lvisibles) {
			if (lvisibles[i]) {
				var v = lvisibles[i];
				for (var j in v) {
					var tr = $_('tr_' + v[j]);
					if(tr) {
						tr.style.display = 'none';
					}
				}
			}
		}
		if (lvisibles[value]) {
			for (var i in lvisibles[value]) {
				var tr = $_('tr_' + lvisibles[value][i]);
				if(tr) {
					tr.style.display = '';
				}
			}
		}
	}
}

var d = document;
var offsetfromcursorY=15 // y offset of tooltip
var ie=d.all && !window.opera;
var ns6=d.getElementById && !d.all;
var tipobj,op;

function tooltip(el,txt) {
	tipobj = d.getElementById('help_mess');
	if (!tipobj) {
		var b = document.getElementsByTagName('body')[0];
		var div = document.createElement('div');
		div.id = 'help_mess';
		div.style.position = "absolute";
		div.style.display = "none";
		b.appendChild(div);
		tipobj = d.getElementById('help_mess');
	}
	tipobj.innerHTML = txt;
	op = 0.1;
	tipobj.style.opacity = op;
	tipobj.style.display = "block";
	el.onmousemove = positiontip;
	appear();
}

function hide_info(el) {
	d.getElementById('help_mess').style.display = "none";

	el.onmousemove = '';
}

function ietruebody(){
return (d.compatMode && d.compatMode!="BackCompat")? d.documentElement : d.body
}

function positiontip(e) {
	var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
	var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;
	var winwidth=ie? ietruebody().clientWidth : window.innerWidth-20
	var winheight=ie? ietruebody().clientHeight : window.innerHeight-20

	var rightedge=ie? winwidth-event.clientX : winwidth-e.clientX;
	var bottomedge=ie? winheight-event.clientY-offsetfromcursorY : winheight-e.clientY-offsetfromcursorY;

	if (rightedge < tipobj.offsetWidth)	tipobj.style.left=curX-tipobj.offsetWidth+"px";
	else tipobj.style.left=curX+"px";

	if (bottomedge < tipobj.offsetHeight) tipobj.style.top=curY-tipobj.offsetHeight-offsetfromcursorY+"px"
	else tipobj.style.top=curY+offsetfromcursorY+"px";

}

function appear() {
	if(op < 1) {
		op += 0.1;
		tipobj.style.opacity = op;
		tipobj.style.filter = 'alpha(opacity='+op*100+')';
		t = setTimeout('appear()', 30);
	}
}


// Lookup поля
var lookup_id = null;
var lookup_sid = 0;
var lookup_url = null;
var lookup_vals = null;
var req = null;
var in_action = false;
var last_req = "";
var old_value = "";
var inext = {0:0};
var iprev = {0:0};
var to = 0;
function lookup_timer()
{
	if (lookup_id)
	{
		var value = $_(lookup_id + '_lookup').value;
		if (value == old_value && !in_action) {
			do_lookup(value);
		}
		to = setTimeout("lookup_timer()", in_action ? 100 : 500);
		old_value = value;
	}
	return true;
}

function do_lookup(value)
{
	if (value != last_req)
	{
		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				if (req.responseJS.a) {
					lookup_vals= req.responseJS.a;
					var p = $_(lookup_id + '_popup');
					p.innerHTML = "";
					var pi=0;
					inext = {};
					iprev = {};
					var f = 0;
					var cnt = 0;
					for (i in lookup_vals) {
						el = document.createElement('div');
						el.innerHTML = lookup_vals[i];
						el.id = lookup_id + '_popup_' + i;
						el.setAttribute('onclick', "apply_lookup('" + i + "');hide_lookup();");
						el.setAttribute('onmouseover', "this.className='active'; lookup_sid='" + i + "';");
						el.setAttribute('onmouseout',  "this.className='';");
						if (i == lookup_sid) {
							el.className = 'active';
							f = i;
						}
						p.appendChild(el);
						iprev[i] = pi;
						inext[pi] = i;
						pi=i;
						cnt++;
					}
					lookup_sid = f;
					inext[pi] = 0;
					iprev[0] = pi;
					p.innerHTML += '<!--[if lte IE 6.5]><iframe></iframe><![endif]--></div>';
					if (cnt == 1 && $_(lookup_id + '_lookup').value == lookup_vals[lookup_sid]) {
						p.style.display = 'none';
					} else {
						p.style.display = 'block';
						p.style.width = ($_(lookup_id + '_lookup').clientWidth + 2) + 'px';
					}
				}
				in_action = false;
			}
		}
		req.open("GET", lookup_url, true);
		req.send({s : value});
		last_req = value;
		in_action = true;
	}
}
function hide_lookup()
{
	if (lookup_sid) {
		apply_lookup(lookup_sid);
	}
	if (lookup_id) {
		var value = $_(lookup_id + '_popup').style.display = 'none';
	}
}
function apply_lookup(i)
{
	if (lookup_vals[i]) {
		$_(lookup_id).value = i;
		$_(lookup_id + '_lookup').value = lookup_vals[i];
	}
	clearTimeout(to);
}
function lookup_key(evt)
{
	evt = (evt) ? evt : ((window.event) ? event : null);
	var code = evt.keyCode;
	if (code == 40 || code == 38) {
		var ls = lookup_sid;
		lookup_sid = (code == 40) ? inext[lookup_sid] : iprev[lookup_sid];
		if (ls) {
			$_(lookup_id + '_popup_' + ls).className = '';
		}
		if (lookup_sid) {
			$_(lookup_id + '_popup_' + lookup_sid).className = 'active';
		}
		$_(lookup_id + '_lookup').focus();
		return false;
	}
	if (code == 13) {
		apply_lookup(lookup_sid);
		hide_lookup();
		return false;
	}
	lookup_focus();
}
function lookup_focus(id, url)
{
	if (id) {
		lookup_id = id;
		last_req = "";
	}
	if (url) {
		lookup_url = url;
	}

	if (!req) {
		req = new JsHttpRequest();
	}
	to = setTimeout("lookup_timer()",50);
}
function key_killer(evt)
{
	evt = (evt) ? evt : ((window.event) ? event : null);
	var code = evt.keyCode;
	if (code == 13) {
		return false;
	}
}
// Таскание строк в таблице
var movedRow = null, moveTarget = null, movedRowIndex = 0,  mPos = '';
function selMovedRow(row, sel)
{
	rows = row.parentNode.parentNode.rows;
	rowIndex = row.rowIndex;
	row.style.backgroundColor = sel ? '#EEEEEE' : 'transparent';
	for (var i = 0; i < row.cells.length; i++) {
		row.cells[i].style.backgroundColor = sel ? '#EEEEEE' : 'transparent';
	}
}

function startMoving(event)
{
	if (!event) event = window.event;
	movedRow = event.target ? event.target : event.srcElement;
	moveTarget = null;
	while (movedRow && movedRow.tagName != 'TR') movedRow = movedRow.parentNode;
	if (!movedRow) return;
	movedRowIndex = movedRow.rowIndex;
	selMovedRow(movedRow, 1);
	document.body.style.cursor = 'move';
	if (is_msie()) {
		document.body.attachEvent("onmouseup", endMoving);
		document.body.attachEvent("onselectstart", movingSelectStart);
	} else {
		addEventListener("mouseup", endMoving, false);
		event.preventDefault();
	}
}

function moveRow(event)
{
	if (!movedRow) {
		return;
	}
	if (!event) event = window.event;
	if (mPos == event.clientX + ' ' + event.clientY) {
		return;
	}
	mPos = event.clientX + ' ' + event.clientY;

	var newRow = event.target ? event.target : event.srcElement;
	while (newRow && newRow.tagName != 'TR') newRow = newRow.parentNode;
	if (!newRow) {
		return;
	}
	var rows = movedRow.parentNode.parentNode.rows;
	var minRow = Math.min(movedRow.rowIndex, newRow.rowIndex);
	var maxRow = Math.max(movedRow.rowIndex, newRow.rowIndex);
	if (is_msie() && is_msie() <= 6) {
		var move_id = rows[maxRow].id;
		var statuses = getCheckBoxStatuses(rows[maxRow]);
	}
	movedRow.parentNode.insertBefore(rows[maxRow], rows[minRow]);
	if (is_msie() && is_msie() <= 6) {
		setCheckBoxStatuses(document.getElementById(move_id), statuses);
	}
	return;
}

function endMoving(event)
{
	if (!movedRow) return;
	if (!event) event = window.event;
	var table = movedRow.parentNode;
	while (null != table && table.nodeName != 'TABLE') {
		table = table.parentNode;
	}
	document.body.style.cursor = 'auto';
	if (typeof(rowMoved) == "function" && moveTarget
	&& moveTarget != movedRow && movedRow.rowIndex != movedRowIndex) {
		rowMoved(movedRow, moveTarget);
	}
	selMovedRow(movedRow, 0);
	movedRow = null;
	moveTarget = null;
	if (is_msie()) {
		document.body.detachEvent("onmouseup", endMoving);
		document.body.detachEvent("onselectstart", movingSelectStart);
	} else {
		removeEventListener("mouseup", endMoving, false);
		event.preventDefault();
	}
}

function getCheckBoxStatuses(Element)
{
	var res = new Array();
	if(!Element) return res;
	var els = Element.getElementsByTagName('input');
	for (var i = 0; i < els.length; i++) {
		if (els[i].type=='checkbox') {
			res[els[i].name] = els[i].checked;
		}
	}
	return res;
}

function setCheckBoxStatuses(Element, statuses)
{
	if(!Element) return res;
	var els = Element.getElementsByTagName('input');
	for (var i = 0; i < els.length; i++) {
		if (els[i].type=='checkbox' && statuses[els[i].name] != undefined) {
			els[i].checked = statuses[els[i].name];
		}
	}
}

function movingSelectStart()
{
	return false;
}
var multiselect_counts = Array();
function multiselect_add_row(table_id, value, key)
{
	if (!multiselect_counts[table_id]) {
		multiselect_counts[table_id] = 0;
	}
	multiselect_counts[table_id] = parseInt(multiselect_counts[table_id]) + 1;
	var ind = multiselect_counts[table_id];
	var table = document.getElementById(table_id);
	var tr4 = document.getElementById(table_id +'_rowedit');
	var tr = document.createElement('TR');
	tr.onmouseover = function(event){moveRow(event);}
	tr.id = table_id + '_row_' + ind;
	if (document.getElementById(table_id + '_edit_key')) {
		var td = document.createElement('TD');
		td.innerHTML = key;
		td.className = "Int";
		td.onselectstart = function(){return false;};
		td.onmousedown = function(event){startMoving(event);};
		var input = document.createElement('INPUT');
		input.type  = 'hidden';
		input.name  = table_id + '_key[' + ind + ']';
		input.value = key;
		td.appendChild(input);
		tr.appendChild(td);
	}
	if (typeof value === 'object') {
		for( i in value) {
			var td1 = document.createElement('TD');
			td1.innerHTML = value[i];
			td1.onselectstart = function(){return false;};
			td1.onmousedown = function(event){startMoving(event);};
			var input1 = document.createElement('INPUT');
			input1.type  = 'hidden';
			input1.name  = table_id + '_value[' + ind + ']['+ i +']';
			input1.value = value[i];
			td1.appendChild(input1);
			tr.appendChild(td1);
		}
	} else {
		var td1 = document.createElement('TD');
		td1.innerHTML = value;
		td1.onselectstart = function(){return false;};
		td1.onmousedown = function(event){startMoving(event);};
		var input1 = document.createElement('INPUT');
		input1.type  = 'hidden';
		input1.name  = table_id + '_value[' + ind + ']';
		input1.value = value;
		td1.appendChild(input1);
		tr.appendChild(td1);

	}
	var td2 = document.createElement('TD');
	var img = document.createElement('IMG');
	img.src = "/core/static/img/button/delete.gif";
	img.onclick = function(){multiselect_delete_row(table_id, ind);};
	img.style.cursor='pointer';
	td2.appendChild(img);
	td2.onselectstart = function(){return false;};
	td2.onmousedown = function(event){startMoving(event);};
	tr.appendChild(td2);
	table.insertBefore(tr, tr4);
}
function multiselect_add_new(table_id)
{
	var key = document.getElementById(table_id + '_edit_key');
	var s = table_id + '_names';
	var abc = '';
	myeval('if (' + s +') {abc = ' + s + ' };');
	if (abc) {
		var value = [];
		for(i in abc) {
			value[abc[i]] = document.getElementById(table_id + '_edit_value[' + abc[i] +']' ).value;
			document.getElementById(table_id + '_edit_value[' + abc[i] +']' ).value = '';
		}
	} else {
		var value = document.getElementById(table_id + '_edit_value').value;
		document.getElementById(table_id + '_edit_value').value = '';
	}

	if (key) {
		multiselect_add_row(table_id, value, key.value);
		key.value = '';
	} else {
		multiselect_add_row(table_id, value);
	}

}
function multiselect_delete_row(table_id, ind)
{
	var table = document.getElementById(table_id);
	var row = document.getElementById(table_id + '_row_' + ind);
	table.removeChild(row);
}
function myeval(s)
{
	return eval(s);
}

function ajaxed(ind, item, lookup_url, type)
{
	var val = item.value;
	if (type == 'Int') {
		if (!(parseFloat(val) == parseInt(val, 10) && !isNaN(val))) {
			item.className = 'error';
			return;
		} else {
			item.className = '';
		}
	}
	if (!req) {
		req = new JsHttpRequest();
	}
	req.onreadystatechange = function() {
		if (req.readyState == 4) {
			if (req.responseJS.a) {
				n_action = false;
			}
		}
	}
	req.open("GET", lookup_url, true);
	
	if (item.type == 'checkbox' && !item.checked) {
		val = 0;
	}
	req.send({s : val, i: ind});
}
function form_init_tag(el_id, url) {
	$(document).ready(function() {
		$('#'+el_id+'_taginput').autocomplete({
				source: function( request, response ) {
					$.getJSON( url, {
						term: extractLast( request.term )
					}, response );
				},
				search: function() {
					var term = extractLast( this.value );
					if ( term.length < 2 ) {
						return false;
					}
				},
				focus: function() {
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					terms.pop();
					terms.push( ui.item.value );
					terms.push( "" );
					this.value = terms.join( ", " );
					return false;
				}
		}).keypress(function(event) {
 			 if (event && event.keyCode == '13') {
 			 	var terms = split($(this).val());
 			 	for(i in terms) {
	     			var v = terms[i];
	     			$(this).autocomplete("close");
	 			 	if (v) {
		 			 	form_add_tag(el_id, v, 0);
		     			form_init_tag_divs();
	 			 	}
 			 	}
 			 	$(this).val('');
 			 	return false;
			 }

  		}) ;

	});
}
function form_add_tag(el_id, value) {
	if (value) {
		var el = $('#'+el_id+'_tags');
		el.css('display', '');
		el.append("<div><input type='hidden' name='" + el_id + "[]' value='" + value + "'>" + value + "<span>&times;</span></div>");
	}
}
function form_init_tag_divs() {
	$('.form-multiselect div').hover(
		function () {
	    	$(this).addClass("hover");
	  	},
	  	function () {
	    	$(this).removeClass("hover");
	  	}
	);
	$('.form-multiselect div span').click(function() {
		var mp = $(this).parent('div').parent('div');
		if (mp.children('div').length < 2) {
			mp.css('display', 'none');
		}
		$(this).parent('div').remove();
	});
}
function split( val ) {
			return val.split( /,\s*/ );
		}
function extractLast( term ) {
	return split( term ).pop();
}
