/* Script:  [[User:TheDJ/Gadget-HotCat.js]]
  * HotCat: Adds an easy way to add, modify and remove categories 
  * Documentation: [[User:TheDJ/HotCat]]
  * Originally written by: Magnus Manske
  * 
  * This version was forked from http://commons.wikimedia.org/w/index.php?title=MediaWiki:Gadget-HotCat.js&oldid=10204404
  * In sync with version: http://commons.wikimedia.org/w/index.php?title=MediaWiki:Gadget-HotCat.js&oldid=19600669
  * Major changes:
  *   - all code for the uploadForm has been removed
  *   - autocommit is disabled
  *   - will be enabled on pages without categories so that you can easily add them
  *   - uses javascript:void() as a dummy value for href in order to avoid a conflict with popups.
  *   - checks for {{Uncategorized}} and removes it if a category is added
  *   - does not use JSconfig for configuration options like its Commons original
  *   - tries to detect other categories and if possible, add to the end of them.
  *   - fixes a bug in the suggestion list with titles containing : character
  *   - Uses opensearch API to look for categories. Allows for case insensitive search.
  *   - Postfix blacklisting in addition to prefix blacklisting.
  * [[User:TheDJ]] 2009-04-18
  <source lang="javascript"><nowiki> */
  
/**
 * Modified version for Dutch projects by [[:m:User:Erwin]]
 * This version was forked from http://en.wikipedia.org/w/index.php?title=User:TheDJ/Gadget-HotCat.js&oldid=321175546
 * Major changes:
 *   - using Dutch as main language
 *   - if the category is not found try if it's caused by {{Uncategorized}}
 *   - checking for interwiki's in hotcat_find_ins().
 */
 
var hotcat_running = 0 ;
var hotcat_last_v = "" ;
var hotcat_exists_yes = "http://upload.wikimedia.org/wikipedia/commons/thumb/b/be/P_yes.svg/20px-P_yes.svg.png" ;
var hotcat_exists_no = "http://upload.wikimedia.org/wikipedia/commons/thumb/4/42/P_no.svg/20px-P_no.svg.png" ;

var hotcat_no_autocommit = 0;
// In Commons hotcat_suggestion_delay is configurable trough JSconfig
var hotcat_suggestion_delay = 100;

var hotcat_old_onsubmit = null;
var hotcat_nosuggestions = false;
// hotcat_nosuggestions is set to true if we don't have XMLHttp! (On IE6, XMLHttp uses
// ActiveX, and the user may deny execution.) If true, no suggestions will ever be
// displayed, and there won't be any checking whether the category  exists.
// Lupo, 2008-01-20

var hotcat_modify_blacklist = new Array (
) ;

var hotcat_cnames=["[Cc]ategorie", "[Cc]ategory"]; // namespaces and alias of category
                                   // in chinese: categoryNames=["[Cc]ategory","分类","分類"];

var hotcat_uncat_regex = /\{\{\s*[Nn]ocat[^}]*\}\}\n/gm;

var hotcat_iw_prefixes = '|aa|ab|af|ak|als|am|an|ang|ar|arc|arz|as|ast|av|ay|az|ba|bar|bat-smg|bcl|be|be-x-old|bg|bh|bi|bm|bn|bo|bpy|br|bs|bug|bxr|ca|cbk-zam|cdo|ce|ceb|ch|cho|chr|chy|closed-zh-tw|co|cr|crh|cs|csb|cu|cv|cy|cz|da|de|diq|dk|dsb|dv|dz|ee|el|eml|en|eo|epo|es|et|eu|ext|fa|ff|fi|fiu-vro|fj|fo|fr|frp|fur|fy|ga|gan|gd|gl|glk|gn|got|gu|gv|ha|hak|haw|he|hi|hif|ho|hr|hsb|ht|hu|hy|hz|ia|id|ie|ig|ii|ik|ilo|io|is|it|iu|ja|jbo|jp|jv|ka|kaa|kab|kg|ki|kj|kk|kl|km|kn|ko|kr|ks|ksh|ku|kv|kw|ky|la|lad|lb|lbe|lg|li|lij|lmo|ln|lo|lt|lv|map-bms|mdf|mg|mh|mi|minnan|mk|ml|mn|mo|mr|ms|mt|mus|my|myv|mzn|na|nah|nan|nap|nb|nds|nds-nl|ne|new|ng|nl|nn|no|nomcom|nov|nrm|nv|ny|oc|om|or|os|pa|pag|pam|pap|pdc|pi|pih|pl|pms|pnt|ps|pt|qu|rm|rmy|rn|ro|roa-rup|roa-tara|ru|rw|sa|sah|sc|scn|sco|sd|se|sg|sh|si|simple|sk|sl|sm|sn|so|sq|sr|srn|ss|st|stq|su|sv|sw|szl|ta|te|tet|tg|th|ti|tk|tl|tn|to|tokipona|tp|tpi|tr|ts|tt|tum|tw|ty|udm|ug|uk|ur|uz|ve|vec|vi|vls|vo|wa|war|wo|wuu|xal|xh|yi|yo|za|zea|zh|zh-cfr|zh-classical|zh-min-nan|zh-yue|zu|zh-cn|';

