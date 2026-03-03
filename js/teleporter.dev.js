/* ================= */
/* Teleporter Script */
/* ================= */

/* --- Set Default Settings --- */
/* 1.0.0: added pageload timeout setting */
/* 1.0.6: added existing definition check */
/* 1.1.3: added prompton and externalize settings */
/* 1.1.3: removed refresh setting as applied elsewhere */
if (typeof teleporter == 'undefined') {
	var teleporter = {debug: false, fadetime: 2000, timeout: 10000, prompton: '404', externalize: true, ignore: ['.no-transition','.no-teleporter'], dynamic: [], iframe: 'teleporter-iframe', loading: 'teleporter-loading', 'siteurl': '' };
}

/* --- Set Initial Variables --- */
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

/* --- Transition Page --- */
function teleporter_transition_link(link) {return teleporter_transition_page(link.href);}
function teleporter_transition_page(href) {
	if ((typeof History != 'function') && !window.history) {return true;}
	t_topwin.t_cancel = false; /* reset cancel state */

	/* maybe load existing state */
	if (typeof t_topwin.stateurls !== 'undefined') {
		/* if (teleporter.debug) {console.log(t_topwin.stateurls);} */
		stateurls = t_topwin.stateurls;
		for (i in stateurls) {
			/* if (teleporter.debug) {console.log(link.href+' - '+i+': '+stateurls[i]);} */
			if (stateurls[i] == href) {
				if (i == t_topwin.currentstate) {
					/* if (teleporter.debug) {console.log('Keeping Current State ('+t_topwin.currentstate+')');} */
					return false;
				}
				/* if (teleporter.debug) {console.log('Switching to Existing State: '+i);} */
				switchstate = teleporter_switch_state(i);
				if (!switchstate) {return false;}
				title = t_topwin.statetitles[i];
				var obj = {id: i, title: title, url: href};
				t_topwin.t_pushing = true;
				if (typeof t_topwin.History == 'function') {t_topwin.History.replaceState(obj, title, href);}
				else if (t_topwin.history) {t_topwin.history.replaceState(obj, title, href);}
				/* if (teleporter.debug) {
					if (typeof t_topwin.History == 'function') {console.log(t_topwin.History.getState());}
					else if (t_topwin.history) {console.log(t_topwin.history.state);}
				} */
				t_topwin.t_pushing = false;
				return false;
			}
		}
	}

	/* load new state in new iframe */
	iframe = teleporter_add_iframe(href);
	/* if (teleporter.debug) {console.log('Loading New Iframe:'); console.log(iframe);} */

	/* maybe show loading div */
	teleporter_show_loading(href);

	return false;
}

/* --- Transition Check --- */
function teleporter_transition_check(url, win) {

	if (t_topwin.t_cancel) {return;}
	if (t_topwin == win.self) {

		/* this is the top (first) window */
		href = t_topwin.location.href;
		titletag = win.document.getElementsByTagName('title');
		if (titletag.length) {title = titletag[0].innerHTML;} else {title = '';}
		stateid = teleporter_push_state(href, title);
		t_topwin.windowstateid = stateid;

	} else {

		/* any other subwindow iframe */
		if (!url) {url = win.location.href; maybefirst = true;} else {maybefirst = false;}
		titletag = win.document.getElementsByTagName('title');
		if (titletag.length) {title = titletag[0].innerHTML;} else {title = '';}

		/* set the browser URL (via pushstate) */
		stateid = teleporter_push_state(url, title);
		if (typeof t_topwin.windowstateid == 'undefined') {t_topwin.windowstateid = stateid;}

		/* show the iframe */
		if (typeof t_topwin.teleporter_show_iframe == 'function') {
			t_topwin.teleporter_show_iframe(url, win, stateid);
		}
		
		/* check if first iframe */
		if (maybefirst && (typeof t_topwin.teleporter_check_for_first_iframe == 'function')) {
			t_topwin.teleporter_check_for_first_iframe(url, stateid);
		}
	}
}

/* Check for first iframe via Top */
/* 1.1.3: added to fix forward button disappearing after first back click */
function teleporter_check_for_first_iframe(url, stateid) {
	iframes = document.getElementsByClassName(teleporter.iframe);
	if (iframes.length == 1) {
		iframe = iframes[0];
		if (!iframe.classList.contains('checked')) {
			/* repush the state for first iframe (back/forth) */
			teleporter_push_state(t_topwin.stateurls[0], t_topwin.statetitles[0]);
			teleporter_push_state(t_topwin.stateurls[1], t_topwin.statetitles[1]);
			iframe.classList.add('checked');
		}
	}
}

