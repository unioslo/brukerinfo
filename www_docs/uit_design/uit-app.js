/*
* UiT JavaScript - Apps
*
* Credits:
*
* http://www.dustindiaz.com/getelementsbyclass/ <= http://ejohn.org/blog/getelementsbyclassname-speed-comparison/
* http://snipplr.com/view/3561/addclass-removeclass-hasclass/
*
*/

  var uitAppDoDebug = false;

  //Searchforms

  var searchSubmits = getElementsByClass("searchsubmit", document, "button");
  var searchStrings = getElementsByClass("searchstring", document, "input");
  var searchLabels = getElementsByClass("searchstringlabel", document, "label");

  // Search submit buttons init
  for(var i = 0; i < searchSubmits.length; i++) {
    searchSubmits[i].onclick = uitAppSearch;
  }

  // Searchfields init (labeltext logic)
  for(var i = 0; i < searchStrings.length; i++) {
    searchStrings[i].onfocus = uitAppSearchFocus;
    searchStrings[i].onblur = uitAppSearchBlur;
    var initSearch = searchLabels[i].innerHTML;
    uitAppSearchStringChanges(initSearch, "#505050", "90%", searchStrings[i])
  }

  // Fix active state for buttons in IE 6 and IE 7
  if(navigator.appName.indexOf("Internet Explorer") != -1) {
    var temp=navigator.appVersion.split("MSIE");
    var version=parseFloat(temp[1]);
    if(version <= 7 && version != -1) {
      var buttons = document.getElementsByTagName("button");
      for(var i = 0; i < buttons.length; i++) {
        buttons[i].onmousedown = uitAppMakeActive;
        buttons[i].onmouseup = uitAppMakeDeactive;
      }
      
    }
  }

  function uitAppHasClass(ele,cls) {
	return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
  }

  function uitAppAddClass(ele,cls) {
	if (!this.uitAppHasClass(ele,cls)) ele.className += " "+cls;
  }

  function uitAppRemoveClass(ele,cls) {
	if (uitAppHasClass(ele,cls)) {
    	var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
		ele.className=ele.className.replace(reg,' ');
	}
  }

  function getElementsByClass(searchClass,node,tag) {
    if(navigator.appName.indexOf("Internet Explorer") != -1) {
      var classElements = new Array();
      if ( node == null )
              node = document;
      if ( tag == null )
              tag = '*';
      var els = node.getElementsByTagName(tag);
      var elsLen = els.length;
      for (i = 0; i < elsLen; i++) {
              if ( uitAppHasClass(els[i], searchClass) ) {
                        classElements.push(els[i]);
              }
      }
      return classElements;
     } else {
       return node.getElementsByClassName(searchClass);
     }
  }

  function uitAppSearchStringChanges(text, color, fontSize, that) {
    var elem = getElementsByClass("searchstring", that.parentNode, "input")[0];
    elem.value = text;
    elem.style.color = color;
    elem.style.fontSize = fontSize;
  }

  function uitAppDebug(e) {
    var str = "<strong>Debug event:</strong>"
            + "<ul style='padding: 10px; list-style-position: inside'>"
            + "<li>Type: " + e.type + "</li>"

    if(navigator.appName.indexOf("Internet Explorer") == -1) {
      str += "<li>Node: " + e.target.nodeName+ "</li>"
          + "<li>Class: " + e.target.className + "</li>"
          + "<li>Name: " + e.target.name + "</li>"
          + "<li>Value: " + e.target.value + "</li>"
    }
    str += "</ul>";
    document.getElementById("app-content").innerHTML = str;
  }

  function uitAppSearch(e) {
    if (!e) var e = window.event;
    if(uitAppDoDebug) {
      uitAppDebug(e);
    }
    var initSearch = getElementsByClass("searchstringlabel", this.parentNode, "label")[0].innerHTML;
    var searchString = getElementsByClass("searchstring", this.parentNode, "input")[0];
    if(searchString.value == initSearch) {
      searchString.value = "";
    }
    e.cancelBubble = true;
    if (e.stopPropagation) e.stopPropagation();
  }

  function uitAppSearchFocus(e) {
    if (!e) var e = window.event;
    if(uitAppDoDebug) {
      uitAppDebug(e);
    }
    uitAppSearchStringChanges("", "#2B2B2B", "100%", this);
    e.cancelBubble = true;
    if (e.stopPropagation) e.stopPropagation();
  }

  function uitAppSearchBlur(e) {
    if (!e) var e = window.event;
    if(uitAppDoDebug) {
      uitAppDebug(e);
    }
    var initSearch = getElementsByClass("searchstringlabel", this.parentNode, "label")[0].innerHTML;
    if(getElementsByClass("searchstring", this.parentNode, "input")[0].value == "") {
      uitAppSearchStringChanges(initSearch, "#505050", "90%", this);
    }
    e.cancelBubble = true;
    if (e.stopPropagation) e.stopPropagation();
  }

  function uitAppMakeActive(e) {
    if (!e) var e = window.event;
    uitAppAddClass(this, "active");
  }

  function uitAppMakeDeactive(e) {
    if (!e) var e = window.event;
    uitAppRemoveClass(this, "active");
  }