addOnloadHook ( hotcat ) ;

function hotcat () {
  if ( hotcat_check_action() ) return ; // Edited page, reloading anyway

  // Do not add interface to protected pages, if user has no edit permission
  // Also disable it on preview pages: on a preview, we *are* already editing,
  // and HotCat must not open the page for editing a second time. Lupo, 2008-02-27
  if( wgAction != "view" || document.getElementById('ca-viewsource' ) != null ||
      wgNamespaceNumber == -1 || wgNamespaceNumber == 10 )
    return;

  // If we have no Categories div, then add one
  // TheDJ, 2008-02-28
  
  var visible_catlinks = document.getElementById ('mw-normal-catlinks') || getElementsByClassName ( document , "p" , "catlinks" ) [0];
  var hidden_catlinks = document.getElementById ('mw-hidden-catlinks');

  if ( visible_catlinks == null || typeof( visible_catlinks ) == 'undefined' ) {
    d3 = document.createElement ( "div" );
    d3.id = "mw-normal-catlinks";
    d3.innerHTML = '<a href="/wiki/Special:Categories" title="Special:Categories">Categorieën</a>: ';
    visible_catlinks = d3;

    if ( hidden_catlinks ) {
      // There are hidden categories.
      hidden_catlinks.parentNode.insertBefore( d3, hidden_catlinks );
      hidden_catlinks.parentNode.className = "catlinks";
    } else {
      // This page has no categories at all, lets create a section where we can add them.
      var footer = getElementsByClassName ( document , "div" , "printfooter" ) [0];
      if( !footer ) return; // We have no idea where we should add this.

      d1 = document.createElement ( "div" );
      d1.id = "catlinks";
      d1.className = "catlinks";
      d1.appendChild ( d3 );
      footer.parentNode.insertBefore( d1, footer.nextSibling );
    } 
  }

  hotcat_modify_existing ( visible_catlinks ) ;
  hotcat_append_add_span ( visible_catlinks ) ;
}

function hotcat_append_add_span ( catline ) {
  var span_add = document.createElement ( "span" ) ;
  var span_sep = document.createTextNode ( " | " ) ;
  if ( catline.getElementsByTagName("span")[0] ) catline.appendChild ( span_sep ) ;
  catline.appendChild ( span_add ) ;
  hotcat_create_span ( span_add ) ;
}

String.prototype.ucFirst = function () {
   return this.substr(0,1).toUpperCase() + this.substr(1,this.length);
}

function hotcat_is_on_blacklist ( cat_title ) {
  if ( !cat_title ) return 0 ;
  for ( var i = 0 ; i < hotcat_modify_blacklist.length ; i++ ) {
  	/* prefix */
    if ( cat_title.substr ( 0 , hotcat_modify_blacklist[i].length ) 
           == hotcat_modify_blacklist[i] ) return 1 ;
    /* postfix */
    var postfix_len = cat_title.length - hotcat_modify_blacklist[i].length;
    if ( postfix_len >= 0 && cat_title.substr ( postfix_len, hotcat_modify_blacklist[i].length ) 
           == hotcat_modify_blacklist[i] ) return 1 ;
  }
  return 0 ;
}