/* --- Show Iframe via Top --- */
function teleporter_show_iframe(href, win, stateid) {

	iframes = document.getElementsByClassName(teleporter.iframe);
	/* if (teleporter.debug) {console.log(iframes);} */
	for (i = 0; i < iframes.length; i++) {
		/* console.log(href+' - '+iframes[i].src); */
		if (href == iframes[i].src) {iframe = iframes[i];}
	}
	if (!iframe) {
		if (teleporter.debug) {console.log('No matching iframe found for '+href+' !');}
		return;
	}

	/* ? not sure if needed anymore */
	/* if (href != t_topwin.location.href) */

	/* maybe hide loading div */
	teleporter_hide_loading();

	/* store top window body margin and padding */
	/* body = topdoc.getElementsByTagName('body')[0];
	if (!t_topwin.bodymargin) {t_topwin.bodymargin = body.style.margin;}
	if (!t_topwin.bodypadding) {t_topwin.bodypadding = body.style.padding;} */
	/* remove parent margin and padding and set overflow hidden hide scrollbars */
	/* body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'hidden'; */
	teleporter_window_body_store();

	/* fade in or display parent iframe with current document */
	if (iframe.style.display != 'block') {
		if ((typeof t_topwin.jQuery == 'function') && teleporter.fadetime) {
			t_topwin.jQuery(iframe).fadeIn(teleporter.fadetime);
		} else {iframe.style.display = 'block';}
	}
	
	/* set state id on iframe */
	iframe.setAttribute('id', teleporter.iframe+'-'+stateid);
	/* teleporter_switch_state(stateid); */
}

/* --- Push State --- */
/* 1.0.0: separated function to allow for timeout usage */
function teleporter_push_state(href, title) {

	/* if (teleporter.debug) {console.log('Current State: '+t_topwin.currentstate);} */
	if (typeof t_topwin.stateurls === 'undefined') {
		t_topwin.windowstateid = 0; stateid = 0;
		/* if (teleporter.debug) {console.log('Loaded Window with New State '+stateid);} */
		stateurls = []; stateurls[0] = href; t_topwin.stateurls = stateurls;
		statetitles = []; statetitles[0] = title; t_topwin.statetitles = statetitles;
	} else {
		found = false;
		for (i = 0; i < t_topwin.stateurls.length; i++) {
			if (t_topwin.stateurls[i] == href) {
				found = true; stateid = i;
				if (title) {t_topwin.statetitles[i] = title;}
				else if (typeof t_topwin.statetitles[i] != 'undefined') {title = t_topwin.statetitles[i];}
			}
		}
		if (!found) {
			stateid = t_topwin.stateurls.length;
			t_topwin.stateurls[stateid] = href;
			if (title) {t_topwin.statetitles[stateid] = title;} else {title = '';}
			/* if (teleporter.debug) {console.log('Loaded Window with New State '+stateid);} */
		}
	}

	/* if (teleporter.debug) {
		console.log('Setting Window PushState');
		console.log('ID: '+stateid+' - Title: '+title+' - URL: '+href);
		console.log(t_topwin.stateurls); console.log(t_topwin.statetitles);
	} */
	var obj = {id: stateid, title: title, url: href};
	t_topwin.t_pushing = true;
	if (typeof t_topwin.History == 'function') {t_topwin.History.pushState(obj, title, href);}
	else if (t_topwin.history) {t_topwin.history.pushState(obj, title, href);}
	t_topwin.t_pushing = false;
	teleporter_custom_event('teleporter-state-pushed', obj);
	/* if (teleporter.debug) {
		if (typeof t_topwin.History == 'function') {console.log(t_topwin.History.getState());}
		else if (t_topwin.history) {console.log(t_topwin.history.state);}
	} */

	t_topwin.currentstate = stateid;
	/* if (teleporter.debug) {console.log('Set Current State: '+t_topwin.currentstate);} */
	t_topwin.t_loaded = t_topwin.t_loading; t_topwin.t_loading = false;
	return stateid;
}

/* --- Show Loading Divs --- */
/* 1.1.3: use classList add/remove instead of className */
function teleporter_show_loading(href) {

	/* set maximum page load timeout */
	/* 1.0.0: added to auto-display slow loading pages */
	t_topwin.t_loading = href;
	setTimeout(function() {
		if (!t_topwin.t_loading) {return;}
		if (teleporter.debug) {console.log('Page load timeout reached.');}
		/* 1.1.2: prompt user to view, retry or cancel */
		doprompt = false; prompton = teleporter.prompton;
		if ((prompton == 'yes') || (prompton == '404t') || (prompton == 'all')) {doprompt = true;}
		if (doprompt && (typeof jQuery == 'function') && jQuery.ui && jQuery.ui.dialog && jQuery('#teleporter-prompt-modal'.length)) {
			/* 1.1.3: give an extra few seconds before prompting */
			setTimeout(function() {
				/* 1.1.3: allow for error checking */
				if (!jQuery('#teleporter-prompt-modal').hasClass('error')) {
					question = jQuery('#teleporter-timeout-question').html();
					jQuery('#teleporter-prompt-modal .teleporter-prompt-question').html(question);
					jQuery('#teleporter-prompt-modal').addClass('timeout').dialog({ modal:true, width:300, dialogClass: 'teleporter-dialog', position: {my: 'center top', at: 'center top+80', of: window } });

					var teleporter_timeout_check; clearInterval(teleporter_timeout_check);
					teleporter_timeout_check = setInterval(function() {
						if (jQuery('#teleporter-prompt-modal').hasClass('error')) {clearInterval(teleporter_timeout_check);}
						if (!t_topwin.t_loading) {
							jQuery('#teleporter-prompt-modal').removeClass('timeout').removeClass('error');
							if (jQuery('#teleporter-prompt-modal').data('ui-dialog')) {
								jQuery('#teleporter-prompt-modal').dialog('close');
							}
							clearInterval(teleporter_timeout_check);
						}
					}, 250);
				}
			}, 2000);
		} else {
			teleporter_prompt_choice('view');
		}

	}, teleporter.timeout);

	/* maybe show the loading div */
	if (!teleporter.loading) {return;}
	
	/* 1.1.3: trigger show loading function in top window */
	if (typeof t_topwin.teleporter_show_loading_via_top == 'function') {
		t_topwin.teleporter_show_loading_via_top();
	}
	
}

