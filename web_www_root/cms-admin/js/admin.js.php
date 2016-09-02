<?php

    define ('ROOT_PATH', str_replace('cms-admin\js' , '',  str_replace('cms-admin/js' , '', realpath(dirname(__FILE__)))));
    require_once(ROOT_PATH.'/cms-includes/defines.inc.php');
    require_once ROOT_PATH.'/cms-includes/functions.inc.php';
    require_once(ROOT_PATH.'/cms-includes/init.inc.php');


    // any custom defines for this site
    if (is_file(ROOT_PATH.'cms-includes/site.defines.inc.php'))
	require_once ROOT_PATH.'/cms-includes/site.defines.inc.php';

    // any custom functions for this site
    if (is_file(ROOT_PATH.'cms-includes/site.functions.inc.php'))
	require_once ROOT_PATH.'/cms-includes/site.functions.inc.php';
    
    $user = lum_call('Users', 'isSignedIn');
    header("content-type: application/x-javascript");
?>

var perms = {};

<?php

$perms = unserialize(base64_decode($user->permissions));

foreach ($perms as $perm)
{
    echo 'perms[\''.addslashes($perm).'\'] = 1;'."\r\n";
}
?>

var SITE_PATH = '/';
var TOOLS_PATH = '<?=TOOLS_PATH?>/<?=TOOLS_PAGE?>';
var RPC_URL = "<?=BASE_URL_OFFSET?>cms-admin/admin_service.php";
var jRpc;
var REMEMBER_START = <?=(isset($_SESSION['REMEMBER_START']) ? $_SESSION['REMEMBER_START'] : 0)?>;

$(function()
{
	jRpc = $.jRpc({
		rpc_url: RPC_URL
	});	
	if(typeof initPlugin == 'function') { 
		initPlugin();
		lum_setupCollapsibleFieldsets();
	} 
});

function function_exists (function_name) {
    if (typeof function_name == 'string') {
        return (typeof this.window[function_name] == 'function');
    } else {
        return (function_name instanceof Function);
    }
}

function lum_hasPermission(perm)
{
    if (lum_isDefined(perms['Users\\Super User']))
	    return true;

    // doe this user have the 'All' permission for this permission group
    var temp = perm.split('\\');
    var perm_all = temp[0]+'\\All';
    if (lum_isDefined(perms[perm_all]))
	return true;
	
    if (lum_isDefined(perms[perm]))
	return true;
	
    return false;
}

function lum_setupCollapsibleFieldsets()
{
	$('.collapsible').each(function(){
		if ($(this).hasClass('collapsed'))
		{
			$(this).parent().children().not('legend').toggle();
		}
	})
	
	$('.collapsible').click(function(){
		if ($(this).hasClass('collapsed'))
		{
			$(this).removeClass('collapsed');
		}
		else
		{
			$(this).addClass('collapsed');
		}
		
		$(this).parent().children().not('legend').toggle();
	});
}

function lum_alert(title, msg)
{
	$("#admin-dialog:ui-dialog").dialog( "destroy" );
	$("#admin-dialog").attr('title', title);
	$("#admin-dialog").html(msg);
	lum_dialog();
}

function lum_prompt(title, msg, buttons)
{
	$("#admin-dialog:ui-dialog").dialog( "destroy" );
	$("#admin-dialog").attr('title', title);
	$("#admin-dialog").html(msg);
	lum_dialog(buttons);
}

function lum_confirm(title, msg, buttons)
{
	$("#admin-dialog:ui-dialog").dialog( "destroy" );
	$("#admin-dialog").attr('title', title);
	$("#admin-dialog").html(msg);
	lum_dialog(buttons);
}

function lum_dialog(buttons)
{
	$( "#admin-dialog" ).dialog({
		resizable: false,
		height: 'auto',
		modal: true,
		buttons: buttons
	});
}

function checkForTimeout(o)
{
	if (o.success == true && o.session_timeout != undefined)
	{
		$.jGrowl("Your session has timed out. Please refresh the page to log back in.", {sticky: true});
		return true;
	}
	return false;
}

function createObjectCallback(obj, fn)
{
    return function() { fn.apply(obj, arguments); };
}