function hotcat_modify_span ( span , i ) {
  //var cat_title = span.firstChild.getAttribute ( "title" ) ;
  // This fails with MW 1.13alpha if the category is a redlink, because MW 1.13alpha appends
  // [[MediaWiki:Red-link-title]] to the category name... it also fails if the category name
  // contains "&" (because that is represented by &amp; in the XHTML both in the title and in
  // the link's content (innerHTML). Extract the category name from the href instead:
  var cat_title = null;
  var classes   = span.firstChild.getAttribute ('class');
  if (classes && classes.search (/\bnew\b/) >= 0) {  // href="/w/index.php?title=...&action=edit"
    cat_title = hotcatGetParamValue ('title', span.firstChild.href);
  } else { // href="/wiki/..."
    var re = new RegExp (wgArticlePath.replace (/\$1/, '(.*)'));
    var matches = re.exec (span.firstChild.href);
    if (matches && matches.length > 1)
      cat_title = decodeURIComponent (matches[1]);
    else
      return;
  }
  // Strip namespace, replace _ by blank
  cat_title = cat_title.substring (cat_title.indexOf (':') + 1).replace (/_/g, ' ');

  var sep1 = document.createTextNode ( " " ) ;
  var a1 = document.createTextNode ( "(-)" ) ;
  var remove_link = document.createElement ( "a" ) ;
  // Set the href to a dummy value to make sure we don't move if somehow the onclick handler
  // is bypassed.
  remove_link.className = "noprint";
  remove_link.href = "#catlinks";
  remove_link.onclick = hotcat_remove;
  remove_link.appendChild ( a1 ) ;
  span.appendChild ( sep1 ) ;
  span.appendChild ( remove_link ) ;

  if ( hotcat_is_on_blacklist ( cat_title ) ) return ;
  var mod_id = "hotcat_modify_" + i ;
  var sep2 = document.createTextNode ( " " ) ;
  var a2 = document.createTextNode ( "(±)" ) ;
  var modify_link = document.createElement ( "a" ) ;
  modify_link.id = mod_id ;
  modify_link.className = "noprint";
  modify_link.href = "javascript:hotcat_modify(\"" + mod_id + "\");" ;
  modify_link.appendChild ( a2 ) ;
  span.appendChild ( sep2 ) ;
  span.appendChild ( modify_link ) ;
  span.hotcat_name = cat_title; //Store the extracted category name in our own new property of the span DOM node
}

function hotcat_modify_existing ( catline ) {
  var spans = catline.getElementsByTagName ( "span" ) ;
  for ( var i = 0 ; i < spans.length ; i++ ) {
    hotcat_modify_span ( spans[i] , i ) ;
  }
}

function hotcat_getEvt (evt) {
  return evt || window.event || window.Event; // Gecko, IE, Netscape
}
  
function hotcat_evt2node (evt) {
  var node = null;
  try {
    var e = hotcat_getEvt (evt);
    node = e.target;
    if (!node) node = e.srcElement;
  } catch (ex) {
    node = null;
  }
  return node;
}

function hotcat_evtkeys (evt) {
  var code = 0;
  try {
    var e = hotcat_getEvt (evt);
    if (typeof(e.ctrlKey) != 'undefined') { // All modern browsers
      if (e.ctrlKey)  code |= 1;
      if (e.shiftKey) code |= 2;
      if (e.altKey) code |= 4;
    } else if (typeof (e.modifiers) != 'undefined') { // Netscape...
      if (e.modifiers & Event.CONTROL_MASK) code |= 1;
      if (e.modifiers & Event.SHIFT_MASK)   code |= 2;
      if (e.modifiers & Event.ALT_MASK)   code |= 4;
    }
  } catch (ex) {
  }
  return code;
}

function hotcat_killEvt (evt)
{
  try {
    var e = hotcat_getEvt (evt);
    if (typeof (e.preventDefault) != 'undefined') {
      e.preventDefault();
      e.stopPropagation()
    } else
      e.cancelBubble = true;
  } catch (ex) {
  }
}
  
function hotcat_remove (evt) {
  var node = hotcat_evt2node (evt);
  if (!node) return false;
  // Get the category name from the original link to the category
  var cat_title = node.parentNode.hotcat_name;
  
  var editlk = wgServer + wgScript + '?title=' + encodeURIComponent (wgPageName) + '&action=edit';
  if ((hotcat_evtkeys (evt) & 1) || (hotcat_evtkeys (evt) & 4 )) // CTRL or ALT pressed?
    editlk = editlk + '&hotcat_nocommit=1';
  hotcat_killEvt (evt);
  document.location = editlk + '&hotcat_removecat=' + encodeURIComponent(cat_title) ;
  return false;
}

function hotcatGetParamValue(paramName, h) {
  if (typeof h == 'undefined' ) { h = document.location.href; }
  var cmdRe=RegExp('[&?]'+paramName+'=([^&]*)');
  var m=cmdRe.exec(h);
  if (m) {
    try {
      return decodeURIComponent(m[1]);
    } catch (someError) {}
  }
  return null;
}

// New. Code by Lupo & Superm401, added by Lupo, 2008-02-2007
function hotcat_find_category (wikitext, category)
{
  var cat_name  = category.replace(/([\\\^\$\.\?\*\+\(\)])/g, "\\$1");
  var initial   = cat_name.substr (0, 1);
  var cat_regex = new RegExp ("\\[\\[\\s*(?:" + hotcat_cnames.join("|") + ")\\s*:\\s*"
                              + (initial == "\\"
                                 ? initial
                                 : "[" + initial.toUpperCase() + initial.toLowerCase() + "]")
                              + cat_name.substring (1).replace (/[ _]/g, "[ _]")
                              + "\\s*(\\|.*?)?\\]\\]", "g"
                             );
  var result = new Array ();
  var curr_match  = null;
  while ((curr_match = cat_regex.exec (wikitext)) != null) {
    result [result.length] = {match : curr_match};
  }
  return result; // An array containing all matches, with positions, in result[i].match
}