/* --- Show Loading via Top Window --- */
function teleporter_show_loading_via_top() {
	
	document.getElementsByTagName('body')[0].classList.add('teleporter-loading');
	document.getElementById(teleporter.loading).classList.add('reset');
	setTimeout(function() {
		document.getElementById(teleporter.loading).classList.remove('reset');
		document.getElementById(teleporter.loading).classList.add('loading');
	}, 250);
	iframes = document.getElementsByClassName(teleporter.iframe);
	for (i = 0; i < iframes.length; i++) {
		doc = iframes[i].contentDocument || iframes[i].contentWindow.document;
		if (doc.getElementById(teleporter.loading)) {
			doc.getElementById(teleporter.loading).classList.remove('loading');
			doc.getElementById(teleporter.loading).classList.add('reset');
		}
	}
	setTimeout(function() {
		for (i = 0; i < iframes.length; i++) {
			doc = iframes[i].contentDocument || iframes[i].contentWindow.document;
			body = doc.getElementsByTagName('body')[0];
			if (body) {body.classList.add('teleporter-loading');}
			if (doc.getElementById(teleporter.loading)) {
				doc.getElementById(teleporter.loading).classList.add('loading');
			}
		}
	}, 250);
}
	
/* --- Hide Loading Divs --- */
function teleporter_hide_loading() {
	if (!teleporter.loading) {return;}
	/* 1.1.3: trigger show loading function in top window */
	if (typeof t_topwin.teleporter_hide_loading_via_top == 'function') {
		t_topwin.teleporter_hide_loading_via_top();
	} else {console.log('Hide loading function not found.');}
}

/* Hide Loading via Top Window */
function teleporter_hide_loading_via_top() {
	document.getElementById(teleporter.loading).classList.remove('loading');
	document.getElementById(teleporter.loading).classList.remove('reset');
	document.getElementsByTagName('body')[0].classList.remove('teleporter-loading');
	iframes = document.getElementsByClassName(teleporter.iframe);
	for (i = 0; i < iframes.length; i++) {
		doc = iframes[i].contentDocument || iframes[i].contentWindow.document;
		body = doc.getElementsByTagName('body')[0];
		if (body) {body.classList.remove('teleporter-loading');}
		/* 1.0.0: fix to check for loading element */
		if (doc.getElementById(teleporter.loading)) {
			doc.getElementById(teleporter.loading).classList.remove('reset');
			doc.getElementById(teleporter.loading).classList.remove('loading');
		}
	}
}

/* Timeout Prompt Response Handler */
function teleporter_prompt_choice(choice) {
	jQuery('#teleporter-prompt-modal').removeClass('timeout').removeClass('error');
	if (jQuery('#teleporter-prompt-modal').data('ui-dialog')) {
		jQuery('#teleporter-prompt-modal').dialog('close');
	}
	if (!t_topwin.t_loading) {return;}
	href = t_topwin.t_loading; /* console.log(href); */
	if (choice == 'view') {
		if (teleporter.debug) {console.log('Displaying URL: '+href);}
		if (typeof t_topwin.teleporter_view_page == 'function') {
			t_topwin.teleporter_view_page(href);
		}
	}
	if ((choice == 'retry') || (choice == 'cancel')) {
		t_topwin.t_cancel = true; t_topwin.t_loading = false;
		teleporter_hide_loading();
		if (typeof t_topwin.teleporter_cancel_loading == 'function') {
			t_topwin.teleporter_cancel_loading(href);
		}
	}
	if (choice == 'retry') {
		if (teleporter.debug) {console.log('Reloading Page: '+href);}
		teleporter_transition_page(href);
	} else if (choice == 'cancel') {
		if (teleporter.debug) {console.log('Cancelled Page Transition.');}
	}
}

/* --- View Page via Top --- */
function teleporter_view_page(href) {
	iframes = document.getElementsByClassName(teleporter.iframe);
	for (i = 0; i < iframes.length; i++) {
		if (href == iframes[i].src) {iframe = iframes[i];}
	}
	if (!iframe) {return;}
	win = iframe.contentWindow;
	teleporter_transition_check(href, win);
}