function Get_Cookie( name ) 
{
	var start = document.cookie.indexOf( name + "=" );
	var len = start + name.length + 1;
	if ( ( !start ) &&
	( name != document.cookie.substring( 0, name.length ) ) )
	{
	return null;
	}
	if ( start == -1 ) return null;
	var end = document.cookie.indexOf( ";", len );
	if ( end == -1 ) end = document.cookie.length;
	return unescape( document.cookie.substring( len, end ) );
}	


function strtotime (str, now) {
    // http://kevin.vanzonneveld.net
    // +   original by: Caio Ariede (http://caioariede.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: David
    // +   improved by: Caio Ariede (http://caioariede.com)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Wagner B. Soares
    // +   bugfixed by: Artur Tchernychev
    // %        note 1: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
    // *     example 1: strtotime('+1 day', 1129633200);
    // *     returns 1: 1129719600
    // *     example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
    // *     returns 2: 1130425202
    // *     example 3: strtotime('last month', 1129633200);
    // *     returns 3: 1127041200
    // *     example 4: strtotime('2009-05-04 08:30:00');
    // *     returns 4: 1241418600
 
    var i, match, s, strTmp = '', parse = '';

    strTmp = str;
    strTmp = strTmp.replace(/\s{2,}|^\s|\s$/g, ' '); // unecessary spaces
    strTmp = strTmp.replace(/[\t\r\n]/g, ''); // unecessary chars

    if (strTmp == 'now') {
        return (new Date()).getTime()/1000; // Return seconds, not milli-seconds
    } else if (!isNaN(parse = Date.parse(strTmp))) {
        return (parse/1000);
    } else if (now) {
        now = new Date(now*1000); // Accept PHP-style seconds
    } else {
        now = new Date();
    }

    strTmp = strTmp.toLowerCase();

    var __is =
    {
        day:
        {
            'sun': 0,
            'mon': 1,
            'tue': 2,
            'wed': 3,
            'thu': 4,
            'fri': 5,
            'sat': 6
        },
        mon:
        {
            'jan': 0,
            'feb': 1,
            'mar': 2,
            'apr': 3,
            'may': 4,
            'jun': 5,
            'jul': 6,
            'aug': 7,
            'sep': 8,
            'oct': 9,
            'nov': 10,
            'dec': 11
        }
    };

    var process = function (m) {
        var ago = (m[2] && m[2] == 'ago');
        var num = (num = m[0] == 'last' ? -1 : 1) * (ago ? -1 : 1);

        switch (m[0]) {
            case 'last':
            case 'next':
                switch (m[1].substring(0, 3)) {
                    case 'yea':
                        now.setFullYear(now.getFullYear() + num);
                        break;
                    case 'mon':
                        now.setMonth(now.getMonth() + num);
                        break;
                    case 'wee':
                        now.setDate(now.getDate() + (num * 7));
                        break;
                    case 'day':
                        now.setDate(now.getDate() + num);
                        break;
                    case 'hou':
                        now.setHours(now.getHours() + num);
                        break;
                    case 'min':
                        now.setMinutes(now.getMinutes() + num);
                        break;
                    case 'sec':
                        now.setSeconds(now.getSeconds() + num);
                        break;
                    default:
                        var day;
                        if (typeof (day = __is.day[m[1].substring(0, 3)]) != 'undefined') {
                            var diff = day - now.getDay();
                            if (diff == 0) {
                                diff = 7 * num;
                            } else if (diff > 0) {
                                if (m[0] == 'last') {diff -= 7;}
                            } else {
                                if (m[0] == 'next') {diff += 7;}
                            }
                            now.setDate(now.getDate() + diff);
                        }
                }
                break;

            default:
                if (/\d+/.test(m[0])) {
                    num *= parseInt(m[0], 10);

                    switch (m[1].substring(0, 3)) {
                        case 'yea':
                            now.setFullYear(now.getFullYear() + num);
                            break;
                        case 'mon':
                            now.setMonth(now.getMonth() + num);
                            break;
                        case 'wee':
                            now.setDate(now.getDate() + (num * 7));
                            break;
                        case 'day':
                            now.setDate(now.getDate() + num);
                            break;
                        case 'hou':
                            now.setHours(now.getHours() + num);
                            break;
                        case 'min':
                            now.setMinutes(now.getMinutes() + num);
                            break;
                        case 'sec':
                            now.setSeconds(now.getSeconds() + num);
                            break;
                    }
                } else {
                    return false;
                }
                break;
        }
        return true;
    };

    match = strTmp.match(/^(\d{2,4}-\d{2}-\d{2})(?:\s(\d{1,2}:\d{2}(:\d{2})?)?(?:\.(\d+))?)?$/);
    if (match != null) {
        if (!match[2]) {
            match[2] = '00:00:00';
        } else if (!match[3]) {
            match[2] += ':00';
        }

        s = match[1].split(/-/g);

        for (i in __is.mon) {
            if (__is.mon[i] == s[1] - 1) {
                s[1] = i;
            }
        }
        s[0] = parseInt(s[0], 10);

        s[0] = (s[0] >= 0 && s[0] <= 69) ? '20'+(s[0] < 10 ? '0'+s[0] : s[0]+'') : (s[0] >= 70 && s[0] <= 99) ? '19'+s[0] : s[0]+'';
        return parseInt(this.strtotime(s[2] + ' ' + s[1] + ' ' + s[0] + ' ' + match[2])+(match[4] ? match[4]/1000 : ''), 10);
    }

    var regex = '([+-]?\\d+\\s'+
        '(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?'+
        '|sun\\.?|sunday|mon\\.?|monday|tue\\.?|tuesday|wed\\.?|wednesday'+
        '|thu\\.?|thursday|fri\\.?|friday|sat\\.?|saturday)'+
        '|(last|next)\\s'+
        '(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?'+
        '|sun\\.?|sunday|mon\\.?|monday|tue\\.?|tuesday|wed\\.?|wednesday'+
        '|thu\\.?|thursday|fri\\.?|friday|sat\\.?|saturday))'+
        '(\\sago)?';

    match = strTmp.match(new RegExp(regex, 'gi')); // Brett: seems should be case insensitive per docs, so added 'i'
    if (match == null) {
        return false;
    }

    for (i = 0; i < match.length; i++) {
        if (!process(match[i].split(' '))) {
            return false;
        }
    }

    return (now.getTime()/1000);
}

  function mysqlTimeStampToDate(timestamp) {
    //function parses mysql datetime string and returns javascript Date object
    //input has to be in this format: 2007-06-05 15:26:02
    var regex=/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9])?$/;
    var parts=timestamp.replace(regex,"$1 $2 $3").split(' ');
    return new Date(parts[0],parts[1]-1,parts[2]);
  }