// New. Code by TheDJ, 2008-03-12
// Modified. Code by Erwin, 2008-07-01
function hotcat_find_ins ( wikitext )
{
  var re = new RegExp("\\[\\[\\s*(?:" + hotcat_cnames.join("|") + ")\\s*:\[^\\]\]+\\]\\]", "ig" );
  var index = -1;
  while( re.exec(wikitext) != null ) index = re.lastIndex;
  
  if( index > -1) return index;
  
  //Find interwiki's
  var reiw = /\[\[([^\]:]+):[^\]]+\]\]/ig
  m = reiw.exec(wikitext);
  while(m) {
    if (hotcat_iw_prefixes.indexOf('|' + m[1] + '|') != -1) {
      index = m.index;
      break;
    }
    m = reiw.exec(wikitext);
  }
  if( index > -1) return index;
  
  // No luck finding a suitable place
  return -1;
}

// Rewritten (nearly) from scratch. Lupo, 2008-02-27
function hotcat_check_action () {
  var ret = 0;
  if (wgAction != 'edit' || typeof(document.editform) == "undefined" ) return ret; // Not an edit page, so not our business...
  var summary = new Array () ;
  var t = document.editform.wpTextbox1.value ;
  var prevent_autocommit = 0;
  if (   (typeof (hotcat_no_autocommit) != "undefined" && hotcat_no_autocommit)
      || hotcatGetParamValue ('hotcat_nocommit') == '1')
    prevent_autocommit = 1;

  var cat_rm  = hotcatGetParamValue ('hotcat_removecat');
  var cat_add = hotcatGetParamValue ('hotcat_newcat');
  var comment = hotcatGetParamValue ('hotcat_comment') || "";
  var cat_key = hotcatGetParamValue ('hotcat_sortkey');
  if (cat_key != null) cat_key = '|' + cat_key;

  if (cat_rm != null && cat_rm.length > 0) {
    var matches = hotcat_find_category (t, cat_rm);
    if (!matches || matches.length == 0) {
      if (cat_rm.match('^Wikipedia:Nog te categoriseren')) {
        var t2 = t.replace(hotcat_uncat_regex, ""); // Remove "uncat" templates
        if (t2.length != t.length) {
          t = t2;
          ret = 1;
          summary.push ( "{{nocat}} verwijderd" ) ;
        }
      } else {
        alert ('Categorie "' + cat_rm + '" niet gevonden; misschien staat de categorie in een sjabloon?');
        prevent_autocommit = 1;
      }
    } else if (matches.length > 1) {
      alert ('Categorie "' + cat_rm
             + "\" meerdere keren gevonden; weet niet welke te verwijderen.");
      prevent_autocommit = 1;
    } else {
      if (cat_add != null && cat_add.length > 0 && matches[0].match.length > 1)
        cat_key = matches[0].match[1]; // Remember the category key, if any.
      var t1 = t.substring (0, matches[0].match.index);
      var t2 = t.substring (matches[0].match.index + matches[0].match[0].length);
      // Remove whitespace (properly): strip whitespace, but only up to the next line feed.
      // If we then have two linefeeds in a row, remove one. Otherwise, if we have two non-
      // whitespace characters, insert a blank.
      var i = t1.length - 1;
      while (i >= 0 && t1.charAt (i) != '\n' && t1.substr (i, 1).search (/\s/) >= 0) i--;
      var j = 0;
      while (j < t2.length && t2.charAt (j) != '\n' && t1.substr (j, 1).search (/\s/) >= 0) j++;
      if (i >= 0 && t1.charAt (i) == '\n' && j < t2.length && t2.charAt (j) == '\n')
        i--;
      if (i >= 0) t1 = t1.substring (0, i+1); else t1 = "";
      if (j < t2.length) t2 = t2.substring (j); else t2 = "";
      if (t1.length > 0 && t1.substring (t1.length - 1).search (/\S/) >= 0
          && t2.length > 0 && t2.substr (0, 1).search (/\S/) >= 0)
        t1 = t1 + ' ';
      t = t1 + t2;
      summary.push ( "[[:Categorie:" + cat_rm + "]] verwijderd" ) ;
      ret = 1;
    }
  }
  if (cat_add != null && cat_add.length > 0) {
    var matches = hotcat_find_category (t, cat_add);
    if (matches && matches.length > 0) {
      alert ('Categorie "' + cat_add + '" bestaat al; niet toegevoegd.');
      prevent_autocommit = 1;
    } else {
      var insertionpoint = hotcat_find_ins( t );
      var newcatstring = '\[\[Categorie:' + cat_add + (cat_key != null ? cat_key : "") + '\]\]';
      if( insertionpoint > -1 ) {
        if (t.charAt (insertionpoint - 1) != '\n') newcatstring = '\n' + newcatstring;
        if (t.charAt (insertionpoint) != '\n' && t.charAt(insertionpoint + 1) != '\n') newcatstring = newcatstring + '\n\n';
        t = t.substring(0, insertionpoint ) + newcatstring + t.substring( insertionpoint );
      } else {
        t = t + newcatstring;
      }
      summary.push ( "[[:Categorie:" + cat_add + "]] toegevoegd" + comment) ;

      var t2 = t.replace(hotcat_uncat_regex, ""); // Remove "uncategorized" template
      if (t2.length != t.length) {
        t = t2;
        summary.push ( "{{nocat}} verwijderd" ) ;
      }
      ret = 1;
    }
  }
  if (ret) {
    document.editform.wpTextbox1.value = t ;
    document.editform.wpSummary.value = summary.join( "; " )
                                      + " ([[Wikipedia:HotCat|HotCat.js]])" ;
    document.editform.wpMinoredit.checked = true ;
    if (!prevent_autocommit) {
      // Hide the entire edit section so as not to tempt the user into editing...
      var bodyContentId = document.getElementById("bodyContent") //monobook skin
      	|| document.getElementById("mw_contentholder")   // modern skin
      	|| document.getElementById ("article");          // classic skin
      bodyContentId.style.display = "none";
      document.editform.submit();
    }
  }
  return ret;
}