/* --- Cancel Loading (and Remove) via Top --- */
function teleporter_cancel_loading(href) {
	iframes = document.getElementsByClassName(teleporter.iframe);
	for (i = 0; i < iframes.length; i++) {
		if (href == iframes[i].src) {iframe = iframes[i];}
	}
	if (!iframe) {return;}
	iframe.parentElement.removeChild(iframe);
	stateurls = t_topwin.stateurls; statetitles = t_topwin.statetitles;
	for (i = stateurls.length - 1; i >= 0; i--) {
		if (stateurls[i] == href) {stateurls.splice(i,1); statetitles.splice(i,1);}
	}
}

/* --- Add PopState Event Checker --- */
function teleporter_add_popstate_checker() {
	/* if (teleporter.debug) {console.log('Adding Window Popstate Event');} */

	/* for History.js only */
	if (typeof window.History == 'function') {
		/* ref: https://github.com/browserstate/history.js */
		(function(window,undefined) {
			History.Adapter.bind(window, 'statechange', function (event) {
				/* if (teleporter.debug) {console.log('State Change Event');} */
				teleporter_custom_event('teleporter-popstate-event', {event: event});
				teleporter_popstate_checker(event);
			});
		})(window);
	} else {
		/* add main popstate event listener */
		window.addEventListener('popstate', function(event) {
			/* if (teleporter.debug) {console.log('Window PopState Event');} */
			teleporter_custom_event('teleporter-popstate-event', {event: event});
			teleporter_popstate_checker(event);
		}, false );
	}
}

/* --- Popstate Event Checker --- */
function teleporter_popstate_checker(event) {
	
	/* if (teleporter.debug) {console.log(event);} */

	/* ignore initial popstate that some browsers fire on page load */
	/* ref: https://stackoverflow.com/a/17176274/5240159 */
	t_topwin.initialpop = !t_topwin.t_poppedstate && (window.location.href == t_topwin.t_initialurl);
	t_topwin.t_poppedstate = true; if (t_topwin.initialpop) {return;}

	/* ignore pushstate to create state ID */
	if (t_topwin.t_pushing) {return;}

	/* if (teleporter.debug) {
		if (window.document.referrer == (t_topwin.location.protocol+'//'+t_topwin.location.hostname)) {
			console.log('Referrer matches top window hostname.');
		}
	} */

	/* do not go back more than once from original URL */
	if ((typeof t_topwin.backclicked != 'undefined') && t_topwin.backclicked) {
		t_topwin.backclicked = false; return;
	}

	/* get event history state */
	stateid = null;
	/* if (teleporter.debug) {if (event.state) {console.log('History Event State:'); console.log(event);} } */
	if (typeof t_topwin.History != 'undefined') {
		state = t_topwin.History.getState();
		if (state.data.id) {stateid = state.data.id;}
		else {
			/* if (teleporter.debug) {console.log(state);} */
			for (i = 0; i < t_topwin.stateurls.length; i++) {
				if (stateurls[i] == state.url) {stateid = i;}
			}
		}
	} else if (t_topwin.history) {
		if (event.state) {state = event.state; stateid = state.id;}
		else if (t_topwin.history.state) {state = t_topwin.history.state; stateid = state.id;}
		else {return true;}
	} else {return true;}
	/* if (teleporter.debug) {console.log('Popstate Event'); console.log(event); console.log(state);} */

	/* check state ID and URL match */
	/* note: this means back button was pressed beyond existing states */
	if ((stateid === null) || (t_topwin.stateurls == 'undefined') || (state.url != t_topwin.stateurls[stateid])) {
		/* found = false;
		for (i in t_topwin.stateurls) {
			if (t_topwin.stateurls[i] == state.url) {
				stateid = i; title = t_topwin.statetitles[i]; found = true;
				if (teleporter.debug) {console.log('State mismatch. Corrected to State ID '+stateid);}
				var obj = {id: i, title: title, url: state.url};
				if (typeof t_topwin.History == 'function') {t_topwin.History.replaceState(obj, title, state.url);}
				else (t_topwin.history) {t_topwin.history.replaceState(obj, title, state.url);}
			}
		}
		if (!found) { */

			/* if (teleporter.debug) {
				console.log('State mismatch. No transition action.');
				console.log('ID: '+stateid+' - URL: '+state.url);
				console.log(t_topwin.stateurls);
			} */
			if (state.url == t_topwin.t_initialurl) {
				t_topwin.backclicked = true;
				if (typeof t_topwin.History == 'function') {t_topwin.History.back();}
				else if (t_topwin.history) {history.back();}
			} else {
				/* lost from history so just load it */
				teleporter_transition_page(state.url);
			}
			
			/* return false; */
		/* }*/
	}

	/* if (teleporter.debug) {console.log('Switching to State '+stateid);} */
	if (event.preventDefault) {event.preventDefault();}
	if (event.stopImmediatePropagation) {event.stopImmediatePropagation();}
	switchstate = teleporter_switch_state(stateid);
	if (!switchstate) {return false;}
	return true;
}