function mysqlTimeStampToDateTime(timestamp) {
    //function parses mysql datetime string and returns javascript Date object
    //input has to be in this format: 2007-06-05 15:26:02
    var regex=/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/;
    var parts=timestamp.replace(regex,"$1 $2 $3 $4 $5 $6").split(' ');
    return new Date(parts[0],parts[1]-1,parts[2],parts[3],parts[4],parts[5]);
  }
  
Date.prototype.addDays = function(days) {
	this.setDate(this.getDate()+days);
}

function date(format, timestamp) {
    // http://kevin.vanzonneveld.net
    // +   original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
    // +      parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: MeEtc (http://yass.meetcweb.com)
    // +   improved by: Brad Touesnard
    // +   improved by: Tim Wiel
    // +   improved by: Bryan Elliott
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: David Randall
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +  derived from: gettimeofday
    // +      input by: majak
    // +   bugfixed by: majak
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Alex
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +   improved by: Thomas Beaucourt (http://www.webapp.fr)
    // +   improved by: JT
    // +   improved by: Theriault
    // +   improved by: Rafa Kukawski (http://blog.kukawski.pl)
    // %        note 1: Uses global: php_js to store the default timezone
    // *     example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400);
    // *     returns 1: '09:09:40 m is month'
    // *     example 2: date('F j, Y, g:i a', 1062462400);
    // *     returns 2: 'September 2, 2003, 2:26 am'
    // *     example 3: date('Y W o', 1062462400);
    // *     returns 3: '2003 36 2003'
    // *     example 4: x = date('Y m d', (new Date()).getTime()/1000); 
    // *     example 4: (x+'').length == 10 // 2009 01 09
    // *     returns 4: true
    // *     example 5: date('W', 1104534000);
    // *     returns 5: '53'
    // *     example 6: date('B t', 1104534000);
    // *     returns 6: '999 31'
    // *     example 7: date('W U', 1293750000.82); // 2010-12-31
    // *     returns 7: '52 1293750000'
    // *     example 8: date('W', 1293836400); // 2011-01-01
    // *     returns 8: '52'
    // *     example 9: date('W Y-m-d', 1293974054); // 2011-01-02
    // *     returns 9: '52 2011-01-02'
    var that = this,
        jsdate, f, formatChr = /\\?([a-z])/gi, formatChrCb,
        // Keep this here (works, but for code commented-out
        // below for file size reasons)
        //, tal= [],
        _pad = function (n, c) {
            if ((n = n + "").length < c) {
                return new Array((++c) - n.length).join("0") + n;
            } else {
                return n;
            }
        },
        txt_words = ["Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur",
        "January", "February", "March", "April", "May", "June", "July",
        "August", "September", "October", "November", "December"],
        txt_ordin = {
            1: "st",
            2: "nd",
            3: "rd",
            21: "st", 
            22: "nd",
            23: "rd",
            31: "st"
        };
    formatChrCb = function (t, s) {
        return f[t] ? f[t]() : s;
    };
    f = {
    // Day
        d: function () { // Day of month w/leading 0; 01..31
            return _pad(f.j(), 2);
        },
        D: function () { // Shorthand day name; Mon...Sun
            return f.l().slice(0, 3);
        },
        j: function () { // Day of month; 1..31
            return jsdate.getDate();
        },
        l: function () { // Full day name; Monday...Sunday
            return txt_words[f.w()] + 'day';
        },
        N: function () { // ISO-8601 day of week; 1[Mon]..7[Sun]
            return f.w() || 7;
        },
        S: function () { // Ordinal suffix for day of month; st, nd, rd, th
            return txt_ordin[f.j()] || 'th';
        },
        w: function () { // Day of week; 0[Sun]..6[Sat]
            return jsdate.getDay();
        },
        z: function () { // Day of year; 0..365
            var a = new Date(f.Y(), f.n() - 1, f.j()),
                b = new Date(f.Y(), 0, 1);
            return Math.round((a - b) / 864e5) + 1;
        },

    // Week
        W: function () { // ISO-8601 week number
            var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3),
                b = new Date(a.getFullYear(), 0, 4);
            return 1 + Math.round((a - b) / 864e5 / 7);
        },

    // Month
        F: function () { // Full month name; January...December
            return txt_words[6 + f.n()];
        },
        m: function () { // Month w/leading 0; 01...12
            return _pad(f.n(), 2);
        },
        M: function () { // Shorthand month name; Jan...Dec
            return f.F().slice(0, 3);
        },
        n: function () { // Month; 1...12
            return jsdate.getMonth() + 1;
        },
        t: function () { // Days in month; 28...31
            return (new Date(f.Y(), f.n(), 0)).getDate();
        },

    // Year
        L: function () { // Is leap year?; 0 or 1
            var y = f.Y(), a = y & 3, b = y % 4e2, c = y % 1e2;
            return 0 + (!a && (c || !b));
        },
        o: function () { // ISO-8601 year
            var n = f.n(), W = f.W(), Y = f.Y();
            return Y + (n === 12 && W < 9 ? -1 : n === 1 && W > 9);
        },
        Y: function () { // Full year; e.g. 1980...2010
            return jsdate.getFullYear();
        },
        y: function () { // Last two digits of year; 00...99
            return (f.Y() + "").slice(-2);
        },

    // Time
        a: function () { // am or pm
            return jsdate.getHours() > 11 ? "pm" : "am";
        },
        A: function () { // AM or PM
            return f.a().toUpperCase();
        },
        B: function () { // Swatch Internet time; 000..999
            var H = jsdate.getUTCHours() * 36e2, // Hours
                i = jsdate.getUTCMinutes() * 60, // Minutes
                s = jsdate.getUTCSeconds(); // Seconds
            return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
        },
        g: function () { // 12-Hours; 1..12
            return f.G() % 12 || 12;
        },
        G: function () { // 24-Hours; 0..23
            return jsdate.getHours();
        },
        h: function () { // 12-Hours w/leading 0; 01..12
            return _pad(f.g(), 2);
        },
        H: function () { // 24-Hours w/leading 0; 00..23
            return _pad(f.G(), 2);
        },
        i: function () { // Minutes w/leading 0; 00..59
            return _pad(jsdate.getMinutes(), 2);
        },
        s: function () { // Seconds w/leading 0; 00..59
            return _pad(jsdate.getSeconds(), 2);
        },
        u: function () { // Microseconds; 000000-999000
            return _pad(jsdate.getMilliseconds() * 1000, 6);
        },

    // Timezone
        e: function () { // Timezone identifier; e.g. Atlantic/Azores, ...
// The following works, but requires inclusion of the very large
// timezone_abbreviations_list() function.
/*              var abbr = '', i = 0, os = 0;
            if (that.php_js && that.php_js.default_timezone) {
                return that.php_js.default_timezone;
            }
            if (!tal.length) {
                tal = that.timezone_abbreviations_list();
            }
            for (abbr in tal) {
                for (i = 0; i < tal[abbr].length; i++) {
                    os = -jsdate.getTimezoneOffset() * 60;
                    if (tal[abbr][i].offset === os) {
                        return tal[abbr][i].timezone_id;
                    }
                }
            }
*/
            return 'UTC';
        },
        I: function () { // DST observed?; 0 or 1
            // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
            // If they are not equal, then DST is observed.
            var a = new Date(f.Y(), 0), // Jan 1
                c = Date.UTC(f.Y(), 0), // Jan 1 UTC
                b = new Date(f.Y(), 6), // Jul 1
                d = Date.UTC(f.Y(), 6); // Jul 1 UTC
            return 0 + ((a - c) !== (b - d));
        },
        O: function () { // Difference to GMT in hour format; e.g. +0200
            var a = jsdate.getTimezoneOffset();
            return (a > 0 ? "-" : "+") + _pad(Math.abs(a / 60 * 100), 4);
        },
        P: function () { // Difference to GMT w/colon; e.g. +02:00
            var O = f.O();
            return (O.substr(0, 3) + ":" + O.substr(3, 2));
        },
        T: function () { // Timezone abbreviation; e.g. EST, MDT, ...
// The following works, but requires inclusion of the very
// large timezone_abbreviations_list() function.
/*              var abbr = '', i = 0, os = 0, default = 0;
            if (!tal.length) {
                tal = that.timezone_abbreviations_list();
            }
            if (that.php_js && that.php_js.default_timezone) {
                default = that.php_js.default_timezone;
                for (abbr in tal) {
                    for (i=0; i < tal[abbr].length; i++) {
                        if (tal[abbr][i].timezone_id === default) {
                            return abbr.toUpperCase();
                        }
                    }
                }
            }
            for (abbr in tal) {
                for (i = 0; i < tal[abbr].length; i++) {
                    os = -jsdate.getTimezoneOffset() * 60;
                    if (tal[abbr][i].offset === os) {
                        return abbr.toUpperCase();
                    }
                }
            }
*/
            return 'UTC';
        },
        Z: function () { // Timezone offset in seconds (-43200...50400)
            return -jsdate.getTimezoneOffset() * 60;
        },

    // Full Date/Time
        c: function () { // ISO-8601 date.
            return 'Y-m-d\\Th:i:sP'.replace(formatChr, formatChrCb);
        },
        r: function () { // RFC 2822
            return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb);
        },
        U: function () { // Seconds since UNIX epoch
            return jsdate.getTime() / 1000 | 0;
        }
    };
    this.date = function (format, timestamp) {
        that = this;
        jsdate = (
            (typeof timestamp === 'undefined') ? new Date() : // Not provided
            (timestamp instanceof Date) ? new Date(timestamp) : // JS Date()
            new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
        );
        return format.replace(formatChr, formatChrCb);
    };
    return this.date(format, timestamp);
}

