//<source lang="javascript">
 
/*
    Support for local hiding of user names.

    Authors: [[:m:User:DerHexer]] and [[:m:User:Erwin]], July 2009 - August 2009
    License: Quadruple licensed GFDL, GPL, LGPL and Creative Commons Attribution 3.0 (CC-BY-3.0)

    This tool uses code from SBHandler at Meta.
        [[:m:MediaWiki:Gadget-SBHandler.js]]
        Author: [[:m:User:Erwin]], October 2008 - February 2009
        License: Quadruple licensed GFDL, GPL, LGPL and Creative Commons Attribution 3.0 (CC-BY-3.0)
        
    Which in turn uses code from DelReqHandler at Commons.
        [[:Commons:MediaWiki:Gadget-DelReqHandler.js]] (oldid=15093612)
        Author: [[:Commons:User:Lupo]], October 2007 - January 2008
        License: Quadruple licensed GFDL, GPL, LGPL and Creative Commons Attribution 3.0 (CC-BY-3.0)

    Tested only in Firefox.
*/

/**** Guard against double inclusions */
 
if (typeof (LHHandler) == 'undefined') {

    var LHUtils =
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

    } // End of LHUtils
 
     /**** Enable the whole shebang only for stewards. */
    if (LHUtils.userIsInGroup ('steward')) {
        
        var LHHandler =
        {
            user            : '',
            projects        : 0,
            completed       : 0,
            error           : false,
            
            addLinks : function ()
            {               
                // Add div with progress information and link to locally hide users.
                d = document.createElement('div');
                d.setAttribute('style', 'border: 1px solid; padding: 5px; margin: 25px; float:right; width:300px; min-height:200px;');
                
                // Add container for spinner.
                dspinner = document.createElement('div');
                dspinner.setAttribute('style', 'float:right; width: 30px');
                dspinner.setAttribute('id', 'dspinner');
                d.appendChild(dspinner);
                
                // Add link to hide users.
                s = document.createElement('div');
                s.setAttribute('style', 'text-align:center; font-weight:bold;');
                a = document.createElement('a');
                a.setAttribute('href', 'javascript:LHHandler.parsePage()');
                a.appendChild(document.createTextNode('Hide local users'));
                s.appendChild(a);
                d.appendChild(s);
                
                // Add list for logging.
                ul = document.createElement('ul');
                ul.setAttribute('id', 'mw-centralauth-localhide-status');
                d.appendChild(ul);
                document.getElementById('bodyContent').insertBefore(d, document.getElementById('bodyContent').firstChild);
                LHHandler.user = document.getElementById('target').value;
            }, // End addLinks()
 
            parsePage : function ()
            {
                if (document.getElementById('mw-centralauth-status-hidden-oversight').checked == true) {
                    // Fresh start. No errors yet.
                    LHHandler.error = false;
                    
                    // Add spinner.
                    dspinner = document.getElementById('dspinner');
                    img = document.createElement('img');
                    img.setAttribute('id', 'hideuserrunning');
                    img.setAttribute('height', '20px');
                    img.setAttribute('width', '20px');
                    img.setAttribute('alt', 'script running');
                    img.setAttribute('src', 'http://upload.wikimedia.org/wikipedia/commons/3/32/Loader3.gif');
                    dspinner.appendChild(img);
 
                    // Get projects.
                    var trs = document.getElementById('mw-wikis-list').getElementsByTagName('table')[0].getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                    LHHandler.projects = trs.length-1;
                                        
                    // Loop over projects.
                    for (i=0; i<(trs.length-1); i++) {
                        var project = trs[i].getElementsByTagName('td')[1].getElementsByTagName('a')[0].innerHTML.split('.');
                        project = project[1]+'/'+project[0];
                        if(project=='mediawiki/www')project='wikipedia/mediawiki';
                        if(project=='wikimedia/species')project='wikipedia/species';
                        if(project=='wikimedia/meta')project='wikipedia/meta';
                        if(project=='wikimedia/commons')project='wikipedia/commons';
                        if(project=='org/wikisource')project='wikipedia/sources';
                        LHHandler.retrieveLog(project);
                    }
                } else {
                    LHHandler.logMsg('Please lock and hide the global account first.', true);
                }
            }, // End parsePage()
            
            retrieveLog : function (project)
            {
                query = 'https://secure.wikimedia.org/' + project + '/w/api.php'
                query += '?format=xml&action=query&list=blocks&bkusers=' + encodeURIComponent(LHHandler.user);
                LHHandler.getRequest(query, LHHandler.checkLog, project);
            }, //End retrieveLog()        
        
            checkLog : function (request, project)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    blocklog = xml.getElementsByTagName('block');
                    if(!blocklog[0] || (blocklog[0] && (blocklog[0].getAttribute("hidden") == null))) {
                        LHHandler.getToken(project);
                    } else {
                        LHHandler.logMsg('Already hidden on ' + project + '.');
                        LHHandler.checkStatus()
                    }
                } else {
                    // Just do it.
                    LHHandler.getToken(project)
                }
            }, //End checkLog()
                       
            getToken : function (project)
            {
                query = 'https://secure.wikimedia.org/' + project + '/w/api.php'
                query += '?format=xml&action=query&prop=info&intoken=block&titles=' + LHHandler.user;
                LHHandler.getRequest(query, LHHandler.blockUser, project);
            }, // End getToken()
            
            blockUser : function (request, project)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    page = xml.getElementsByTagName('page');
                    if (!page[0] || (page[0] && (page[0].getAttribute('blocktoken') == null))) {
                        LHHandler.logMsg('Could not get token on ' + project + '.', true);
                        LHHandler.checkStatus()
                    } else {
                        token = page[0].getAttribute('blocktoken');
                    }
                    query = 'https://secure.wikimedia.org/' + project + '/w/api.php?format=xml'
                    params = 'action=block&user=' + encodeURIComponent(LHHandler.user) + '&expiry=infinite&hidename=1&reblock=1'
                    params += '&nocreate=1&autoblock=1&noemail=1&reason=Abusive%20user%20name&token=' + encodeURIComponent(token);
                    LHHandler.postRequest(query, LHHandler.checkBlock, params, project);
                } else {
                    LHHandler.logMsg('Could not get token on ' + project + '.', true);
                    LHHandler.checkStatus()
                }
            }, //End blockUser()
            
            checkBlock : function (request, project)
            {
                var xml = request.responseXML;
                if ( xml != null ) {
                    block = xml.getElementsByTagName('block');
                    if(!block[0] || (block[0] && (block[0].getAttribute('hidename') == null))) {
                        LHHandler.logMsg('Not hidden on ' + project + '.', true);
                    } else {
                        LHHandler.logMsg('Hidden on ' + project + '.');
                    }
                } else {
                    LHHandler.logMsg('Could not check whether hiding succeeded on ' + project + '.', true);
                }
                LHHandler.checkStatus()
            }, //End checkBlock()  
            
            checkStatus : function ()
            {
                // Checking status means that the process for a project has
                // ended.
                LHHandler.completed += 1;
                
                // Set image accordingly.
                if (LHHandler.completed == LHHandler.projects) {
                    img = document.createElement('img');
                    img.setAttribute('id', 'hideuserrunning');
                    img.setAttribute('height', '20px');
                    img.setAttribute('width', '20px');
                    if (LHHandler.error) {
                        alt = 'Script stopped with errors';
                        src = 'http://upload.wikimedia.org/wikipedia/commons/thumb/a/a2/X_mark.svg/525px-X_mark.svg.png';
                    } else {
                        alt = 'Script stopped';
                        src = 'http://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Yes_check.svg/600px-Yes_check.svg.png';
                    }
                    img.setAttribute('src', src);
                    img.setAttribute('alt', alt);
                    document.getElementById('dspinner').replaceChild(img, document.getElementById('hideuserrunning'));
                }      
            }, //End checkStatus()
            
            logMsg : function (msg, error)
            {
                li = document.createElement('li');
                if (error) {
                    LHHandler.error = true;
                    li.setAttribute('style', 'color:red; font-weight:bold;');
                }
                li.appendChild(document.createTextNode(msg));
                document.getElementById('mw-centralauth-localhide-status').appendChild(li);
            }, //End logMsg()
            
            getRequest : function(url, callback, args)
            {
                var request = sajax_init_object() ;
                if (request == null) {
                    return null;
                }    
                request.open('GET', url, true);
                request.onreadystatechange = function () {
                    if(request.readyState==4) {
                        callback(request, args)
                    }
                };
                request.setRequestHeader('Pragma', 'cache=yes');
                request.setRequestHeader('Cache-Control', 'no-transform');
                request.send(null);
            },
 
            postRequest : function(url, callback, params, args)
            {
                var request = sajax_init_object() ;
                if (request == null) {
                    return null;
                }    
                request.open('POST', url, true);
                request.onreadystatechange = function () {
                    if(request.readyState==4) {
                        callback(request, args)
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
                /**
                 * Only for [[Special:CentralAuth]] via a secure connection.
                 * Secure connection is needed for all projects to be at the
                 * same domain, such that we can make XMLRequests to all
                 * projects.
                 */
                 
                if (wgServer.search(/secure/)==-1) return;
                if (wgCanonicalSpecialPageName != 'CentralAuth') return;
                LHHandler.addLinks();
            }
 
        } // End of LHHandler
 
        addOnloadHook (LHHandler.setupHandler);
 
    } // End of steward check
 
} // End of idempotency check
 
//</source>

// [[Category:Gadgets|LHHandler.js]]