/* --- Switch Page State --- */
function teleporter_switch_state(stateid) {

	/* check conditions */
	if (typeof t_topwin.windowstateid == 'undefined') {return;}
	if (typeof t_topwin.currentstate == 'undefined') {t_topwin.currentstate = 0;}
	if (stateid == t_topwin.currentstate) {
		/* if (teleporter.debug) {console.log('Keeping Existing State ('+stateid+')');} */
		return false;
	}
	/* if (teleporter.debug) {console.log('Switching to State ID: '+stateid+' (Current State: '+t_topwin.currentstate+')');} */
	teleporter_custom_event('teleporter-switch-state', {stateid: stateid});

	/* 1.1.3: switch to top window check earlier */
	if (t_topwin.windowstateid == stateid) {
		win = t_topwin;
		if (typeof t_topwin.teleporter_switch_to_top == 'function' ) {
			continuing = t_topwin.teleporter_switch_to_top(stateid);
			if (!continuing) {return false;} /* on reload */
		}
	} else {	
		if (typeof t_topwin.teleporter_switch_to_iframe == 'function' ) {
			continuing = t_topwin.teleporter_switch_to_iframe(stateid);
			if (!continuing) {return false;} /* iframe not found */
		}
	}

	/* set current state */
	t_topwin.currentstate = stateid;
	/* if (teleporter.debug) {console.log('New Current State: '+t_topwin.currentstate);} */

	teleporter_custom_event('teleporter-transitioned', {stateid: stateid});
}

/* --- Switch to Top State --- */
function teleporter_switch_to_top(stateid) {

	href = stateurls[stateid];
	body = document.getElementsByTagName('body')[0];

	/* 1.0.8: maybe refresh top window contents */
	if (body.hasAttribute('teleporter-refresh')) {
		/* if (teleporter.debug) {console.log('Reloading Top Window: '+href);} */
		/* 1.1.3: push state before reload */
		teleporter_push_state(href, false);
		if (t_topwin.location.href == href) {t_topwin.location.reload();}
		else {t_topwin.location.href = href;}
		return false;
	}

	/* restore top window view */
	/* body.style.margin = t_topwin.bodymargin;
	body.style.padding = t_topwin.bodypadding;
	body.style.overflow = 'scroll'; */
	/* if (teleporter.debug) {console.log('Restoring First Page State');} */
	teleporter_window_body_restore();
	teleporter_set_window_title(t_topwin.statetitles[stateid]);

	iframes = document.getElementsByClassName(teleporter.iframe);
	for (i = 0; i < iframes.length; i++) {
		/* if (teleporter.debug) {console.log('Hiding All Iframes');} */
		if (iframes[i].style.display != 'none') {
			if ((typeof jQuery == 'function') && teleporter.fadetime) {
				/* 1.1.2: halve fade time for existing window */
				fadetime = parseInt(teleporter.fadetime / 2);
				jQuery(iframes[i]).fadeOut(fadetime);
			} else {iframes[i].style.display = 'none';}
		}
	}
	
	teleporter_push_state(href, false);
	return true;
}

function teleporter_switch_to_iframe(stateid) {

	/* get all iframes */
	iframes = document.getElementsByClassName(teleporter.iframe);
	/* if (teleporter.debug) {console.log(iframes);} */
	iframe = false;
	for (i = 0; i < iframes.length; i++) {
		if (iframes[i].id == teleporter.iframe+'-'+stateid) {
			/* if (teleporter.debug) {console.log('Matched State '+stateid+' to Iframe '+i); console.log(iframe);} */
			iframe = iframes[i]; j = i;
			win = iframe.contentWindow;
			/* 1.0.8: maybe refresh window contents */
			doc = iframe.contentDocument || iframe.contentWindow.document;
			body = doc.getElementsByTagName('body')[0];
			if (body.hasAttribute('teleporter-refresh')) {
				/* if (teleporter.debug) {console.log('Reloading iframe '+i+': '+iframe.src);} */
				src = iframe.src; iframe.src = 'javascript:void(0);'; iframe.src = src;
			}
		}
	}

	if (iframe) {

		/* set iframe scroll styles */
		/* doc = iframe.contentDocument || iframe.contentWindow.document;
		body = doc.getElementsByTagName('body')[0];
		body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'scroll'; */

		/* set top window to passthrough view */
		/* body = t_topwin.document.getElementsByTagName('body')[0];
		body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'hidden'; */
		/* if (teleporter.debug) {console.log('Removed Margins, Padding and Scroll on Top Window');} */
		t_topwin.teleporter_window_body_full();

		/* hide other iframes */
		for (i = 0; i < iframes.length; i++) {
			/* if (teleporter.debug) {console.log('Hiding Iframes');} */
			if ((i != j) && (iframes[i].style.display != 'none')) {
				if ((typeof jQuery == 'function') && teleporter.fadetime) {
					/* 1.1.2: halve fade time for existing iframe */
					fadetime = parseInt(teleporter.fadetime / 2);
					jQuery(iframes[i]).fadeOut(fadetime);
				} else {iframes[i].style.display = 'none';}
			}
		}

		/* display this iframe */
		if ((typeof jQuery == 'function') && teleporter.fadetime) {
			/* 1.1.2: halve fade time for existing iframe */
			fadetime = parseInt(teleporter.fadetime / 2);
			jQuery(iframe).fadeIn(fadetime);
		} else {iframe.style.display = 'block';}

		/* set top window title */
		/* t_topwin.document.title = t_topwin.statetitles[stateid]; */
		teleporter_set_window_title(t_topwin.statetitles[stateid]);
		
		/* 1.0.8: push new state */
		href = t_topwin.stateurls[stateid];
		teleporter_push_state(href, false);
		
		return true;
	}
	return false;
}