function in_array (needle, haystack, argStrict) {
	var key = '', strict = !!argStrict;

	if (strict) {
		for (key in haystack) {
			if (haystack[key] === needle) {
				return true;
			}
		}
	} else {
		for (key in haystack) {
			if (haystack[key] == needle) {
				return true;
			}
		}
	}

	return false;
}

function stripslashes (str) {
    return (str+'').replace(/\\(.?)/g, function (s, n1) {
        switch (n1) {
            case '\\':
                return '\\';
            case '0':
                return '\u0000';
            case '':
                return '';
            default:
                return n1;
        }
    });
}

function lum_bulkAction(t, method, ask, growl_text)
{
	var ids = t.getSelected();
	if (ids.length == 0)
	{
		$.jGrowl("Nothing was selected.");
		return;
	}
	
	var buttons = {
			"Confirm": function()
				{
					 _lum_doBulkAction(t, method, ask, growl_text);
				},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}

	if (parseInt(ask))
		lum_confirm("Please Confirm?", "Are you sure you want to "+method+" these items?", buttons);
	else
		_lum_doBulkAction(t, method, ask, growl_text);
}

function _lum_doBulkAction(t, method, ask, growl_text)
{
	var ids = t.getSelected();
	$("#admin-dialog").dialog( "close" );
	$.jGrowl(growl_text);
	jRpc.send(handleResponse, {plugin: t.config.plugin, method: method, params: {ids: ids}});	
}

