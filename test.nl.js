// <source lang='javascript'>
/**
 * Protected pages
 * Written by: Erwin
 * Description: Display templates on protected pages.
 *
 */
 function protectionTemplates() {
    var content = document.getElementById('content');   
    if (content == null || document.getElementsByTagName('h1')[0] == null) {
        // There is no 'content' element and/or no h1 element.
        return false;
    }
    
    // The most restricting level of restriction (edit or move) on the current page.
    var restrictionLevel = null;
    
    // Get restriction level.
    if ((wgRestrictionEdit[0] != null && wgRestrictionEdit[0] == 'sysop')
            || (wgRestrictionMove[0] != null && wgRestrictionMove[0] == 'sysop')) {
        // Editing and/or moving the page is limited to sysops.
        restrictionLevel = 'sysop';
    } else if ((wgRestrictionEdit[0] != null && wgRestrictionEdit[0] == 'autoconfirmed')
            || (wgRestrictionMove[0] != null && wgRestrictionMove[0] == 'autoconfirmed')) {
        // Editing and/or moving the page is limited to autoconfirmed users.
        restrictionLevel = 'autoconfirmed';
    }
    
    if (restrictionLevel == null) {
        // Nothing to do. So quit.
        return false;   
    }
    
    
    // Set template title and node id.
    if (restrictionLevel == 'sysop') {
        var templateTitle = 'Gebruiker:Erwin/Klad4';
        var nodeId = 'templ_Beveiligd';
    } else if (restrictionLevel == 'autoconfirmed') {
        var templateTitle = 'Gebruiker:Erwin/Klad5';
        var nodeId = 'templ_Semibeveiligd';
    }

    // Get template from API.
    var request = sajax_init_object ();      
    request.open('GET', wgServer + wgScriptPath + '/api.php?format=json&action=parse&text={{' + templateTitle + '}}&title=' + wgTitle, true);
    request.onreadystatechange =
    function () {
        if (request.readyState != 4) return;
        if (request.status == 200 && request.responseText && request.responseText.charAt(0) == '{') {
            var json = eval ('(' + request.responseText + ')');
            if (json.parse.text['*']) {
                var divContent = json.parse.text['*'];
            }
        }
        
        if (divContent != null) {
            // We retrieved the template, so add it to the page.
            var divNode = document.createElement('div');
            divNode.id = nodeId;
            divNode.className = 'Titel_item2';
            divNode.innerHTML = divContent;
            content.insertBefore(divNode, document.getElementsByTagName('h1')[0]);
        }
    };
    request.setRequestHeader ('Pragma', 'cache=yes');
    request.setRequestHeader ('Cache-Control', 'no-transform');
    request.send (null); 
}

addOnloadHook(protectionTemplates);
// </source>