/* --- Set Window Title --- */
function teleporter_set_window_title(title) {
	document.title = title;
}

/* --- Set Window Body Full */
function teleporter_window_body_full() {
	body = document.getElementsByTagName('body')[0];
	/* remove parent margin and padding and set overflow hidden hide scrollbars */
	body.style.margin = '0'; body.style.padding = '0'; body.style.overflow = 'hidden';
}

/* --- Store Window Body --- */
function teleporter_window_body_store() {
	/* store top window body margin and padding */
	body = document.getElementsByTagName('body')[0];
	if (!t_topwin.bodymargin) {t_topwin.bodymargin = body.style.margin;}
	if (!t_topwin.bodypadding) {t_topwin.bodypadding = body.style.padding;}
	teleporter_window_body_full();
}

/* --- Restore Window Body --- */
function teleporter_window_body_restore() {
	body = document.getElementsByTagName('body')[0];
	body.style.margin = t_topwin.bodymargin;
	body.style.padding = t_topwin.bodypadding;
	body.style.overflow = 'scroll';
}

/* --- Add (Missing) Transition Iframe --- */
function teleporter_add_iframe(href) {
	teleporter_push_state(href, false);
	if (typeof t_topwin.teleport_add_iframe_via_top == 'function') {
		return t_topwin.teleport_add_iframe_via_top(href);
	}
}

/* --- Add Transition Iframe via Top */
function teleport_add_iframe_via_top(href) {
	teleporter_check_url(href);
	iframe = document.createElement('iframe');
	iframe.setAttribute('class', teleporter.iframe);
	iframe.setAttribute('name', teleporter.iframe);
	iframe.setAttribute('src', href);
	iframe.setAttribute('width', '100%');
	iframe.setAttribute('height', '100%');
	iframe.setAttribute('frameborder', '0');
	iframe.setAttribute('scrolling', 'auto');
	iframe.setAttribute('allowfullscreen', 'true');
	iframe.setAttribute('style', 'display:none;');
	document.getElementsByTagName('body')[0].appendChild(iframe);
	return iframe;
}

/* --- Check URL --- */
function teleporter_check_url(url) {
	if (!jQuery('#teleporter-prompt-modal').length) {return;}
	fetch(url, {method: 'HEAD'}).then(response => {
		if (!response.ok) {
			if (teleporter.debug) {console.log(response.status+': '+response.statusText);}
			doprompt = false; prompton = teleporter.prompton;
			if (404 == response.status) {
				if ((prompton == '404') || (prompton == '404t') || (prompton == 'all')) {doprompt = true;}
				question = jQuery('#teleporter-not-found-question').html();
			} else {
				if (prompton == 'all') {doprompt = true;}
				question = jQuery('#teleporter-error-question').html();
			}
			jQuery('#teleporter-prompt-modal .teleporter-prompt-question').html(question);
			jQuery('#teleporter-prompt-modal').removeClass('timeout').addClass('error').dialog({ modal:true, width:300, dialogClass: 'teleporter-dialog', position: {my: 'center top', at: 'center top+80', of: window } });
			var teleporter_error_check; clearInterval(teleporter_error_check);
			teleporter_error_check = setInterval(function() {
				if (!t_topwin.t_loading) {
					jQuery('#teleporter-prompt-modal').removeClass('timeout').removeClass('error').dialog('close');
					clearInterval(teleporter_error_check);
				}
			}, 250);
		} else if (teleporter.debug) {console.log('Response OK for URL:' +url);}
	}).catch(error => {console.error('Error fetching URL:', error);});
}