function hotcat_clear_span ( span_add ) {
  while ( span_add.firstChild ) span_add.removeChild ( span_add.firstChild ) ;
}

function hotcat_create_span ( span_add ) {
  hotcat_clear_span ( span_add ) ;
  var a_add = document.createElement ( "a" ) ;
  var a_text = document.createTextNode ( "(+)" ) ;
  span_add.id = "hotcat_add" ;
  a_add.className = "noprint";
  a_add.href = "javascript:hotcat_add_new()" ;
  a_add.appendChild ( a_text ) ;
  span_add.appendChild ( a_add ) ;
}

function hotcat_modify ( link_id ) {
  var link = document.getElementById ( link_id ) ;
  var span = link.parentNode ;
  var catname = span.hotcat_name;

  while ( span.firstChild.nextSibling ) span.removeChild ( span.firstChild.nextSibling ) ;
  span.firstChild.style.display = "none" ;
  hotcat_create_new_span ( span , catname ) ;
  hotcat_last_v = "" ;
  hotcat_text_changed () ; // Update icon
}

function hotcat_add_new () {
  var span_add = document.getElementById ( "hotcat_add" ) ;
  hotcat_clear_span ( span_add ) ;
  hotcat_last_v = "" ;
  hotcat_create_new_span ( span_add , "" ) ;
}

function hotcat_create_new_span ( thespan , init_text ) {
  var form = document.createElement ( "form" ) ;
  form.method = "post" ;
  form.onsubmit = function () { hotcat_ok(); return false; } ; 
  form.id = "hotcat_form" ;
  form.style.display = "inline" ;

  var list = null;
  
  if (!hotcat_nosuggestions) {
    // Only do this if we may actually use XMLHttp...
    list = document.createElement ( "select" ) ;
    list.id = "hotcat_list" ;
    list.onclick = function ()
      {
        var l = document.getElementById("hotcat_list");
        if (l != null)
          document.getElementById("hotcat_text").value = l.options[l.selectedIndex].text;
        hotcat_text_changed();
      };
    list.ondblclick = function (evt)
      {
        var l = document.getElementById("hotcat_list");
        if (l != null)
          document.getElementById("hotcat_text").value = l.options[l.selectedIndex].text;
        // Don't call text_changed here if on upload form: hotcat_ok will remove the list
        // anyway, so we must not ask for new suggestions since show_suggestions might
        // raise an exception if it tried to show a no longer existing list.
        // Lupo, 2008-01-20
        hotcat_text_changed();
        hotcat_ok((hotcat_evtkeys (evt) & 1) || (hotcat_evtkeys (evt) & 4)); // CTRL or ALT pressed?
      };
    list.style.display = "none" ;
  }
  
  var text = document.createElement ( "input" ) ;
  text.size = 40 ;
  text.id = "hotcat_text" ;
  text.type = "text" ;
  text.value = init_text ;
  text.onkeyup = function () { window.setTimeout("hotcat_text_changed();", hotcat_suggestion_delay ); } ;

  var exists = null;
  if (!hotcat_nosuggestions) {
    exists = document.createElement ( "img" ) ;
    exists.id = "hotcat_exists" ;
    exists.src = hotcat_exists_no ;
  }

  var OK = document.createElement ( "input" ) ;
  OK.type = "button" ;
  OK.value = "OK" ;
  OK.onclick = function (evt) { hotcat_ok ((hotcat_evtkeys (evt) & 1) || (hotcat_evtkeys (evt) & 4)); }; // CTRL or ALT pressed?

  var cancel = document.createElement ( "input" ) ;
  cancel.type = "button" ;
  cancel.value = "Cancel" ;
  cancel.onclick = hotcat_cancel ;

  if (list != null) form.appendChild ( list ) ;
  form.appendChild ( text ) ;
  if (exists != null) form.appendChild ( exists ) ;
  form.appendChild ( OK ) ;
  form.appendChild ( cancel ) ;
  thespan.appendChild ( form ) ;
  text.focus () ;
}