function lum_changeStatus(id, status, override_method)
{
	method = 'activate';
	if (status == 0)
		method = 'deactivate';
		
	if (lum_isDefined(override_method))
		method = override_method;
		
	var params = {};
	params[data_index] = id;
	
	jRpc.send(handleResponse, {plugin: plugin, method: method, params: params});
}

function lum_delete(name, id, extra_msg, override_method)
{
	method = 'delete';
	if (lum_isDefined(override_method))
		method = override_method;
		
	if (override_method != '')
	if (extra_msg == undefined)
		extra_msg = '';
		
	var params = {};
	params[data_index] = id;
	
	var buttons = {
			"Delete": function()
				{
					$( this ).dialog( "close" );
					$.jGrowl("Deleting...");
					jRpc.send(handleResponse, {plugin: plugin, method: method, params: params});
				},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}

	lum_confirm("Delete this?", "Are you sure you want to delete '"+name+"'?"+extra_msg, buttons);
}

function lum_isDefined(x)
{
	return (typeof(x) != "undefined");
}

// generic handle response function
function lum_handleResponse(o)
{
	if (o.success)
	{
		if (!checkForTimeout(o))
		{
			$.jGrowl("The action was a success");
			if (window.t !== undefined)
				t.load();
		}
	}
	else
	{
		$.jGrowl(o.errors, {sticky: true});
	}	
}

