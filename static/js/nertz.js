function $_(n){
	return document.getElementById(n);
}

function get_event_id(evt)
{
    evt = (evt) ? evt : ((window.event) ? event : null);
    if(evt)
    {
        var elem = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
        if(elem.nodeType == 3) elem = elem.parentNode;
        if(elem && elem.id) return elem.id;
        else return null;
    }
}

function is_msie()
{
    var ua = navigator.userAgent,
        m  = ua.match(/MSIE (\d+(\.\d+)?)/);
    if (ua.indexOf('Opera') == -1 && m) {
        return parseFloat(m[1]);
    } else {
        return 0;
    }
} 

jsHover = function() {
		var hEls = document.getElementById("nav").getElementsByTagName("LI");
		for (var i=0, len=hEls.length; i<len; i++) {
			hEls[i].onmouseover=function() { this.className+=" jshover"; }
			hEls[i].onmouseout=function() { this.className=this.className.replace(" jshover", ""); }
		}
	}
if (window.attachEvent && navigator.userAgent.indexOf("Opera")==-1) window.attachEvent("onload", jsHover);

