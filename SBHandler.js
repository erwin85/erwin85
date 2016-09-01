//<source lang="javascript">
 
/*
    Support for quick handling of the [[Spam blacklist]] at meta. See [[:m:User:Erwin/SBHandler]] for
    more information.

    Author: [[:m:User:Erwin]], October 2008 - February 2009
    License: Quadruple licensed GFDL, GPL, LGPL and Creative Commons Attribution 3.0 (CC-BY-3.0)


    This tool uses code from DelReqHandler at Commons.
        [[:Commons:MediaWiki:Gadget-DelReqHandler.js]] (oldid=15093612)
        Author: [[:Commons:User:Lupo]], October 2007 - January 2008
        License: Quadruple licensed GFDL, GPL, LGPL and Creative Commons Attribution 3.0 (CC-BY-3.0)

    Tested only in Firefox.
*/

/**** Guard against double inclusions */
 
if (typeof (SBHandler) == 'undefined') {
 
    var SBUtils =
    {
        // userIsInGroup (from Commons:MediaWiki:Utilities.js)
        userIsInGroup : function (group)
        {
            if (wgUserGroups) {
                if (!group || group.length == 0) group = '*';
                for (var i=0; i < wgUserGroups.length; i++) {
                    if (wgUserGroups[i] == group) return true;
                }
            }
            return false;
        },
 
        // setEditSummary (from Commons:MediaWiki:Utilities.js)
        setEditSummary : function (text)
        {
            if (document.editform == null || document.editform.wpSummary == null) return;
            var summary = document.editform.wpSummary.value;
            if (summary == null || summary.length == 0) {
                document.editform.wpSummary.value = text;
            } else if (summary.substr(-3) == '*/ ' || summary.substr(-2) == '*/' ) {
                document.editform.wpSummary.value += text
            } else {
                document.editform.wpSummary.value += '; ' + text;
            }
        },
 
        // makeRawLink (from Commons:MediaWiki:Utilities.js)
        makeRawLink : function (name, url, target)
        {
            var link = document.createElement('a');
            link.setAttribute('href', url);
            if (target) link.setAttribute ('target', target);
            link.appendChild(document.createTextNode(name));
            return link;
        },

        // The following function is defined in several places, and unfortunately, it is defined
        // wrongly in some places. (For instance, in [[:en:User:Lupin/popups.js]]: it should use
        // decodeURIComponent, not decodeURI!!!) Make sure we've got the right version here:
        getParamValue : function (paramName)
        {
            var cmdRe = RegExp ('[&?]' + paramName + '=([^&]*)');
            var h = document.location.href;
            var m = cmdRe.exec (document.location.href);
            if (m) {
                try {
                    return decodeURIComponent (m[1]);
                } catch (someError) {}
            }
            return null;
        }
 
    } // End of SBUtils
 
    /**** Enable the whole shebang only for sysops. */
    if (SBUtils.userIsInGroup ('sysop')) {
 
        var SBHandler =
        {
 
            /*------------------------------------------------------------------------------------------
            Spam blacklist requests closing: add "[add]", "[remove]", "[reverted]" and "[decline]" links to
            the left of the section edit links of a request.
            ------------------------------------------------------------------------------------------*/
 
            sb_close_add       : 'close_add',
            sb_close_rem       : 'close_rem',
            sb_close_na        : 'close_na',
            sb_close_rev       : 'close_rev',
            sb_close_dec       : 'close_dec',
            close_add_summary  : 'Added',
            close_rem_summary  : 'Removed',
            close_na_summary   : 'Closed',
            close_rev_summary  : 'Reverted',
            close_dec_summary  : 'Declined',
            sb_add             : 'add',
            sb_rem             : 'rem',
 
            closeRequestLinks : function ()
            {
                function addRequestLinks (name, href, before, parent)
                {
                    parent.insertBefore (document.createTextNode ('['), before);
                    parent.insertBefore (SBUtils.makeRawLink (name, href), before);
                    parent.insertBefore (document.createTextNode (']'), before);
                }
 
                var param = SBUtils.getParamValue ('fakeaction');
                var wpAction = SBUtils.getParamValue ('action');
                if (param == null) {
                    if (wgPageName == 'Talk:Spam_blacklist') {
                        // We're on [[Talk:Spam blacklist]]
                        var edit_lks = getElementsByClassName(document, 'span', 'editsection');
                        if (edit_lks.length == 0) {
                            return;
                        }
                        for (i = 0; i < edit_lks.length; i++) {
                            // Set to true if the addition or removal section is found
                            ignore = false;
                            // Find the A within:
                            var anchors = edit_lks[i].getElementsByTagName('a');
                            if (anchors != null && anchors.length > 0) {
                                var anchor = anchors[0];
                                var href = anchor.getAttribute('href');
                                var title = anchor.getAttribute('title');
                                if (title.indexOf('additions') > 0 && title.indexOf('Bot') < 0) {
                                    ignore = true;
                                    lk_name = 'add';
                                    lk_action = SBHandler.sb_close_add;
                                } else if (title.indexOf('additions') > 0 && title.indexOf('Bot') > 0) {
                                    lk_name = null;
                                    lk_action = null;
                                } else if (title.indexOf('removals') > 0 ) {
                                    ignore = true;
                                    lk_name = 'remove';
                                    lk_action = SBHandler.sb_close_rem;
                                } else if (title.indexOf('problems') > 0 ) {
                                    lk_name = null;
                                    lk_action = null;
                                }
                                if (href.indexOf ('Talk:Spam_blacklist') > 0 && href.indexOf ('&section=') > 0 && lk_name != null && !ignore) {
                                    var orig_bracket = edit_lks[i].firstChild;
                                    addRequestLinks(lk_name, href + '&fakeaction=' + lk_action, orig_bracket, edit_lks[i]);
                                    addRequestLinks('decline', href + '&fakeaction=' + SBHandler.sb_close_dec, orig_bracket, edit_lks[i]);
                                }
                            }
                        }
                    } else if (wgPageName.substr(0, 18) == 'User:COIBot/XWiki/') {
                        var edit_lks = getElementsByClassName (document, 'span', 'editsection');
                        if (edit_lks.length == 0) {
                            return;
                        }
                        i = edit_lks.length - 1;
                        var anchors = edit_lks[i].getElementsByTagName ('a');
                        if (anchors != null && anchors.length > 0) {
                            var anchor = anchors[0];
                            var href   = anchor.getAttribute ('href');
                            if (href.indexOf ('&section=') > 0) {
                                var orig_bracket = edit_lks[i].firstChild;
                                // [close]
                                addRequestLinks('close', href + '&fakeaction=' + SBHandler.sb_close_na, orig_bracket, edit_lks[i]);
                                // [reverted]
                                addRequestLinks('reverted', href + '&fakeaction=' + SBHandler.sb_close_rev, orig_bracket, edit_lks[i]);
                                // [add]
                                addRequestLinks('add', href + '&fakeaction=' + SBHandler.sb_close_add, orig_bracket, edit_lks[i]);
                            }
                        }
                    } else if (wgPageName.substr(0, 18) == 'User:COIBot/Local/') {
                        var edit_lks = getElementsByClassName (document, 'span', 'editsection');
                        if (edit_lks.length == 0) {
                            return;
                        }
                        i = edit_lks.length - 1;
                        var anchors = edit_lks[i].getElementsByTagName ('a');
                        if (anchors != null && anchors.length > 0) {
                            var anchor = anchors[0];
                            var href   = anchor.getAttribute ('href');
                            if (href.indexOf ('&section=') > 0) {
                                var orig_bracket = edit_lks[i].firstChild;
                                // [close]
                                addRequestLinks('close', href + '&fakeaction=' + SBHandler.sb_close_na, orig_bracket, edit_lks[i]);
                                // [reverted]
                                addRequestLinks('reverted', href + '&fakeaction=' + SBHandler.sb_close_rev, orig_bracket, edit_lks[i]);
                            }
                        }
                    }
 
                } else if (param != null) {
// We're on a request page
                    var summary = null;
                    action = null;
                    var text = document.editform.wpTextbox1;
                    urls = new Array();
                    
                    //Only do anything if we're editing a section.
                    if (document.getElementsByName('wpSection') == null) {
                        return;
                    }
                    
                    // Get URLs
                    if (wgPageName == 'Talk:Spam_blacklist') {
                        var reurl = /\{\{([Ll]ink[Ss]ummary|[Ss]pam[Ll]ink)\|(.*?)\}\}/g;
                        m = text.value.match(reurl)
                        if (m != null) {
                            for (var i=0; i < m.length; i++) {
                                url = m[i].substr(m[i].indexOf('|') + 1, m[i].length - m[i].indexOf('|') - 3); // Can't simply refer to the group
                                urls.push('\\b' + url.replace(/\./g, '\\.') + '\\b');
                            }
                        }
                        SBrequest = text.value.match(/^\=\=\=.*?\=\=\=$/m);
                        if (SBrequest && SBrequest.length > 0) {
                            SBrequest = SBrequest[0].substr(3, SBrequest[0].length - 6);
                        } else {
                            SBrequest = '';
                        }
                        
                        if (urls == '' && SBrequest != '') {
                            m = SBrequest.match(/(?:www\.|)[^\s]*?\.[a-zA-Z]{2,3}/g)
                            for (var i=0; i < m.length; i++) {
                                if (m[i].substr(0,4) == 'www.') {
                                    urls.push('\\b' + m[i].substr(4).replace(/\./g, '\\.') + '\\b');
                                } else {
                                    urls.push('\\b' + m[i].replace(/\./g, '\\.') + '\\b');
                                }
                            }
                        }
                    } else if (wgPageName.substr(0, 18) == 'User:COIBot/XWiki/') {
                        url = wgPageName.substr(18);
                        urls.push('\\b' + url.replace(/\./g, '\\.') + '\\b');
                        SBrequest = wgPageName;
 
                        // Close report
                        if (param == SBHandler.sb_close_add || param == SBHandler.sb_close_rev || param == SBHandler.sb_close_na) {
                            text.value = text.value.replace("{{LinkStatus|open}}", "{{LinkStatus|closed}}");
                        }
                    } else if (wgPageName.substr(0, 18) == 'User:COIBot/Local/') {
                        SBrequest = wgPageName;
 
                        // Close report
                        if (param == SBHandler.sb_close_rev || param == SBHandler.sb_close_na) {
                            //FIXME: use regex?
                            text.value = text.value.replace("|open}}", "|closed}}");
                        }
                    }

                    var copyWarn = document.getElementById('editpage-copywarn');
                    copyWarn.innerHTML = copyWarn.innerHTML + '<div style=\"border: 1px solid; margin: 5px; padding: 5px;\"><h3>SBHandler</h3>\n<div id=\"SBdebug\"></div>';
                    SBdebug = document.getElementById('SBdebug');
                    if (param == SBHandler.sb_close_add) {
                        summary = SBHandler.close_add_summary;
                        append = (typeof(SBHandlerAddComment) != 'undefined' ? SBHandlerAddComment : ':{{Added}}. --~~~~')
                        action = SBHandler.sb_add;
                    } else if (param == SBHandler.sb_close_rem) {
                        summary = SBHandler.close_rem_summary;
                        append = (typeof(SBHandlerRemComment) != 'undefined' ? SBHandlerRemComment : ':Removed. --~~~~')
                        action = SBHandler.sb_rem;
                    } else if (param == SBHandler.sb_close_na) {
                        summary = SBHandler.close_na_summary;
                        append = (typeof(SBHandlerCloseComment) != 'undefined' ? SBHandlerCloseComment : ':Closed. --~~~~')
                    } else if (param == SBHandler.sb_close_rev) {
                        summary = SBHandler.close_rev_summary;
                        append = (typeof(SBHandlerRevComment) != 'undefined' ? SBHandlerRevComment : ':Reverted. --~~~~')
                    } else if (param == SBHandler.sb_close_dec) {
                        summary = SBHandler.close_dec_summary;
                        append = (typeof(SBHandlerDecComment) != 'undefined' ? SBHandlerDecComment : ':{{Declined}}. --~~~~')
                    }                                      
                    if (summary != null) {
                        if (wpAction == 'edit') {
                            SBUtils.setEditSummary (summary);
                            text.value += '\n' + append;
                        }
                        if (action != null && urls != '') {
                            SBdebug.innerHTML += '<span style=\"font-weight:bold;\">Action: </span>' +action + ';<br />';
                            SBdebug.innerHTML += '<span style=\"font-weight:bold;\">Domains: </span>' + urls.join(', ') + ';<br />';
                            var editform = document.getElementById('editform');
                            editform.action += '&fakeaction=' + param;
                            
                            // Remove save button
                            var wpSave = document.getElementById('wpSave');
                            wpSave.parentNode.removeChild(wpSave);
                            
                            //Add save link:
                            wpSave = document.createElement('span');
                            wpSave.setAttribute('id', 'wpSave');
                            wpSave.innerHTML = '<a href=\"javascript:SBHandler.saveRequest()\">Save and edit blacklist</a> ';
                            
                            var wpPreview = document.getElementById('wpPreview');
                            wpPreview.parentNode.insertBefore(wpSave, wpPreview);
                        }
                        // Don't close the window to allow adding a comment.
                        if (text.scrollHeight > text.clientHeight) {
                            text.scrollTop = text.scrollHeight - text.clientHeight;
                        }
                        text.focus ();
                    }
                }
            },
 
            saveRequest : function ()
            {
                SBdebug.innerHTML += '<span style=\"font-weight:bold;\">Saving request…</span><br />';
                var summary = document.getElementById('wpSummary').value;
                var text = document.getElementById('wpTextbox1').value;
                var section = document.getElementsByName('wpSection')[0].value;
                var minor = document.getElementById('wpMinoredit');
                if (minor.checked) {
                    minor = '&minor=1';
                } else {
                    minor = '&notminor=1';
                }
                var watch = document.getElementById('wpWatchthis');
                if (watch.checked) {
                    watch = '&watchlist=watch';
                } else {
                    watch = '&watchlist=unwatch';
                }
                if (section != '') {
                    section = '&section=' + encodeURIComponent(section)
                }
                token = document.getElementsByName('wpEditToken')[0].value;
                query = 'format=xml';
                params = 'action=edit&title=' + encodeURIComponent(wgPageName) + '&summary=' + encodeURIComponent(summary) + '&text=' + encodeURIComponent(text) + section + minor + watch + '&token=' + encodeURIComponent(token);
                SBHandler.postRequest(query, SBHandler.setLocation, params, true);
            },
            
            setLocation : function (request)
            {
                var xml = request.responseXML;
                var location = wgServer + wgScriptPath + '/index.php'
                                            + '?title=Special:SpamBlacklist'
                                            + '&action=' + action
                                            + '&urls=' + urls.join('|')
                                            + '&request=' + SBrequest;
                if (xml != null ) {
                    edits = xml.getElementsByTagName('edit');
                    if (edits.length == 0 ) {
                        SBdebug.innerHTML = '<div style=\"font-weight:bold;\">Saving might have failed. Please close the request yourself. '
                                                + 'Use <a href="' + location
                                                + '" title="Special:SpamBlacklist">Special:SpamBlacklist</a>'
                                                + ' to add/remove the domains to/from the blacklist.<br />Params:<br /><pre>' + params + '</pre><br />Response:<pre>' + request.responseText + '</pre></div>';
                        return;
                    }    
                    result = edits[0].getAttribute('result');
                    SBHandler.oldid = edits[0].getAttribute('newrevid');
                    if (result != 'Success') {
                        SBdebug.innerHTML = '<div style=\"font-weight:bold;\">Saving failed. Please close the request yourself. '
                                                + 'Use <a href="' + location
                                                + '" title="Special:SpamBlacklist">Special:SpamBlacklist</a>'
                                                + ' to add/remove the domains to/from the blacklist.<br />Params:<br /><pre>' + params + '</pre><br />Response:<pre>' + request.responseText + '</pre></div>';
                        return;
                    } else {
                        window.location = location;                         
                    }
                } else {
                    SBdebug.innerHTML += '<div style=\"font-weight:bold;\">ERROR: ' + request.status + '<br /> Please close the request yourself. '
                                                + 'Use <a href="' + location
                                                + '" title="Special:SpamBlacklist">Special:SpamBlacklist</a>'
                                                + ' to add/remove the domains to/from the blacklist.<br />Params:<br /><pre>' + params + '</pre><br />Response:<pre>' + request.responseText + '</pre></div>';
                    return;
                }
            },
            /*------------------------------------------------------------------------------------------
            Add to blacklist.
            ------------------------------------------------------------------------------------------*/
            edittoken : '',
            text : '',
            request : '',
            urls : '',
            action : '',
            timestamp : '',
            oldid : '',
            custom : false,
 
            SBWrapper : function ()
            {
                document.title = 'Spam blacklist';
 
                // Add CSS for viewing the differences
                importStylesheetURI('http://meta.wikimedia.org/skins-1.5/common/diff.css?182');
                
                // Set header
                header = document.getElementsByTagName('h1')[0];
                header.innerHTML = 'Spam blacklist';
 
                // Set content
                content = document.getElementById('bodyContent');
                content.innerHTML = '<h3 id=\"siteSub\">From Meta, a Wikimedia project coordination wiki</h3>'
                                    + '<p>Use this tool to add domains to the <a href=\"http://meta.wikimedia.org/wiki/Spam blacklist\" title=\"Spam blacklist\">Spam blacklist</a>, or remove them, and log the change. Note that it does not escape paths. <span style=\"font-weight:bold;\">Do not use this tool unless you have a basic understanding of regex.</span> You still need to confirm the changes. Do not assume that the proposed edit is correct.</p>';

                SBHandler.action = SBUtils.getParamValue ('action');
                SBHandler.urls = SBUtils.getParamValue ('urls');
                SBHandler.request = SBUtils.getParamValue('request');
                
                if ((SBHandler.action == 'add' || SBHandler.action == 'rem') && SBHandler.urls != null && SBHandler.urls != '' && SBHandler.request != null) {
                    content.innerHTML += '<div id=\"SBstatus\" style=\"font-style: italic; border: 1px solid; padding: 5px; float:right;\">'
                                        + '<span id=\"Sthrobber\" style=\"text-align:center; font-weight:bold; float:right;\">'
                                        + '<img src=\"http://upload.wikimedia.org/wikipedia/commons/d/d2/Spinning_wheel_throbber.gif\" alt=\"Loading\">'
                                        + 'Loading…</span>'
                                        + '<br clear=\"all\" />'
                                        + '<table><tr style=\"vertical-align:top;\"><td>'
                                        + '<h5>Blacklist</h5>'
                                        + '<ul><li id=\"SgetBL\" style=\"color:grey;\">Get blacklist text</li>'
                                        + '<li id=\"SaddBL\" style=\"color:grey;\">Add/remove domains</li>'
                                        + '<li id=\"SgetC\" style=\"color:grey;\">Get changes from server</li>'
                                        + '<li id=\"SparseBL\" style=\"color:grey;\">Parse and show changes</li>'
                                        + '<li id=\"SconfirmBL\" style=\"color:grey;\">Confirm changes</li>'
                                        + '<li id=\"SsaveBL\" style=\"color:grey;\">Save changes</li></ul></td><td>'
                                        + '<h5>Log</h5>'
                                        + '<ul><li id=\"SgetL\" style=\"color:grey;\">Get log text</li>'
                                        + '<li id=\"SaddL\" style=\"color:grey;\">Add/remove domains</li>'
                                        + '<li id=\"SsaveL\" style=\"color:grey;\">Save changes</li>'
                                        + '</ul></tr></table></div><br clear=\"all\" />'
                                        + '<h3>Blacklist</h3><div id=\"SBlist\"></div>'
                                         + '<h3>Log</h3><div id=\"SBlog\"></div>';
                    SBlist = document.getElementById("SBlist");
                    SBlog = document.getElementById("SBlog");
                    SBHandler.getRequest('action=query&prop=info&intoken=edit&titles=Spam blacklist', SBHandler.getBL, true);
                } else {
                    content.innerHTML += '<p style=\"font-style:italic\">This tool can only be used in conjunction with <a href=\"http://meta.wikimedia.org/wiki/Talk:Spam_blacklist\" title=\"Talk:Spam blacklist\">Talk:Spam blacklist</a> or <a href=\"http://meta.wikimedia.org/wiki/User:COIBot/XWiki\" title=\"User:COIBOt/XWiki\">COIBot\'s spam reports</a> to add or remove domains.</p>'
                }
            },
 
            // Get the current text and oldid of [[Spam blacklist]]
            getBL : function(request)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    pages = xml.getElementsByTagName('page');
                    SBHandler.edittoken = pages[0].getAttribute('edittoken');
                    SBHandler.getRequest('action=query&prop=revisions&titles=Spam blacklist&rvprop=ids|timestamp|user|comment|content', SBHandler.parseBL, true);
                }
            },
 
            // Add/remove domains from the text
            parseBL : function(request)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    revs = xml.getElementsByTagName('rev') ;
                    user = revs[0].getAttribute('user');
                    revid = revs[0].getAttribute('revid');
                    SBHandler.timestamp = revs[0].getAttribute('timestamp');
                    SBHandler.text = revs[0].textContent;
                    oldlength = SBHandler.text.length;
                    document.getElementById('SgetBL').style.color = 'black';
                } else {
                    SBlist.innerHTML += '<div style=\"font-weight:bold;\">ERROR: ' + request.status + '<br />Aborting.</div>';
                    return;
                }
 
                if (SBHandler.action == SBHandler.sb_add) {
                    urls = SBHandler.urls.replace(/\|/g,'\n');
                    if (SBHandler.text.length > 1000 ) {
                        lastchars = SBHandler.text.substr(-1000);
                        SBHandler.text = SBHandler.text.substr(0, SBHandler.text.length - 1000);
                        if (lastchars.indexOf('## sbhandler_end') > 0) {
                            lastchars = lastchars.replace('## sbhandler_end', urls + '\n## sbhandler_end');
                        } else {
                            SBlist.innerHTML += '<div style=\"font-weight:bold;\">ERROR: Could not find marker. Aborting.</div>';
                            return;
                        }    
                        SBHandler.text += lastchars;
                    } else {
                        if (SBHandler.text.indexOf('## sbhandler_end') > 0) {
                            SBHandler.text = SBHandler.text.replace('## sbhandler_end', urls + '\n## sbhandler_end');
                        } else {
                            SBlist.innerHTML += '<div style=\"font-weight:bold;\">ERROR: Could not find marker. Please add \"## sbhandler_end\" on a single line below the blacklist\'s entry\'s and try again.</div>';
                            return;
                        }
                    }
                } else if (SBHandler.action == SBHandler.sb_rem) {
                    urls = SBHandler.urls.split('|');
                    for (var i=0; i < urls.length; i++) {
                        SBHandler.text = SBHandler.text.replace(urls[i] + '\n', '');
                    }
                }
                //Check if the length changed, if not assume nothing changed.
                if (oldlength == SBHandler.text.length) {
                    SBlist.innerHTML += '<div style=\"font-weight:bold;\">ERROR: The length of the old and new text are the same. This shouldn\'t happen. Aborting.</div>';
                    return;
                }
                document.getElementById('SaddBL').style.color = 'black';
                params = 'action=query&prop=revisions&titles=Spam+blacklist&rvdifftotext=' + encodeURIComponent(SBHandler.text);
                SBHandler.postRequest('format=xml', SBHandler.parseDiff, params, true);
            },
 
            // Parse and show the proposed edit
            parseDiff : function(request)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    document.getElementById('SgetC').style.color = 'black';
                    diffSource = xml.getElementsByTagName('diff');
                    if (diffSource[0].childNodes[0].nodeValue) {
                        urls = SBHandler.urls.split('|');
 
                        if (SBHandler.action == 'add') {
                            summary = 'Adding ';
                        } else {
                            summary = 'Removing ';
                        }
 
                        if (urls.length > 1 ) {
                            summary += urls.length + ' domains ';
                        } else {
                            summary += urls[0] + ' ';
                        }
 
                        if (SBHandler.request.substr(0, 18) == 'User:COIBot/XWiki/') {
                            summary += 'per [[' + SBHandler.request + ']].';
                        } else {
                            summary += 'per [[Talk:Spam blacklist]].';
                        }
 
                        SBlist.innerHTML += '<div id="wikiDiff"><table class="diff"><col class="diff-marker" /><col class="diff-content" /><col class="diff-marker" /><col class="diff-content" /><tr valign="top"><td colspan="2" class="diff-otitle">Current revision</td><td colspan="2" class="diff-ntitle">Your text</td></tr>'                       
                        + diffSource[0].childNodes[0].nodeValue + '</table></div>'
                                        + '<br /><div id=\"BLform\">'
                                        + '<input type=\"text\" value=\"' + summary + '\" id=\"summary\" maxlength=\"200\" size=\"60\" >&nbsp;&nbsp;&nbsp;<button onclick=\"SBHandler.submitBL()\">Confirm changes</button><button onclick=\"SBHandler.editBL()\">Edit changes</button></div>';
                        document.getElementById('SparseBL').style.color = 'black';
                    } else {
                        SBlist.innerHTML += '<div style=\"font-weight:bold;\">ERROR: Could not show diff.<br />Aborting.</div>';
                        return;
                    }
                } else {
                    SBlist.innerHTML += '<div style=\"font-weight:bold;\">ERROR: ' + request.status + '<br />Aborting.</div>';
                    return;
                }
            },
            
            // Add a text area to change the blacklist yourself
            
            editBL : function ()
            {
                BLform = document.getElementById('BLform');
                BLform.innerHTML = '<textarea name=\"wpTextbox1\" id=\"wpTextbox1\" cols=\"80\" rows=\"25\" accesskey=\",\">'
                                    + SBHandler.text
                                    + '</textarea>'
                                    + BLform.innerHTML;
            },
                
            // Submit the edit to [[Spam blacklist]]
            submitBL : function()
            {
                wpTextbox = document.getElementById('wpTextbox1');
                if (wpTextbox != null) {
                    SBHandler.text = wpTextbox.value;
                    SBHandler.custom = true; //We can't simply log the change. User needs to do that.
                }
                document.getElementById('SconfirmBL').style.color = 'black';
                summary = document.getElementById('summary').value;
                summary += ' Using SBHandler.';
                query = 'format=xml';
                params = 'action=edit&title=Spam blacklist&summary=' + encodeURIComponent(summary) + '&text=' + encodeURIComponent(SBHandler.text) + '&basetimestamp=' + SBHandler.timestamp  +  '&token=' + encodeURIComponent(SBHandler.edittoken);
                SBHandler.postRequest(query, SBHandler.LWrapper, params, true);
            },
 
            // Start logging procedure
            LWrapper : function(request)
            {
                var xml = request.responseXML;
                if (xml != null ) {
                    edits = xml.getElementsByTagName('edit');
                    if (edits.length == 0 ) {
                        SBlist.innerHTML = '<div style=\"font-weight:bold;\">Saving might have failed. Please check if it succeeded and log the edit yourself if necessary.</div>';
                        return;
                    }    
                    result = edits[0].getAttribute('result');
                    SBHandler.oldid = edits[0].getAttribute('newrevid');
                    if (result != 'Success') {
                        SBlist.innerHTML = '<div style=\"font-weight:bold;\">Saving failed. Please blacklist the domains yourself.</div>';
                        return;
                    } else {
                        document.getElementById('SsaveBL').style.color = 'black';
                        SBlist.innerHTML = '<div>Blacklist has been updated, <a href=\"' + wgServer + wgScriptPath + '/index.php?oldid=' + SBHandler.oldid + '&diff=prev\" title=\"diff\">diff</a>.</div>';
                    }
 
                } else {
                    SBlist.innerHTML += '<div style=\"font-weight:bold;\">ERROR: ' + request.status + '<br />Aborting.</div>';
                    return;
                }
 
                d = new Date();
                m = d.getMonth() + 1;
                if (m < 10 ) {
                    m = '0' + m;
                }
                y = d.getFullYear();
                logtitle = 'Spam blacklist/Log/' + y + '/' + m;
                
                if (SBHandler.request.substr(0, 18) != 'User:COIBot/XWiki/') {
                    SBHandler.getRequest('action=query&prop=revisions&titles=Talk:Spam blacklist&rvprop=ids|timestamp|user|comment|content', SBHandler.parseTBL, true);
                } else {  
                    SBHandler.getRequest('action=query&prop=revisions&titles=' + logtitle + '&rvprop=ids|timestamp|user|comment|content', SBHandler.parseL, true);
                }
            },
 
            // Get current oldid of [[Talk:Spam blacklist]], because that's the location of the request.
            parseTBL : function(request)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    revs = xml.getElementsByTagName('rev') ;
                    tprevid = revs[0].getAttribute('revid');
                } else {
                    SBlog.innerHTML += '<div style=\"font-weight:bold;\">ERROR: ' + request.status + '<br />. Please log the edit yourself.</div>';
                    return;
                }
 
                SBHandler.getRequest('action=query&prop=revisions&titles=' + logtitle + '&rvprop=ids|timestamp|user|comment|content', SBHandler.parseL, true);    
            },
 
            // Add/remove domains to/from log
            parseL : function(request)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    document.getElementById('SgetL').style.color = 'black';
                    revs = xml.getElementsByTagName('rev') ;
                    if (revs.length == 0) {
                        SBlog.innerHTML += '<div style=\"font-weight:bold;\">ERROR: Could not get log.<br /> It probably does not exist yet. Please create <a href="http://meta.wikimedia.org/wiki/' + logtitle + '" title="' + logtitle + '">' + logtitle + '</a> and log the edit yourself.</div>';
                        return;
                    }
                    SBHandler.timestamp = revs[0].getAttribute('timestamp');
                    SBHandler.text = revs[0].textContent;
                } else {
                    SBlog.innerHTML += '<div style=\"font-weight:bold;\">ERROR: ' + request.status + '<br /> Please log the edit yourself.</div>';
                    return;
                }
 
                if (SBHandler.action == SBHandler.sb_add) {
                    sbldiff = '{{sbl-diff|' + SBHandler.oldid + '}}';
                } else {
                    sbldiff = '{{sbl-diff|' + SBHandler.oldid + '|removal}}';
                }
 
                urls = SBHandler.urls.split('|');
                r = SBHandler.request
                if (r.substr(0, 18) == 'User:COIBot/XWiki/')
                {
                    r = '[[' + r + ']]';
                } else {
                    r = '{{sbl-log|' + tprevid + '#{{subst:anchorencode:' + r + '}}}}';
                }
 
                spaces = '                                        ';
 
                if (urls.length == 1) {
                    log_text = ' ' + urls[0] + spaces.substr(0, 39 - urls[0].length) + '# '
                                     + wgUserName + ' # ' + sbldiff + '; see ' + r
                } else {
                    log_text = ' #:                                     '
                                     + wgUserName + ' # ' + sbldiff + '; see ' + r
                    for (var i=0; i < urls.length; i++) {
                        log_text += '\n   ' + urls[i];
                    }
                }
                
                // User needs to confirm log edit
                if (SBHandler.custom) {
                    SBlog.innerHTML += '<p>The following text will be added to the log. You need to update this to reflect the changes you made to the proposed edit.</p>'
                                    + '<textarea name=\"wpTextbox1\" id=\"wpTextbox1\" cols=\"80\" rows=\"10\" accesskey=\",\">'
                                    + log_text
                                    + '</textarea>'
                                    + '<button onclick=\"SBHandler.submitL()\">Confirm changes</button>';
                } else {
                    SBHandler.submitL();
                }
            },
            
            submitL : function() {
                wpTextbox = document.getElementById('wpTextbox1');
                if (wpTextbox != null) {
                    SBHandler.text += '\n' + wpTextbox.value;
                } else {
                    SBHandler.text += '\n' + log_text;
                }
                document.getElementById('SaddL').style.color = 'black';
                query = 'format=xml';
                params = 'action=edit&title=' + encodeURIComponent(logtitle) + '&summary=' + encodeURIComponent(summary) + '&text=' + encodeURIComponent(SBHandler.text) + '&token=' + encodeURIComponent(SBHandler.edittoken);
                SBHandler.postRequest(query, SBHandler.LEnd, params, true);                 
            },
 
            // Confirm results
            LEnd : function(request)
            {
                var xml = request.responseXML;
 
                if (xml != null ) {
                    edits = xml.getElementsByTagName('edit');
                    if (edits.length == 0 ) {
                        SBlist.innerHTML = '<div style=\"font-weight:bold;\">Saving might have failed. Please check if it succeeded and log the edit yourself if necessary.</div>';
                        return;
                    }
                    result = edits[0].getAttribute('result');
                    oldid = edits[0].getAttribute('newrevid');
                    if (result != 'Success') {
                        SBlog.innerHTML = '<div>Saving failed. Please log the edit yourself.</div>';
                        return;
                    } else {
                        document.getElementById('SsaveL').style.color = 'black';
                        document.getElementById('Sthrobber').innerHTML = '';
                        SBlog.innerHTML = '<div>Log has been updated, <a href=\"' + wgServer + wgScriptPath + '/index.php?oldid=' + oldid + '&diff=prev\" title=\"diff\">diff</a>.<p style=\"font-style:italic\">Thanks for helping with the Spam blacklist! Return to <a href=\"http://meta.wikimedia.org/wiki/Talk:Spam_blacklist\" title=\"Talk:Spam blacklist\">Talk:Spam blacklist</a>.</p></div>';
                    }
                }
            },
 
            getRequest : function(query, callback, api)
            {
                if (api) {
                    var url = wgServer + wgScriptPath + '/api.php?format=xml&' + query;
                } else {
                    var url = wgServer + wgScriptPath + '/index.php?' + query;
                }
                var request = sajax_init_object() ;
                if (request == null) {
                    return null;
                }    
                request.open('GET', url, true);
                request.onreadystatechange = function () {
                    if(request.readyState==4) {
                        callback(request)
                    }
                };
                request.setRequestHeader('Pragma', 'cache=yes');
                request.setRequestHeader('Cache-Control', 'no-transform');
                request.send(null);
            },
 
            postRequest : function(query, callback, params, api)
            {
                if (api) {
                    var url = wgServer + wgScriptPath + '/api.php?' + query;
                } else {
                    var url = wgServer + wgScriptPath + '/index.php?' + query;
                }
 
                var request = sajax_init_object() ;
                if (request == null) {
                    return null;
                }    
                request.open('POST', url, true);
                request.onreadystatechange = function () {
                    if(request.readyState==4) {
                        callback(request)
                    }
                };

                request.setRequestHeader('Content-type','application/x-www-form-urlencoded');
                request.setRequestHeader('Content-length', params.length);
                request.setRequestHeader("Pragma", "cache=yes");
                request.setRequestHeader("Cache-Control", "no-transform");

                request.send(params);
            },
 
            setupHandler : function ()
            {
                if (wgPageName == 'Special:SpamBlacklist') {
                    SBHandler.SBWrapper();
                } else {
                    SBHandler.closeRequestLinks();
                }
            }
 
        } // End of SBHandler
 
        addOnloadHook (SBHandler.setupHandler);
 
    } // End of sysop check
 
} // End of idempotency check
 
//</source>

// [[Category:Gadgets|SBHandler.js]]

