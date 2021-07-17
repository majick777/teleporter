// =================
// Teleporter Script
// =================

/* Set Default Settings */
var teleporter = {debug: false, fadetime: 2000, ignore: ['no-transition','no-teleporter'], iframe: 'teleporter-iframe', loading: 'teleporter-loading'}

topwin = window.top;
if (typeof topwin.poppedstate == 'undefined') {
	if  (typeof topwin.History == 'function') {topwin.poppedstate = topwin.History.getState();}
	else {topwin.poppedstate = ('state' in topwin.history && topwin.history.state !== null);}
}
if (typeof topwin.initialurl == 'undefined') {topwin.initialurl = window.location.href;}

/* --- Transition Page --- */
function teleporter_transition_page(link) {
	if ((typeof History != 'function') && !window.history) {return true;}

	/* maybe load existing state */
	topwin = window.top;
	if (typeof topwin.stateurls !== 'undefined') {
		if (teleporter.debug) {console.log(topwin.stateurls);}
		stateurls = topwin.stateurls;
		for (i in stateurls) {
			if (stateurls[i] == link.href) {
				if (i == topwin.currentstate) {return false;}
				if (teleporter.debug) {console.log('Switching to Existing State: '+i);}
				teleporter_switch_state(i);
				title = topwin.statetitles[i];
				var obj = {id: i, title: title, url: href};
				topwin.pushing = true;
				if (typeof topwin.History == 'function') {topwin.History.replaceState(obj, title, link.href);}
				else {topwin.history.replaceState(obj, title, link.href);}
				topwin.pushing = false;
				topwin.currentstate = i;
				return false;
			}
		}
	}

	/* load new state in new iframe */
	iframe = teleporter_add_iframe(link.href);
	if (teleporter.debug) {console.log('Loading New Iframe:'); console.log(iframe);}

	/* maybe show loading div */
	topdoc = topwin.document;
	if (teleporter.loading && topdoc.getElementById(teleporter.loading)) {
		topdoc.getElementById(teleporter.loading).className = 'reset';
		setTimeout(function() {topdoc.getElementById(teleporter.loading).className = 'loading';}, 250);
	}

	return false;
}

/* --- Transition Check --- */
function teleporter_transition_check() {

	href = null; iframe = null; topwin = window.top; topdoc = window.top.document;
	if (topwin != window.self) {
		iframes = parent.document.getElementsByClassName(teleporter.iframe);
		if (teleporter.debug) {console.log(iframes);}
		for (i = 0; i < iframes.length; i++) {
			/* console.log(window.location.href+' - '+iframes[i].src); */
			if (window.location.href == iframes[i].src) {iframe = iframes[i];}
		}
		if (!iframe) {return;}

		if (iframe.src != topwin.location.href) {

			/* maybe hide loading div */
			if (teleporter.loading && topdoc.getElementById(teleporter.loading)) {
	    		topdoc.getElementById(teleporter.loading).className = '';
			}

			/* store top window body margin and padding */
	    	body = topdoc.getElementsByTagName('body')[0];
	    	if (!topwin.bodymargin) {topwin.bodymargin = body.style.margin;}
	    	if (!topwin.bodypadding) {topwin.bodypadding = body.style.padding;}

			/* remove parent margin and padding and set overflow hidden hide scrollbars */
	    	body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'hidden';

			/* fade in or display parent iframe with current document */
			if ((typeof parent.jQuery == 'function') && teleporter.fadetime) {
				topwin.jQuery(iframe).fadeIn(teleporter.fadetime);
			} else {iframe.style.display = 'block';}
			href = iframe.src;
	    }
	} else {href = window.location.href;}

	/* set the browser URL (via pushstate) */
	if (href) {
		titletag = document.getElementsByTagName('title');
		if (titletag.length) {title = titletag[0].innerHTML;} else {title = '';}
		if (typeof topwin.statecount === 'undefined') {
			topwin.statecount = 0; stateid = 0; topwin.windowstateid = 0;
			if (teleporter.debug) {console.log('Loaded Window with New State '+stateid);}
			stateurls = []; stateurls[0] = href; topwin.stateurls = stateurls;
			statetitles = []; statetitles[0] = document.title; topwin.statetitles = statetitles;
		} else {
			topwin.statecount++; stateid = topwin.statecount;
			if (teleporter.debug) {console.log('Loaded Window with New State '+stateid);}
			if ( (topwin != window.self) && (typeof window.windowstateid === 'undefined') ) {
				window.windowstateid = stateid;
				if (teleporter.debug) {console.log(topwin.stateurls);}
				topwin.stateurls[stateid] = href;
				topwin.statetitles[stateid] = document.title;
			}
		}
		if (teleporter.debug) {
			console.log('Setting Window PushState');
			console.log('ID: '+stateid+' - Title: '+title+' - URL: '+href);
		}
		var obj = {id: stateid, title: title, url: href};
		topwin.pushing = true;
		if (topwin.History == 'function') {topwin.History.pushState(obj, title, href);}
		else if (topwin.history) {topwin.history.pushState(obj, title, href);}
		topwin.pushing = false;

		topwin.currentstate = stateid;
		if (iframe) {iframe.setAttribute('id', teleporter.iframe+'-'+stateid);}
	}
}