/* --- Check link element link --- */
/* 1.0.0: standardized and moved all link checks here */
function teleporter_skip_link(el) {

	/* check for an already treated link */
	/* 1.0.4: added to allow for multiple runs */
	/* 1.0.8: added extra check for no-teleporter attribute */
	if ((el.getAttribute('teleporter') == '1') || (el.getAttribute('no-teleporter') == '1')) {return true;}

	/* treat an undefined/empty href as internal */
	if ((typeof el.href == 'undefined') || (el.href == '')) {return true;}

	/* skip links with a target other than self */
	if ((typeof el.target != 'undefined') && (el.target != null) && (el.target != '_self') && (el.target != '')) {return true;}

	/* skip links that already have an onclick sttribute */
	if ((typeof el.onclick != 'undefined') && (el.onclick != null) && (el.onclick != '')) {return true;}

	/* 1.1.0: skip links with an existing link click event */
	if (typeof t_click_events != 'undefined') {
		for (i in t_click_events) { if (t_click_events[i].element == el) {return true;} }
	}
	
	/* 1.1.0: skip Elementor lightbox links */
	if (el.hasAttribute('data-elementor-open-lightbox') && (el.getAttribute('data-elementor-open-lightbox') == 'yes')) {return true;}

	/* set href shortname */
	u = el.href; skip = true;

	/* 1.0.1: always treat javascript, mailto and tel at position 0 as external */
	/* 1.1.3: added sms prefix to ignore also */
	if ((u.indexOf('javascript') === 0) || (u.indexOf('mailto') === 0) || (u.indexOf('tel') === 0) || (u.indexOf('sms') === 0)) {return true;}

	/* treat hash or query at position 0 as internal */
	if ((u.indexOf('#') === 0) || (u.indexOf('?') === 0)) {skip = false;}

	/* check against site URL */
	if ((teleporter.siteurl != '') && (u.indexOf(teleporter.siteurl) === 0)) {skip = false;}

	/* check against host/protocol */
	if (el.host == t_topwin.location.host) {
		a = t_topwin.location.protocol+'//'+t_topwin.location.host;
		b = '//'+t_topwin.location.host;
		if ((u.indexOf(a) === 0) || (u.indexOf(b) === 0)) {skip = false;}
	}

	/* check against ignore classes */
	if (!skip && teleporter.ignore.length) {
		for (i in teleporter.ignore) {
			if (el.matches(teleporter.ignore[i])) {skip = true;}
		}
	}
	
	if (teleporter.debug) {if (!skip) {console.log('Found internal URL: '+u);} }
	return skip;
}

/* maybe Force External to New Window */
/* 1.1.3: added this check */
function teleporter_maybe_externalize(el) {
	
	/* ignore links with a target already set */
	if ((typeof el.target != 'undefined') && (el.target != '')) {return 1;}

	/* ignore an undefined/empty href */
	if ((typeof el.href == 'undefined') || (el.href == '')) {return 2;}

	/* set href shortname */
	u = el.href;

	/* 1.0.1: always treat javascript, mailto and tel at position 0 as external */
	if ((u.indexOf('javascript') === 0) || (u.indexOf('mailto') === 0) || (u.indexOf('tel') === 0) || (u.indexOf('sms') === 0)) {return 3;}

	/* treat hash or query at position 0 as internal */
	if ((u.indexOf('#') === 0) || (u.indexOf('?') === 0)) {return 4;}

	/* check against site URL */
	if ((teleporter.siteurl != '') && (u.indexOf(teleporter.siteurl) === 0)) {return 5;}

	/* check against host/protocol */
	if (el.host == t_topwin.location.host) {
		a = t_topwin.location.protocol+'//'+t_topwin.location.host;
		b = '//'+t_topwin.location.host;
		if ((u.indexOf(a) === 0) || (u.indexOf(b) === 0)) {return 6;}
	}

	/* set target to _blank to open in new window */
	if (teleporter.debug) {console.log('Found external URL: '+u);}
	el.setAttribute('target', '_blank');
	return false;
}

/* --- Get Top Window (Accessible) --- */
function teleporter_top_window() {
	try {test = window.top.location; return window.top;} catch(e) {
		return teleporter_get_window_parent(window.self);
	}
}

/* --- Got Accessible Window Parent Recursively --- */
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

/* --- Add Link Click Events --- */
function teleporter_add_link_events() {

	/* 1.1.0: get all a tag click events */
	if (typeof findEventHandlers != 'undefined') {
		var t_click_events = findEventHandlers('click','a');
	}
	jQuery('a').each(function() {
		/* 1.0.0: use standard link checking function */
		el = jQuery(this)[0];
		skip = teleporter_skip_link(el);
		if (!skip) {
			/* 1.0.4: ignore events with existing click handler */
			/* ev = jQuery._data(el, 'events');
			if (ev && ev.click && teleporter.debug) {console.log(ev.click);} */
			/* 1.1.0: click event handlers now checked in teleporter_skip_link */
			el.setAttribute('teleporter','1');
			/* 1.0.4: add event listener to append to existing events */
			teleporter_add_link_event(el);
		} else if (teleporter.externalize) {
			code = teleporter_maybe_externalize(el);
			if (code && teleporter.debug) {console.log(el.href+': '+code);}
		}
	});
}

/* --- Add Links within Element by ID --- */
/* 1.1.1: added for easy updating on AJAX loads */
function teleporter_add_links_in_element(el_id) {
	jQuery('#'+el_id+' a').each(function() {
		el = jQuery(this)[0];
		skip = teleporter_skip_link(el);
		if (!skip) {
			el.setAttribute('teleporter','1');
			teleporter_add_link_event(el);
		} else if (teleporter.externalize) {
			code = teleporter_maybe_externalize(el);
			if (code && teleporter.debug) {console.log(el.href+': '+code);}
		}
	});
}