function hotcat_ok (nocommit) {
  var text = document.getElementById ( "hotcat_text" ) ;
  var v = text.value || ""; 
  v = v.replace(/_/g, ' ').replace(/^\s\s*/, '').replace(/\s\s*$/, ''); // Trim leading and trailing blanks
  
  // Empty category ?
  if (!v) {
    hotcat_cancel() ;
    return ;
  } else if ( hotcat_is_on_blacklist(v) ) {
  	alert( 'Deze categorie kan enkel via een sjabloon worden toegevoegd.' );
  	return;
  }

  // Get the links and the categories of the chosen category page
  var url = wgServer + wgScriptPath + '/api.php?action=query&titles='
          + encodeURIComponent ('Category:' + v)
          + '&prop=info|links|categories&plnamespace=14&format=json&callback=hotcat_json_resolve';
  var request = sajax_init_object() ;
  if (request == null) {
    //Oops! We don't have XMLHttp...
    hotcat_nosuggestions = true;
    hotcat_closeform (nocommit);
    hotcat_running = 0;
    return;
  }
  request.open ('GET', url, true);
  request.onreadystatechange =
    function () {
      if (request.readyState != 4) return;
      if (request.status != 200) {
        hotcat_closeform (nocommit);
      } else {
        var do_submit = eval (request.responseText);
        var txt = document.getElementById ('hotcat_text');
        if (do_submit) {
          hotcat_closeform (
             nocommit
            ,(txt && txt.value != v) ? " (doorverwijzing \[\[:Categorie:" + v + "|" + v + "\]\] gecorrigeerd)" : null
          );
        }
      }
    };
  request.setRequestHeader ('Pragma', 'cache=yes');
  request.setRequestHeader ('Cache-Control', 'no-transform');
  request.send (null);
}

function hotcat_json_resolve (params)
{
  function resolve (page)
  {
    var cats     = page.categories;
    var is_dab   = false;
    var is_redir = typeof (page.redirect) == 'string'; // Hard redirect?
    if (!is_redir && cats) {
      for (var c = 0; c < cats.length; c++) {
        var cat = cats[c]["title"];
        if (cat) cat = cat.substring (cat.indexOf (':') + 1); // Strip namespace prefix
        if (cat == 'Wikipedia:Doorverwijspagina') {
          is_dab = true; break;
        } else if ( /.*soft.redirected.categories.*/.test( cat ) ) {
          is_redir = true; break;
        }
      }
    }
    if (!is_redir && !is_dab) return true;
    var lks = page.links;
    var titles = new Array ();
    for (i = 0; i < lks.length; i++) {
      if (   lks[i]["ns"] == 14                               // Category namespace
          && lks[i]["title"] && lks[i]["title"].length > 0) { // Name not empty
        // Internal link to existing thingy. Extract the page name.
        var match = lks[i]["title"];
        // Remove the category prefix
        match = match.substring (match.indexOf (':') + 1);
        titles.push (match);
        if (is_redir) break;
      }
    }
    if (titles.length > 1) {
      // Disambiguation page
      hotcat_show_suggestions (titles);
      return false;
    } else if (titles.length == 1) {
      var text = document.getElementById ("hotcat_text");
      if (text) text.value = titles[0];
    }
    return true;
  } // end local function resolve

  // We should have at most one page here
  for (var page in params.query.pages) return resolve (params.query.pages[page]);
  return true; // In case we have none.
}