/* --- Add PopState Event Checker --- */
function teleporter_add_popstate_checker() {
	if (teleporter.debug) {console.log('Adding Window Popstate Event');}

	/* for History.js only */
	if (typeof window.History == 'function') {
		/* ref: https://github.com/browserstate/history.js */
		(function(window,undefined) {
			History.Adapter.bind(window, 'statechange', function (event) {
				if (teleporter.debug) {console.log('State Change Event');}
				teleporter_custom_event('teleporter-popstate-event', {event: event});
				teleporter_popstate_checker(event);
			});
		})(window);
	} else {
		/* add main popstate event listener */
		window.addEventListener('popstate', function(event) {
			if (teleporter.debug) {console.log('Window PopState Event');}
			teleporter_custom_event('teleporter-popstate-event', {event: event});
			teleporter_popstate_checker(event);
		}, false );
	}
}

/* --- Popstate Event Checker --- */
function teleporter_popstate_checker(event) {

	topwin = window.top;

	/* ignore inital popstate that some browsers fire on page load */
	/* ref: https://stackoverflow.com/a/17176274/5240159 */
	topwin.initialpop = !topwin.poppedstate && (window.location.href == topwin.initialurl);
	topwin.poppedstate = true; if (topwin.initialpop) {return;}

	/* ignore pushstate to create state ID */
	if (topwin.pushingstate) {return;}

	/* if (window.document.referrer == (window.top.location.protocol+'//'+window.top.location.hostname)) {
		if (teleporter.debug) {console.log('Referrer matches top window hostname.');}
	} */

	/* if ((typeof window.top.backclicked != 'undefined') && window.top.backclicked) {
		window.top.backclicked = false; return;
	*/

	/* get event history state */
	stateid = false;
	if (event.state) {console.log('Event State'); console.log(event);}
	if  (typeof topwin.History != 'undefined') {
		state = topwin.History.getState();
		if (state.data.id) {stateid = state.data.id;}
		else {
			for (i = 0; i < topwin.stateurls.length; i++) {
				if (stateurls[i] == state.url) {stateid = i;}
			}
		}
	} else if (typeof topwin.history != 'undefined') {
		if (event.state) {state = event.state; stateid = state.id;}
		else if (topwin.history.state) {state = topwin.history.state; stateid = state.id;}
		else {return true;}
	} else {return true;}
	if (teleporter.debug) {console.log('Popstate Event'); console.log(event); console.log(state);}

	/* check state ID and URL match */
	/* note: this means back button was pressed beyond existing states */
	if (!stateid || (topwin.stateurls == 'undefined') || (state.url != topwin.stateurls[stateid])) {
		/* found = false;
		for (i in window.top.stateurls) {
			if (window.top.stateurls[i] == state.url) {
				stateid = i; title = window.top.statetitles[i]; found = true;
				if (teleporter.debug) {console.log('State mismatch. Corrected to State ID '+stateid);}
				var obj = {id: i, title: title, url: state.url};
				if (window.top.history.pushState) {window.top.history.replaceState(obj, title, state.url);}
				else {window.top.History.replaceState(obj, title, state.url);}
			}
		}
		if (!found) { */
			if (teleporter.debug) {
				console.log('State mismatch. No transition action.');
				console.log('ID: '+stateid+' - URL: '+state.url);
				console.log(topwin.stateurls);
			}
			// window.top.backclicked = true;
			// history.go(-2);
			/* window.top.document.location.href = state.url; */
			return;
		/* } */
	}

	if (teleporter.debug) {console.log('Switching to State '+stateid);}
	if (event.preventDefault) {event.preventDefault();}
	if (event.stopImmediatePropagation) {event.stopImmediatePropagation();}
	teleporter_switch_state(stateid);
}