function lum_submitForm(form, callback)
{
	mycallback = handleResponse;
	if (lum_isDefined(callback))
		mycallback = callback;
		
	jRpc.send(mycallback, form.serializeObject());
//	$.post( RPC_URL, form.serializeObject(), callback, "json");
	return false;
}

function lum_doSearch(t)
{
	if ($("#lang_code").length)
		t.config.params['lang_code'] = $("#lang_code").val();
		
	if ($("#search").length)		
		t.config.params['query'] = $("#search").val();
		
	t.config.params['start'] = 0;
	t.load();
}

$.fn.serializeObject = function()
{
   var o = {};
   var a = this.serializeArray();
   o['params'] = {};
   o['plugin'] = '';
   o['method'] = '';
   
   $.each(a, function() {
		if (this.name == 'plugin')
		{
			o['plugin'] = this.value;
		}
		else if (this.name == 'method')
		{
			o['method'] = this.value;
		}
		else
		{
			if (o['params'][this.name])
			{
				if (!o['params'][this.name].push)
				{
					o['params'][this.name] = [o['params'][this.name]];
				}
				o['params'][this.name].push(encodeURI(this.value) || '');
			}
			else
			{
					o['params'][this.name] = encodeURI(this.value) || '';
			}
		}
   });
   return o;
};