/* --- Add Links to Elements within Class --- */
/* 1.1.1: added for easy updating on AJAX loads */
function teleporter_add_links_in_class(classname) {
	jQuery('.'+classname+' a').each(function() {
		el = jQuery(this)[0];
		skip = teleporter_skip_link(el);
		if (!skip) {
			el.setAttribute('teleporter','1');
			teleporter_add_link_event(el);
		} else if (teleporter.externalize) {
			code = teleporter_maybe_externalize(el);
			if (code && teleporter.debug) {console.log(el.href+': '+code);}
		}
	});
}
	
/* --- Add Link Click Event --- */
function teleporter_add_link_event(el) {
	el.addEventListener('click', function(e) {
		e.stopImmediatePropagation();
		e.preventDefault();
		target = jQuery(e.target);
		if (target.prop('tagName') != 'a') {target = target.closest('a');}
		el = target[0];
		return teleporter_transition_page(el.href);
	});
}

/* --- Add Onclick Attribute to Links --- */
function teleporter_add_link_onclicks() {
	alinks = document.getElementsByTagName('a');
	for (var i = 0; i < alinks.length; i++) {
		/* 1.0.0: use standard link checking function */
		skip = teleporter_skip_link(alinks[i]);
		if (!skip) {
			alinks[i].setAttribute('teleporter', '1');
			teleporter_add_link_onclick(alinks[i]);
			
			/* TODO: could find parent a tag if not clicked element ? */
			/* alinks[i].addEventListener('click', function(e) {
				e.stopImmediatePropagation();
				e.preventDefault();
				return teleporter_transition_page(e.target.href);
			}); */
		} else if (teleporter.externalize) {
			code = teleporter_maybe_externalize(el);
			if (code && teleporter.debug) {console.log(el.href+': '+code);}
		}
	}
}

/* --- Add Link Onclick Attribute --- */
function teleporter_add_link_onclick(el) {
	/* TODO: set click event instead of attribute? */
	el.setAttribute('onclick', 'return teleporter_transition_link(this);');
}

/* --- Add Dynamic Link Clicks --- */
/* 1.0.4: added event delegation clicks for dynamic link classes */
/* 1.0.6: allow for any selectors not just classes */
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
		el = target[0];
		skip = teleporter_skip_link(el);
		if (!skip) {
			if (teleporter.debug) {console.log(target);}
			return teleporter_transition_page(el.href);	
		}
	});
}


/* --- Add Onclick Loading to Page Links --- */
if (typeof window.jQuery !== 'undefined') {

	/* add onclicks to links with jQuery */
	jQuery(document).ready(function() {

		if (!teleporter.iframe) {return;}

		/* make current iframe scroll */
		if (parent.document) {document.getElementsByTagName('body')[0].style.overflow = 'scroll';}

		/* loop all links to add onclick attribute */
		teleporter_custom_event('teleporter-check-links', false);
		teleporter_add_link_events();
		/* 1.0.5: load dynamic link clicks automatically */
		teleporter_dynamic_link_clicks();
		teleporter_custom_event('teleporter-links-checked', false);
		teleporter_transition_check(false, window);
		teleporter_add_popstate_checker();

		/* 1.0.4: try to account for links added later */
		setTimeout(function() {teleporter_add_link_events();}, 5000);
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
		teleporter_add_link_onclicks();
		/* teleporter_dynamic_link_clicks(); (currently jQuery only) */
		teleporter_custom_event('teleporter-links-checked', false);

		teleporter_transition_check(false, window);
		teleporter_add_popstate_checker();

		setTimeout(function() {teleporter_add_link_onclicks();}, 5000);
	});
}

/* --- Dispatch Custom Event --- */
function teleporter_custom_event(name, detail) {
	params = {bubbles: false, cancelable: false, detail: detail};
	var event = new CustomEvent(name, params); document.dispatchEvent(event);
	/* if (teleporter.debug) {console.log('Teleporter Custom Event: '+name); console.log(detail);} */
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

/* --- Remove Window State ID on Unload --- */
addEventListener('unload', function(event) {
	t_topwin.windowstateid = 'undefined';
}, false);

/* --- Detect Escape Key Press --- */
/* ref: https://stackoverflow.com/a/64446856 */
document.addEventListener('keydown', (event) => {
    if (t_topwin.t_loading && (event.key === 'Escape')) {
        const isNotCombinedKey = !(event.ctrlKey || event.altKey || event.shiftKey);
        if (isNotCombinedKey) {
            if (teleporter.debug) {console.log('Cancelling Page Transition.');}
			t_topwin.t_cancel = true; /* cancel transition check */
			t_topwin.t_loading = false; /* cancels loader timeout */
			teleporter_hide_loading();
			/* 1.1.3: close dialog if open */
			if (jQuery('#teleporter-prompt-modal').data('ui-dialog')) {
				jQuery('#teleporter-prompt-modal').dialog('close');
			}
        }
    }
});

/* --- Find Event Handlers --- */
/* ref: https://github.com/ruidfigueiredo/findHandlersJS */
/* 1.1.0: added here to avoid need to load separately */
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
    if (jqSelector === "*") /* does not include document and we might be interested in handlers registered there */
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
                    $elementsCovered = $(event.selector, element); /* only look at children of the element, since those are the only ones the handler covers */
                } else {
                    $elementsCovered = $(element); /* just itself */
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
