if (typeof teleporter == 'undefined') {
	var teleporter = {debug: false, fadetime: 2000, timeout: 10000, ignore: ['.no-transition','.no-teleporter'], dynamic: [], iframe: 'teleporter-iframe', loading: 'teleporter-loading', 'siteurl': '', refresh: [] };
}
var t_topwin; t_topwin = teleporter_top_window();
if (typeof t_topwin.t_loading == 'undefined') {t_topwin.t_loading = false;}
if (typeof t_topwin.t_loaded == 'undefined') {t_topwin.t_loaded = false;}
if (typeof t_topwin.t_pushing == 'undefined') {t_topwin.t_pushing = false;}
if (typeof t_topwin.t_cancel == 'undefined') {t_topwin.t_cancel = false;}
if (typeof t_topwin.t_initialurl == 'undefined') {t_topwin.t_initialurl = window.location.href;}
if (typeof t_topwin.t_poppedstate == 'undefined') {
	if  (typeof t_topwin.History == 'function') {t_topwin.t_poppedstate = t_topwin.History.getState();}
	else {t_topwin.t_poppedstate = ('state' in t_topwin.history && t_topwin.history.state !== null);}
}
function teleporter_transition_page(link) {
	if ((typeof History != 'function') && !window.history) {return true;}
	t_topwin.t_cancel = false; 
	if (typeof t_topwin.stateurls !== 'undefined') {
		if (teleporter.debug) {console.log(t_topwin.stateurls);}
		stateurls = t_topwin.stateurls;
		for (i in stateurls) {
			if (teleporter.debug) {console.log(link.href+' - '+i+': '+stateurls[i]);}
			if (stateurls[i] == link.href) {
				if (i == t_topwin.currentstate) {
					if (teleporter.debug) {console.log('Keeping Current State ('+t_topwin.currentstate+')');}
					return false;
				}
				if (teleporter.debug) {console.log('Switching to Existing State: '+i);}
				switchstate = teleporter_switch_state(i);
				if (!switchstate) {return false;}
				title = t_topwin.statetitles[i];
				var obj = {id: i, title: title, url: link.href};
				t_topwin.t_pushing = true;
				if (typeof t_topwin.History == 'function') {t_topwin.History.replaceState(obj, title, link.href);}
				else if (t_topwin.history) {t_topwin.history.replaceState(obj, title, link.href);}
				if (teleporter.debug) {
					if (typeof t_topwin.History == 'function') {console.log(t_topwin.History.getState());}
					else if (t_topwin.history) {console.log(t_topwin.history.state);}
				}
				t_topwin.t_pushing = false;
				return false;
			}
		}
	}
	iframe = teleporter_add_iframe(link.href);
	if (teleporter.debug) {console.log('Loading New Iframe:'); console.log(iframe);}
	teleporter_show_loading(link.href);
	return false;
}
function teleporter_transition_check(url, win) {
	if (t_topwin.t_cancel) {return;}
	href = null; iframe = null; topdoc = t_topwin.document;
	if (t_topwin != win.self) {
		iframes = topdoc.getElementsByClassName(teleporter.iframe);
		if (teleporter.debug) {console.log(iframes);}
		if (!url) {url = win.location.href;}
		for (i = 0; i < iframes.length; i++) {
			if (url == iframes[i].src) {iframe = iframes[i];}
		}
		if (!iframe) {
			if (teleporter.debug) {console.log('No matching parent iframe found for '+win.location.href+' !');}
			return;
		}
		if (iframe.src != t_topwin.location.href) {
			teleporter_hide_loading();
	    	body = topdoc.getElementsByTagName('body')[0];
	    	if (!t_topwin.bodymargin) {t_topwin.bodymargin = body.style.margin;}
	    	if (!t_topwin.bodypadding) {t_topwin.bodypadding = body.style.padding;}
	    	body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'hidden';
			if (iframe.style.display != 'block') {
				if ((typeof t_topwin.jQuery == 'function') && teleporter.fadetime) {
					t_topwin.jQuery(iframe).fadeIn(teleporter.fadetime);
				} else {iframe.style.display = 'block';}
			}
			href = iframe.src;
	    }
	} else {href = win.location.href;}
	if (href) {
		stateid = teleporter_push_state(href, win);
		if (iframe) {iframe.setAttribute('id', teleporter.iframe+'-'+stateid);}
	}
}
function teleporter_push_state(href, win) {
		if (teleporter.debug) {console.log('Current State: '+t_topwin.currentstate);}
		titletag = win.document.getElementsByTagName('title');
		if (titletag.length) {title = titletag[0].innerHTML;} else {title = '';}
		if (typeof t_topwin.stateurls === 'undefined') {
			t_topwin.windowstateid = 0; stateid = 0;
			if (teleporter.debug) {console.log('Loaded Window with New State '+stateid);}
			stateurls = []; stateurls[0] = href; t_topwin.stateurls = stateurls;
			statetitles = []; statetitles[0] = title; t_topwin.statetitles = statetitles;
		} else {
			found = false;
			for (i = 0; i < t_topwin.stateurls.length; i++) {
				if (t_topwin.stateurls[i] == href) {found = true; stateid = i; title = t_topwin.statetitles[i];}
			}
			if (!found) {
				stateid = t_topwin.stateurls.length;
				if (teleporter.debug) {console.log('Loaded Window with New State '+stateid);}
				if ((t_topwin != win.self) && (typeof win.windowstateid == 'undefined') ) {
					win.windowstateid = stateid;
					if (teleporter.debug) {console.log(t_topwin.stateurls);}
					t_topwin.stateurls[stateid] = href;
					t_topwin.statetitles[stateid] = title;
				}
			}
		}
		if (teleporter.debug) {
			console.log('Setting Window PushState');
			console.log('ID: '+stateid+' - Title: '+title+' - URL: '+href);
			console.log(t_topwin.stateurls); console.log(t_topwin.statetitles);
		}
		var obj = {id: stateid, title: title, url: href};
		t_topwin.t_pushing = true;
		if (typeof t_topwin.History == 'function') {t_topwin.History.pushState(obj, title, href);}
		else if (t_topwin.history) {t_topwin.history.pushState(obj, title, href);}
		t_topwin.t_pushing = false;
		teleporter_custom_event('teleporter-state-pushed', obj);
		if (teleporter.debug) {
			if (typeof t_topwin.History == 'function') {console.log(t_topwin.History.getState());}
			else if (t_topwin.history) {console.log(t_topwin.history.state);}
		}
		t_topwin.currentstate = stateid;
		if (teleporter.debug) {console.log('Set Current State: '+t_topwin.currentstate);}
		t_topwin.t_loaded = t_topwin.t_loading; t_topwin.t_loading = false;
		return stateid;
}
function teleporter_show_loading(href) {
	t_topwin.t_loading = href;
	setTimeout(function() {
		if (!t_topwin.t_loading) {return;}
		href = t_topwin.t_loading;
		console.log('Page load timeout reached. Displaying URL: '+href);
		iframes = t_topwin.document.getElementsByClassName(teleporter.iframe);
 		for (i = 0; i < iframes.length; i++) {
 			if (href == iframes[i].src) {iframe = iframes[i];}
		}
		if (!iframe) {return;}
		win = iframe.contentWindow;
		teleporter_transition_check(href, win);
	}, teleporter.timeout);
	if (!teleporter.loading) {return;}
	topdoc = t_topwin.document;
	topdoc.getElementsByTagName('body')[0].classList.add('teleporter-loading');
	topdoc.getElementById(teleporter.loading).className = 'reset';
	setTimeout(function() {topdoc.getElementById(teleporter.loading).className = 'loading';}, 250);
	iframes = topdoc.getElementsByClassName(teleporter.iframe);
	for (i = 0; i < iframes.length; i++) {
		doc = iframes[i].contentDocument || iframes[i].contentWindow.document;
		if (doc.getElementById(teleporter.loading)) {doc.getElementById(teleporter.loading).className = 'reset';}
	}
	setTimeout(function() {
		for (i = 0; i < iframes.length; i++) {
			doc = iframes[i].contentDocument || iframes[i].contentWindow.document;
			body = doc.getElementsByTagName('body')[0];
			if (body) {body.classList.add('teleporter-loading');}
			if (doc.getElementById(teleporter.loading)) {doc.getElementById(teleporter.loading).className = 'loading';}
		}
	}, 250);
}
function teleporter_hide_loading() {
	if (!teleporter.loading) {return;}
	topdoc = t_topwin.document;
	topdoc.getElementById(teleporter.loading).className = '';
	topdoc.getElementsByTagName('body')[0].classList.remove('teleporter-loading');
	iframes = topdoc.getElementsByClassName(teleporter.iframe);
	for (i = 0; i < iframes.length; i++) {
		doc = iframes[i].contentDocument || iframes[i].contentWindow.document;
		body = doc.getElementsByTagName('body')[0];
		if (body) {body.classList.remove('teleporter-loading');}
		if (doc.getElementById(teleporter.loading)) {doc.getElementById(teleporter.loading).className = '';}
	}
}
function teleporter_add_popstate_checker() {
	if (teleporter.debug) {console.log('Adding Window Popstate Event');}
	if (typeof window.History == 'function') {
		(function(window,undefined) {
			History.Adapter.bind(window, 'statechange', function (event) {
				if (teleporter.debug) {console.log('State Change Event');}
				teleporter_custom_event('teleporter-popstate-event', {event: event});
				teleporter_popstate_checker(event);
			});
		})(window);
	} else {
		window.addEventListener('popstate', function(event) {
			if (teleporter.debug) {console.log('Window PopState Event');}
			teleporter_custom_event('teleporter-popstate-event', {event: event});
			teleporter_popstate_checker(event);
		}, false );
	}
}
function teleporter_popstate_checker(event) {
	t_topwin.initialpop = !t_topwin.t_poppedstate && (window.location.href == t_topwin.t_initialurl);
	t_topwin.t_poppedstate = true; if (t_topwin.initialpop) {return;}
	if (t_topwin.t_pushing) {return;}
	if (teleporter.debug) {
		if (window.document.referrer == (t_topwin.location.protocol+'//'+t_topwin.location.hostname)) {
			console.log('Referrer matches top window hostname.');
		}
	}
	if ((typeof t_topwin.backclicked != 'undefined') && t_topwin.backclicked) {
		t_topwin.backclicked = false; return;
	}
	stateid = null;
	if (teleporter.debug) {if (event.state) {console.log('History Event State:'); console.log(event);} }
	if  (typeof t_topwin.History != 'undefined') {
		state = t_topwin.History.getState();
		if (state.data.id) {stateid = state.data.id;}
		else {
			if (teleporter.debug) {console.log(state);}
			for (i = 0; i < t_topwin.stateurls.length; i++) {
				if (stateurls[i] == state.url) {stateid = i;}
			}
		}
	} else if (t_topwin.history) {
		if (event.state) {state = event.state; stateid = state.id;}
		else if (t_topwin.history.state) {state = t_topwin.history.state; stateid = state.id;}
		else {return true;}
	} else {return true;}
	if (teleporter.debug) {console.log('Popstate Event'); console.log(event); console.log(state);}
	if ((stateid === null) || (t_topwin.stateurls == 'undefined') || (state.url != t_topwin.stateurls[stateid])) {
			if (teleporter.debug) {
				console.log('State mismatch. No transition action.');
				console.log('ID: '+stateid+' - URL: '+state.url);
				console.log(t_topwin.stateurls);
			}
			if (state.url == t_topwin.t_initialurl) {
				t_topwin.backclicked = true;
				if (typeof t_topwin.History == 'function') {t_topwin.History.back();}
				else if (t_topwin.history) {history.back();}
			} else {
				teleporter_transition_page({href: state.url});
			}
			return;
	}
	if (teleporter.debug) {console.log('Switching to State '+stateid);}
	if (event.preventDefault) {event.preventDefault();}
	if (event.stopImmediatePropagation) {event.stopImmediatePropagation();}
	switchstate = teleporter_switch_state(stateid);
	if (!switchstate) {return false;}
}
function teleporter_switch_state(stateid) {
	if (typeof t_topwin.windowstateid == 'undefined') {return;}
	if (typeof t_topwin.currentstate == 'undefined') {t_topwin.currentstate = 0;}
	if (stateid == t_topwin.currentstate) {
		if (teleporter.debug) {console.log('Keeping Existing State ('+stateid+')');}
		return false;
	}
	if (teleporter.debug) {console.log('Switching to State ID: '+stateid+' (Current State: '+t_topwin.currentstate+')');}
	teleporter_custom_event('teleporter-switch-state', {stateid: stateid});
	iframes = t_topwin.document.getElementsByClassName(teleporter.iframe);
	if (teleporter.debug) {console.log(iframes);}
	iframe = false;
	for (i = 0; i < iframes.length; i++) {
		if (iframes[i].id == teleporter.iframe+'-'+stateid) {
			if (teleporter.debug) {console.log('Matched State '+stateid+' to Iframe '+i); console.log(iframe);}
			iframe = iframes[i]; j = i;
			win = iframe.contentWindow;
			doc = iframe.contentDocument || iframe.contentWindow.document;
			body = doc.getElementsByTagName('body')[0];
			if (body.hasAttribute('teleporter-refresh')) {
				if (teleporter.debug) {console.log('Reloading iframe '+i+': '+iframe.src);}
				src = iframe.src; iframe.src = 'javascript:void(0);'; iframe.src = src;
			}
		}
	}
	if (t_topwin.windowstateid == stateid) {
		win = window.top;
		body = t_topwin.document.getElementsByTagName('body')[0];
		if (body.hasAttribute('teleporter-refresh')) {
			href = stateurls[stateid];
			if (teleporter.debug) {console.log('Reloading Top Window: '+href);}
			if (t_topwin.location.href == href) {t_topwin.location.reload();}
			else {t_topwin.location.href = href;}
			return false;
		}
		if (teleporter.debug) {console.log('Restoring First Page State');}
		body.style.margin = t_topwin.bodymargin;
		body.style.padding = t_topwin.bodypadding;
		body.style.overflow = 'scroll';
		for (i = 0; i < iframes.length; i++) {
			if (teleporter.debug) {console.log('Hiding All Iframes');}
			if (iframes[i].style.display != 'none') {
				if ((typeof jQuery == 'function') && teleporter.fadetime) {
					jQuery(iframes[i]).fadeOut(teleporter.fadetime);
				} else {iframes[i].style.display = 'none';}
			}
		}
	} else if (iframe) {
		body = t_topwin.document.getElementsByTagName('body')[0];
		body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'hidden';
		if (teleporter.debug) {console.log('Removed Margins, Padding and Scroll on Top Window');}
		for (i = 0; i < iframes.length; i++) {
			if (teleporter.debug) {console.log('Hiding Iframes');}
			if ((i != j) && (iframes[i].style.display != 'none')) {
				if ((typeof jQuery == 'function') && teleporter.fadetime) {
					jQuery(iframes[i]).fadeOut(teleporter.fadetime);
				} else {iframes[i].style.display = 'none';}
			}
		}
		if ((typeof jQuery == 'function') && teleporter.fadetime) {
			jQuery(iframe).fadeIn(teleporter.fadetime);
		} else {iframe.style.display = 'block';}
	}
	t_topwin.document.title = t_topwin.statetitles[stateid];
	t_topwin.currentstate = stateid;
	if (teleporter.debug) {console.log('New Current State: '+t_topwin.currentstate);}
	href = t_topwin.stateurls[stateid];
	teleporter_push_state(href, win);
	teleporter_custom_event('teleporter-transitioned', {stateid: stateid});
}
function teleporter_add_iframe(src) {
	iframe = document.createElement('iframe');
	iframe.setAttribute('class', teleporter.iframe);
	iframe.setAttribute('name', teleporter.iframe);
	iframe.setAttribute('src', src);
	iframe.setAttribute('width', '100%');
	iframe.setAttribute('height', '100%');
	iframe.setAttribute('frameborder', '0');
	iframe.setAttribute('scrolling', 'auto');
	iframe.setAttribute('allowfullscreen', 'true');
	iframe.setAttribute('style', 'display:none;');
	t_topwin.document.getElementsByTagName('body')[0].appendChild(iframe);
	return iframe;
}
function teleporter_skip_link(el) {
	if ((el.getAttribute('teleporter') == '1') || (el.getAttribute('no-teleporter') == '1')) {return true;}
	if ((typeof el.href == 'undefined') || (el.href == '')) {return true;}
	if ((typeof el.target != 'undefined') && (el.target != null) && (el.target != '_self') && (el.target != '')) {return true;}
	if ((typeof el.onclick != 'undefined') && (el.onclick != null) && (el.onclick != '')) {return true;}
	if (typeof t_click_events != 'undefined') {
		for (i in t_click_events) { if (t_click_events[i].element == el) {return true;} }
	}
	if (el.hasAttribute('data-elementor-open-lightbox') && (el.getAttribute('data-elementor-open-lightbox') == 'yes')) {return true;}
	u = el.href; skip = true;
	if ((u.indexOf('javascript') === 0) || (u.indexOf('mailto') === 0) || (u.indexOf('tel') === 0)) {return true;}
	if ((u.indexOf('#') === 0) || (u.indexOf('?') === 0)) {skip = false;}
	if ((teleporter.siteurl != '') && (u.indexOf(teleporter.siteurl) === 0)) {skip = false;}
	if (el.host == t_topwin.location.host) {
		a = t_topwin.location.protocol+'//'+t_topwin.location.host;
		b = '//'+t_topwin.location.host;
		if ((u.indexOf(a) === 0) || (u.indexOf(b) === 0)) {skip = false;}
	}
	if (!skip && teleporter.ignore.length) {
		for (i in teleporter.ignore) {
			if (el.matches(teleporter.ignore[i])) {skip = true;}
		}
	}
	if (teleporter.debug) {if (!skip) {console.log('Found internal URL: '+u);} }
	return skip;
}
addEventListener('unload', function(event) {
	t_topwin.windowstateid = 'undefined';
}, false);
function teleporter_top_window() {
	try {test = window.top.location; return window.top;} catch(e) {
		return teleporter_get_window_parent(window.self);
	}
}
function teleporter_get_window_parent(win) {
	parentwindow = false;
	try {test = win.parent.location; parentwindow = win.parent;} catch(e) {return false;}
	if (parentwindow) {
		if (parentwindow == win) {return win;}
		maybe = teleporter_get_window_parent(parentwindow);
		if (maybe) {return maybe;}
		return parentwindow;
	}
	return win;
}
function teleporter_add_link_events() {
	if (typeof findEventHandlers != 'undefined') {
		var t_click_events = findEventHandlers('click','a');
	}
	jQuery('a').each(function() {
		el = jQuery(this)[0];
		skip = teleporter_skip_link(el);
		if (!skip) {
			el.setAttribute('teleporter', '1');
			teleporter_add_link_event(el);
		}
	});
}
function teleporter_add_link_event(el) {
	el.addEventListener('click', function(e) {
		e.stopImmediatePropagation();
		e.preventDefault();
		target = jQuery(e.target);
		if (target.prop('tagName') != 'a') {target = target.closest('a');}
		element = target[0];
		return teleporter_transition_page(element);
	});
}
function teleporter_add_link_onclicks() {
	alinks = document.getElementsByTagName('a');
	for (var i = 0; i < alinks.length; i++) {
		skip = teleporter_skip_link(alinks[i]);
		if (!skip) {
			alinks[i].setAttribute('teleporter', '1');
			teleporter_add_link_onclick(alinks[i]);
		}
	}
}
function teleporter_add_link_onclick(el) {
	el.setAttribute('onclick', 'return teleporter_transition_page(this);');
}
function teleporter_dynamic_link_clicks() {
	if (!teleporter.dynamic.length) {return;}
	var dynamic_selectors = '';
	for (i = 0; i < teleporter.dynamic.length; i++) {
		if (dynamic_selectors != '') {dynamic_selectors += ', ';}
		dynamic_selectors += teleporter.dynamic[i];
	}
	if (teleporter.debug) {console.log('Dynamic Selectors: '+dynamic_selectors);}
	jQuery('a').on('click', dynamic_selectors, function(e) {
		e.stopImmediatePropagation();
		e.preventDefault();
		target = jQuery(e.target);
		if (target.prop('tagName') != 'a') {target = target.closest('a');}
		if (target.getAttribute('teleporter') == '1') {return;}
		element = target[0];
		skip = teleporter_skip_link(element);
		if (!skip) {
			if (teleporter.debug) {console.log(target);}
			return teleporter_transition_page(element);	
		}
	});
}
if (typeof window.jQuery !== 'undefined') {
	jQuery(document).ready(function() {
		if (!teleporter.iframe) {return;}
		if (parent.document) {document.getElementsByTagName('body')[0].style.overflow = 'scroll';}
		teleporter_custom_event('teleporter-check-links', false);
		teleporter_add_link_events();
		teleporter_dynamic_link_clicks();
		teleporter_custom_event('teleporter-links-checked', false);
		teleporter_transition_check(false, window);
		teleporter_add_popstate_checker();
		setTimeout(function() {teleporter_add_link_events();;}, 5000);
	});
} else {
	(function(funcName, baseObj) {
		"use strict"; funcName = funcName || 'documentReady'; baseObj = baseObj || window;
		var readyList = []; var readyFired = false; var readyEventHandlersInstalled = false;
		function ready() {
			if (!readyFired) {
				readyFired = true;
				for (var i = 0; i < readyList.length; i++) {
					readyList[i].fn.call(window, readyList[i].ctx);
				}
				readyList = [];
			}
		}
		function readyStateChange() {if (document.readyState === "complete") {ready();} }
		baseObj[funcName] = function(callback, context) {
			if (readyFired) {setTimeout(function() {callback(context);}, 1); return;}
			else {readyList.push({fn: callback, ctx: context});}
			if (document.readyState === 'complete' || (!document.attachEvent && document.readyState === 'interactive')) {
				setTimeout(ready, 1);
			} else if (!readyEventHandlersInstalled) {
				if (document.addEventListener) {
					document.addEventListener('DOMContentLoaded', ready, false);
					window.addEventListener('load', ready, false);
				} else {
					document.attachEvent('onreadystatechange', readyStateChange);
					window.attachEvent('onload', ready);
				}
				readyEventHandlersInstalled = true;
			}
		}
	})('documentReady', window);
	window.documentReady(function() {
		if (!teleporter.iframe) {return;}
		if (parent.document) {document.getElementsByTagName('body')[0].style.overflow = 'scroll';}
		teleporter_custom_event('teleporter-check-links', false);
		teleporter_add_link_onclicks();
		teleporter_custom_event('teleporter-links-checked', false);
		teleporter_transition_check(false, window);
		teleporter_add_popstate_checker();
		setTimeout(function() {teleporter_add_link_onclicks();}, 5000);
	});
}
function teleporter_custom_event(name, detail) {
	params = {bubbles: false, cancelable: false, detail: detail};
	var event = new CustomEvent(name, params); document.dispatchEvent(event);
	if (teleporter.debug) {console.log('Teleporter Custom Event: '+name); console.log(detail);}
}
(function () {
	if (typeof window.CustomEvent === 'function') {return false;}
	function CustomEvent(event, params) {
		params = params || {bubbles: false, cancelable: false, detail: undefined};
		var evt = document.createEvent('CustomEvent');
		evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
		return evt;
	}
	CustomEvent.prototype = window.Event.prototype;
	window.CustomEvent = CustomEvent;
})();
document.addEventListener('keydown', (event) => {
    if (t_topwin.t_loading && (event.key === 'Escape')) {
        const isNotCombinedKey = !(event.ctrlKey || event.altKey || event.shiftKey);
        if (isNotCombinedKey) {
            if (teleporter.debug) {console.log('Cancelling Page Transition.');}
			t_topwin.t_cancel = true; 
			t_topwin.t_loading = false; 
			teleporter_hide_loading();
        }
    }
});
if (typeof jQuery != 'undefined') {
 var findEventHandlers = function (eventType, jqSelector) {
    var results = [];
    var $ = jQuery;
    var arrayIntersection = function (array1, array2) {
        return $(array1).filter(function (index, element) {
            return $.inArray(element, $(array2)) !== -1;
        });
    };
    var haveCommonElements = function (array1, array2) {
        return arrayIntersection(array1, array2).length !== 0;
    };
    var addEventHandlerInfo = function (element, event, $elementsCovered) {
        var extendedEvent = event;
        if ($elementsCovered !== void 0 && $elementsCovered !== null) {
            $.extend(extendedEvent, { targets: $elementsCovered.toArray() });
        }
        var eventInfo;
        var eventsInfo = $.grep(results, function (evInfo, index) {
            return element === evInfo.element;
        });
        if (eventsInfo.length === 0) {
            eventInfo = {
                element: element,
                events: [extendedEvent]
            };
            results.push(eventInfo);
        } else {
            eventInfo = eventsInfo[0];
            eventInfo.events.push(extendedEvent);
        }
    };
    var $elementsToWatch = $(jqSelector);
    if (jqSelector === "*") 
        $elementsToWatch = $elementsToWatch.add(document); 
    var $allElements = $("*").add(document);
    $.each($allElements, function (elementIndex, element) {
        var allElementEvents = $._data(element, "events");
        if (allElementEvents !== void 0 && allElementEvents[eventType] !== void 0) {
            var eventContainer = allElementEvents[eventType];
            $.each(eventContainer, function(eventIndex, event){
                var isDelegateEvent = event.selector !== void 0 && event.selector !== null;
                var $elementsCovered;
                if (isDelegateEvent) {
                    $elementsCovered = $(event.selector, element); 
                } else {
                    $elementsCovered = $(element); 
                }
                if (haveCommonElements($elementsCovered, $elementsToWatch)) {
                    addEventHandlerInfo(element, event, $elementsCovered);
                }
            });
        }
    });
    return results;
 };
}