function str_replace (search, replace, subject, count) {
    var i = 0,
        j = 0,
        temp = '',
        repl = '',
        sl = 0,
        fl = 0,
        f = [].concat(search),
        r = [].concat(replace),
        s = subject,
        ra = Object.prototype.toString.call(r) === '[object Array]',
        sa = Object.prototype.toString.call(s) === '[object Array]';
    s = [].concat(s);
    if (count) {
        this.window[count] = 0;
    }

    for (i = 0, sl = s.length; i < sl; i++) {
        if (s[i] === '') {
            continue;
        }
        for (j = 0, fl = f.length; j < fl; j++) {
            temp = s[i] + '';
            repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
            s[i] = (temp).split(f[j]).join(repl);
            if (count && s[i] !== temp) {
                this.window[count] += (temp.length - s[i].length) / f[j].length;
            }
        }
    }
    return sa ? s : s[0];
}

	// this is where we set up language tabs other than the default language
	function lum_setupLocalization()
	{
		if ($('.lang_code').length && $('.localize').length)
		{
			$('.lang_code').each(function(){
				var lang_code = $(this).attr('id');
				if (lang_code != def_lang)
				{
					var html = '<fieldset id="content-'+lang_code+'"><legend>Localized Content</legend><table>';
					
					// create the html
					$('.localize').each(function(){
						html += '<tr>'+$(this).html()+'</tr>';
						var id = $(this).children().last().children().attr('id');
						var name = $(this).children().last().children().attr('name');
						html = str_replace('id="'+id+'"', 'id="'+lang_code+'-'+id+'"', html);
						html = str_replace('name="'+name+'"', 'name="'+lang_code+'-'+name+'"', html);

					});
					
					html += '</table></fieldset>';
					$('#tabs').append('<div id="tabs-'+lang_code+'">'+html+'</div>');
					
					// now update the values
					$('.localize').each(function(){
						var id = $(this).children().last().children().attr('id');
						var name = $(this).children().last().children().attr('name');
						var cls = $(this).children().last().children().attr('class');
						
						if ($('#content-'+lang_code+'-'+name).length)
						{
							$('#'+lang_code+'-'+id).val($('#content-'+lang_code+'-'+name).val());
							$('#'+lang_code+'-'+id).addClass(cls);
						}
					});
				}
			});
		}
		
		$( "#tabs" ).tabs();
	}
	
	function lum_loadTinyMce()
	{
		$('textarea.tinymce').tinymce({
			// Location of TinyMCE script
			script_url : '/cms-admin/tiny_mce/tiny_mce.js',
			imagemanager_path : "{0}/images",
			filemanager_path : "{0}/files",
			urlconverter_callback : 'lum_convertTinyMceUrl',
			// General options
			theme : "advanced",
			plugins : "<?=lum_getOption('TinyMCE Plugins')?>",

			// Theme options
			theme_advanced_buttons1 : "<?=lum_getOption('TinyMCE Button Row 1')?>",
			theme_advanced_buttons2 : "<?=lum_getOption('TinyMCE Button Row 2')?>",
			theme_advanced_buttons3 : "<?=lum_getOption('TinyMCE Button Row 3')?>",
			theme_advanced_buttons4 : "<?=lum_getOption('TinyMCE Button Row 4')?>",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,
			extended_valid_elements : "<?=lum_getOption('TinyMCE Valid Elements')?>",
			external_link_list_url : TOOLS_PATH+"/Pages/external-links"
		});
	}
	
	function lum_convertTinyMceUrl(url, node, on_save)
	{
	    var test = url.toLowerCase();
	    if (test.indexOf('.jpg') == -1 &&
		test.indexOf('.png') == -1 &&
		test.indexOf('.gif') == -1)
		    return url;
		    
	    var pathExtract = /^[a-z]+:\/\/\/?[^\/]+(\/[^?]*)/i;
	    var temp = (pathExtract.exec(url));
	    if (temp)
		return '/'+temp[1].substr(1);
	    else
		return url;

	}
	

    function number_format (number, decimals, dec_point, thousands_sep) {
	number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	var n = !isFinite(+number) ? 0 : +number,
	    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
	    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
	    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
	    s = '',
	    toFixedFix = function (n, prec) {
		var k = Math.pow(10, prec);
		return '' + Math.round(n * k) / k;
	    };
	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
	s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	if (s[0].length > 3) {
	    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	}
	if ((s[1] || '').length < prec) {
	    s[1] = s[1] || '';
	    s[1] += new Array(prec - s[1].length + 1).join('0');
	}
	return s.join(dec);
    }
    
    function lum_renderMoney(value, record, css)
    {
	    return '$'+number_format(value, 2);
    }
    
    function addslashes (str) {
	str = (str + '').replace(/\&\#039\;/g, '\'');
	return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }    