/* --- Switch Page State --- */
function teleporter_switch_state(stateid) {

	topwin = window.top;

	/* check conditions */
	if (typeof topwin.windowstateid == 'undefined') {return;}
	if (typeof topwin.currentstate != 'undefined') {currentstate = topwin.currentstate;}
	else {currentstate = 0; topwin.currentstate = 0;}
	if (stateid == currentstate) {return;}
	if (teleporter.debug) {console.log('Switching to State ID: '+stateid+' (Current State: '+currentstate+')');}
	teleporter_custom_event('teleporter-switch-state', {stateid: stateid});

	/* get all iframes */
	iframes = topwin.document.getElementsByClassName(teleporter.iframe);
	if (teleporter.debug) {console.log(iframes);}
	for (i = 0; i < iframes.length; i++) {
		if (iframes[i].id == teleporter.iframe+'-'+stateid) {iframe = iframes[i]; j = i;}
	}
	if (teleporter.debug) {console.log('Matched State '+stateid+' to Iframe '+j); console.log(iframe);}

	if (topwin.windowstateid == stateid) {

		/* restore top window view */
		if (teleporter.debug) {console.log('Restoring First Page State');}
		body = topwin.document.getElementsByTagName('body')[0];
		body.style.margin = topwin.bodymargin;
		body.style.padding = topwin.bodypadding;
		body.style.overflow = 'scroll';
		for (i = 0; i < iframes.length; i++) {
			if (teleporter.debug) {console.log('Hiding Iframes');}
			if (iframes[i].style.display != 'none') {
				if ((typeof jQuery == 'function') && teleporter.fadetime) {
					jQuery(iframes[i]).fadeOut(teleporter.fadetime);
				} else {iframes[i].style.display = 'none';}
			}
		}
		topwin.currentstate = 0;

	} else {

		/* set window scroll styles */
		doc = iframes.contentDocument || iframe.contentWindow.document;
		body = doc.getElementsByTagName('body')[0];
		body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'hidden';
		if (teleporter.debug) {console.log('Removed Margins, Padding and Scroll on Window '+j);}

		/* hide other iframes */
		for (i = 0; i < iframes.length; i++) {
			if (teleporter.debug) {console.log('Hiding Iframes');}
			if ((i != j) && (iframes[i].style.display != 'none')) {
				if ((typeof jQuery == 'function') && teleporter.fadetime) {
					jQuery(iframes[i]).fadeOut(teleporter.fadetime);
				} else {iframes[i].style.display = 'none';}
			}
		}

		/* display the iframe */
		if ((typeof jQuery == 'function') && teleporter.fadetime) {
			jQuery(iframe).fadeIn(teleporter.fadetime);
		} else {iframe.style.display = 'block';}
	}

	/* set top window state and title */
	topwin.document.title = window.top.statetitles[stateid];
	topwin.currentstate = stateid;
	teleporter_custom_event('teleporter-transitioned', {stateid: stateid});
}

/* --- Add (Missing) Transition Iframe --- */
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
	window.top.document.getElementsByTagName('body')[0].appendChild(iframe);
	return iframe;
}

/* --- Remove Window State ID on Unload --- */
addEventListener('unload', function(event) {
	window.top.windowstateid = 'undefined';
}, false);

/* Add Onclick Loading to Page Links */
if (typeof window.jQuery !== 'undefined') {

	/* TODO: ignore all external links ? */

	/* add onclicks to links with jQuery */
	jQuery(document).ready(function() {

		if (!teleporter.iframe) {return;}

		/* make current iframe scroll */
		if (parent.document) {document.getElementsByTagName('body')[0].style.overflow = 'scroll';}

		/* loop all links to add onclick attribute */
		teleporter_custom_event('teleporter-check-links', false);
		jQuery('a').each(function() {
			element = jQuery(this)[0];
			if ( !element.onclick && !jQuery(this).attr('onclick')
			  && !jQuery(this).attr('target') && (element.href.indexOf('#') < 0) ) {
				skip = false;
				if (teleporter.ignore.length) {
					for (i in teleporter.ignore) {
						if (jQuery(this).hasClass(teleporter.ignore[i])) {skip = true;}
					}
				}
				if (!skip) {
					/* TODO: also check for click events via findHandlerJS ? */
					ev = jQuery._data(element, 'events');
					if (!ev || !ev.click) {
						if (teleporter.debug) {console.log('Adding onclick attribute to link.');}
						jQuery(this).attr('onclick', 'return teleporter_transition_page(this);');
					}
				}
			}
		});
		teleporter_custom_event('teleporter-links-checked', false);
		teleporter_transition_check();
		teleporter_add_popstate_checker();
	});

} else {

	/* DocReady */
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

	/* add onclicks to links with Javascript only */
	window.documentReady(function() {

		if (!teleporter.iframe) {return;}

		/* make current iframe scroll */
		if (parent.document) {document.getElementsByTagName('body')[0].style.overflow = 'scroll';}

		/* loop all links to add onclick attribute */
		teleporter_custom_event('teleporter-check-links', false);
		alinks = document.getElementsByTagName('a');
		for (var i = 0; i < alinks.length; i++) {
			if ( !alinks[i].onclick && !alinks[i].getAttribute('onclick')
			  && !alinks[i].getAttribute('target') && (alinks[i].href.indexOf('#') < 0) ) {
				skip = false;
				if (teleporter.ignore.length) {
					for (i in teleporter.ignore) {
						if (alinks[i].classList.contains(teleporter.ignore[i])) {skip = true;}
					}
				}
				if (!skip) {
					/* TODO: check for click events via findHandlerJS ? */
					if (teleporter.debug) {console.log('Adding onclick attribute to link '+alinks[i]);}
					alinks[i].setAttribute('onclick', 'return teleporter_transition_page(this);');
				}
			}
		}
		teleporter_custom_event('teleporter-links-checked', false);

		teleporter_transition_check();
		teleporter_add_popstate_checker();
	});
}

/* --- Dispatch Custom Event --- */
function teleporter_custom_event(name, detail) {
	params = {bubbles: false, cancelable: false, detail: detail}
	var event = new CustomEvent(name, params); document.dispatchEvent(event);
	if (teleporter.debug) {console.log('Teleporter Custom Event: '+name);}
}

/* --- CustomEvent support polyfill --- */
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