function hotcat_closeform (nocommit, comment)
{
  var text = document.getElementById ( "hotcat_text" ) ;
  var v = text.value || ""; 
  v = v.replace(/_/g, ' ').replace(/^\s\s*/, '').replace(/\s\s*$/, ''); // Trim leading and trailing blanks
  if (!v                                                 // Empty
      || wgNamespaceNumber == 14 && v == wgTitle         // Self-reference
      || text.parentNode.parentNode.id != 'hotcat_add'   // Modifying, but
         && text.parentNode.parentNode.hotcat_name == v) //   name unchanged
  {
    hotcat_cancel ();
    return;
  }
  
  var editlk = wgServer + wgScript + '?title=' + encodeURIComponent (wgPageName) + '&action=edit';
  var url = editlk + '&hotcat_newcat=' + encodeURIComponent( v ) ;

  // Editing existing?
  var span = text.parentNode.parentNode ; // span.form.text
  if ( span.id != "hotcat_add" ) { // Not plain "addition"   
    url += '&hotcat_removecat=' + encodeURIComponent (span.hotcat_name);
  }
  if (nocommit) url = url + '&hotcat_nocommit=1';
  if (comment) url = url + '&hotcat_comment=' + encodeURIComponent (comment);
  // Make the list disappear:
  var list = document.getElementById ( "hotcat_list" ) ;
  if (list) list.style.display = 'none';
    
  document.location = url ;
}

function hotcat_just_add ( text ) {
  var span = document.getElementById("hotcat_form") ;
  while ( span.tagName != "SPAN" ) span = span.parentNode ;
  var add = 0 ;
  if ( span.id == "hotcat_add" ) add = 1 ;
  span.id = "" ;
  while ( span.firstChild ) span.removeChild ( span.firstChild ) ;
  var na = document.createElement ( "a" ) ;
  na.href = wgArticlePath.split("$1").join("Categorie:" + encodeURI (text)) ;
  na.appendChild ( document.createTextNode ( text ) ) ;
  na.setAttribute ( "title" , "Categorie:" + text ) ;
  span.appendChild ( na ) ;
  var catline = getElementsByClassName ( document , "p" , "catlinks" ) [0] ;
  if ( add ) hotcat_append_add_span ( catline ) ;

  for ( var i = 0 ; i < span.parentNode.childNodes.length ; i++ ) {
    if ( span.parentNode.childNodes[i] != span ) continue ;
    hotcat_modify_span ( span , i ) ;
    break ;
  }
}

function hotcat_cancel () {
  var span = document.getElementById("hotcat_form").parentNode ;
  if ( span.id == "hotcat_add" ) {
    hotcat_create_span ( span ) ;
  } else {
    while ( span.firstChild.nextSibling ) span.removeChild ( span.firstChild.nextSibling ) ;
    span.firstChild.style.display = "" ;
    for ( var i = 0 ; i < span.parentNode.childNodes.length ; i++ ) {
      if ( span.parentNode.childNodes[i] != span ) continue ;
      hotcat_modify_span ( span , i ) ;
      break ;
    }
  }
}

function hotcat_text_changed () {
  if ( hotcat_running ) return ;
  var text = document.getElementById ( "hotcat_text" ) ;
  var v = text.value.ucFirst() ;
  if ( hotcat_last_v == v ) return ; // Nothing's changed...

  if (hotcat_nosuggestions) {
    // On IE, XMLHttp uses ActiveX, and the user may deny execution... just make sure
    // the list is not displayed.
    var list = document.getElementById ('hotcat_list');
    if (list != null) list.style.display = "none" ;
    var exists = document.getElementById ('hotcat_exists');
    if (exists != null) exists.style.display = "none" ;
    return;
  }
  
  hotcat_running = 1 ;
  hotcat_last_v = v ;

  if ( v != "" ) {
    var url = wgMWSuggestTemplate.replace("{namespaces}","14")
							  	  .replace("{dbname}",wgDBname)
							  	  .replace("{searchTerms}",encodeURIComponent(v));
    var request = sajax_init_object() ;
    if (request == null) {
      //Oops! We don't have XMLHttp...
      hotcat_nosuggestions = true;
      var list = document.getElementById ('hotcat_list');
      if (list != null) list.style.display = "none" ;
      var exists = document.getElementById ('hotcat_exists');
      if (exists != null) exists.style.display = "none" ;
      hotcat_running = 0;
      return;
    } 
    request.open('GET', url, true);
    request.onreadystatechange =
      function () {
        if (request.readyState == 4) {
          try {
            eval( "var queryResult="+ request.responseText );
          } catch (someError ) {
            if( console && console.log )
              console.log( "Oh dear, our JSON query went down the drain?\nError: " +someError );
            return;
          }
          var pages = queryResult[1]; // results are *with* namespace here
          var titles = new Array();
          for ( var i = 0 ; pages && i < pages.length ; i++ ) {
            // Remove the namespace. No hardcoding of 'Category:', please, other Wikis may have
            // local names ("Kategorie:" on de-WP, for instance). Also don't break on category
            // names containing a colon
            var s = pages[i].substring (pages[i].indexOf (':') + 1);
            if ( s.substr ( 0 , hotcat_last_v.length ).toLowerCase() != hotcat_last_v.toLowerCase() ) break ;
            titles.push ( s ) ;
          }
          hotcat_show_suggestions ( titles ) ;
        }
      };
    request.setRequestHeader ('Pragma', 'cache=yes');
    request.setRequestHeader ('Cache-Control', 'no-transform');
    request.send(null);
  } else {
    hotcat_show_suggestions ( new Array () ) ;
  }
  hotcat_running = 0 ;
}

function hotcat_show_suggestions ( titles ) {
  var text = document.getElementById ( "hotcat_text" ) ;
  var list = document.getElementById ( "hotcat_list" ) ;
  var icon = document.getElementById ( "hotcat_exists" ) ;
  // Somehow, after a double click on the selection list, we still get here in IE, but
  // the list may no longer exist... Lupo, 2008-01-20
  if (list == null) return;
  if (hotcat_nosuggestions) {
    list.style.display = "none" ;
    if (icon != null) icon.style.display = "none";
    return;
  }
  if ( titles.length == 0 ) {
    list.style.display = "none" ;
    icon.src = hotcat_exists_no ;
    return ;
  }
  
  // Set list size to minimum of 5 and actual number of titles. Formerly was just 5.
  // Lupo, 2008-01-20
  list.size = (titles.length > 5 ? 5 : titles.length) ;
  // Avoid list height 1: double-click doesn't work in FF. Lupo, 2008-02-27
  if (list.size == 1) list.size = 2;
  list.style.align = "left" ;
  list.style.zIndex = 5 ;
  list.style.position = "absolute" ;

  // Was listh = titles.length * 20: that makes no sense if titles.length > list.size
  // Lupo, 2008-01-20
  var listh = list.size * 20;
  var nl = parseInt (text.offsetLeft) - 1 ;
  var nt = parseInt (text.offsetTop) - listh ;
  if (skin == 'nostalgia' || skin == 'cologneblue' || skin == 'standard') {
    // These three skins have the category line at the top of the page. Make the suggestions
    // appear *below* out input field.
    nt = parseInt (text.offsetTop) + parseInt (text.offsetHeight) + 3;
  }
  list.style.top = nt + "px" ;
  list.style.width = text.offsetWidth + "px" ;
  list.style.height = listh + "px" ;
  list.style.left = nl + "px" ;
  while ( list.firstChild ) list.removeChild ( list.firstChild ) ;
  for ( var i = 0 ; i < titles.length ; i++ ) {
    var opt = document.createElement ( "option" ) ;
    var ot = document.createTextNode ( titles[i] ) ;
    opt.appendChild ( ot ) ;
    //opt.value = titles[i] ;
    list.appendChild ( opt ) ;
  }
  
  icon.src = hotcat_exists_yes ;

  var nof_titles = titles.length;
  var first_title = titles.shift ();
  var v = text.value.ucFirst();

  text.focus();
  if ( first_title == v ) {
    if( nof_titles == 1 ) {
      // Only one result, and it's the same as whatever is in the input box: makes no sense
      // to show the list.
      list.style.display = "none";
    }
    return;
  }
  list.style.display = "block" ;

  // Put the first entry of the title list into the text field, and select the
  // new suffix such that it'll be overwritten if the user keeps typing.
  // ONLY do this if we have a way to select parts of the content of a text
  // field, otherwise, this is very annoying for the user. Note: IE does it
  // again differently from the two versions previously implemented.
  // Lupo, 2008-01-20
  // Only put first entry into the list if the user hasn't typed something 
  // conflicting yet Dschwen 2008-02-18
  if ( ( text.setSelectionRange ||
         text.createTextRange ||
         typeof (text.selectionStart) != 'undefined' &&
         typeof (text.selectionEnd) != 'undefined' ) &&
         v == first_title.substr(0,v.length) )
  {
    // taking hotcat_last_v was a major annoyance, 
    // since it constantly killed text that was typed in
    // _since_ the last AJAX request was fired! Dschwen 2008-02-18
    var nosel = v.length ;
  
    text.value = first_title ;
    
    if (text.setSelectionRange)      // e.g. khtml
      text.setSelectionRange (nosel, first_title.length);
    else if (text.createTextRange) { // IE
      var new_selection = text.createTextRange();
      new_selection.move ("character", nosel);
      new_selection.moveEnd ("character", first_title.length - nosel);
      new_selection.select();
    } else {
      text.selectionStart = nosel;
      text.selectionEnd   = first_title.length;
    }
  }
}
/* </nowiki></source> */
