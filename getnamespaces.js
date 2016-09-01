var xhrq = new XMLHttpRequest;
 
function NamespaceLookup()
{
	xhrq = new XMLHttpRequest;
    var lang  = document.forms[0].elements["lang"].value;
    var family = document.forms[0].elements["family"].value;
    if (family != "commons" && family != "meta" && lang == '') {
        return false;
    }
	xmlurl = "getnamespaces.php?lang=" + lang + "&family=" + family
	xhrq.open("GET", xmlurl, true);
	try
	{
		xhrq.overrideMimeType('text/xml');
	}
	catch (e)
	{
		//xhrq.overridemimetype niet ondersteund
	}
	xhrq.onreadystatechange = NamespaceReadyStateChange;		
	xhrq.send(null);
}

function NamespaceReadyStateChange()
{
	if (xhrq.readyState != 4){
		return;
	}
    
    if (xhrq.status == 200) {
    	result = xhrq.responseXML;
    	 // Get all the zip code tags returned from the request.
        var els = result.getElementsByTagName("namespace");
        var error = false;
        //Clear select
        document.forms[0].elements["namespaces"].options.length=0
        // Add an option for all namespaces
        var option = document.createElement("OPTION");
        option.text = "All";
        option.value = "-1";    
        
        try {
            document.forms[0].elements["namespaces"].add(option, null);
        } catch(ex) {
            // For IE.
            document.forms[0].elements["namespaces"].add(option);
        }
        
        // Add an option for the main namespace
        var option = document.createElement("OPTION");
        option.text = "Articles";
        option.value = "0";
        
        try {
            document.forms[0].elements["namespaces"].add(option, null);
        } catch(ex) {
            // For IE.
            document.forms[0].elements["namespaces"].add(option);
        }

        // Add an option to to the drop-down list for each zip code
        // returned from the request.
        for (var i = 0; i < els.length; i++) {
            option = document.createElement("OPTION");
            option.text = els[i].getAttribute('ns_name');
            option.value = els[i].getAttribute('ns_id');
            if (els[i].getAttribute('ns_name') == 'error') {
                error = true;
            }
            
            try {
                document.forms[0].elements["namespaces"].add(option, null);
            } catch(ex) {
                // For IE.
                document.forms[0].elements["namespaces"].add(option);
            }
        }        
        // Show the drop down list and set focus on it.
        if (error == false) {
            document.getElementById("namespacesstatus").innerHTML = "";
            document.forms[0].elements["namespaces"].style.visibility = "";
        } else {
            document.getElementById("namespacesstatus").innerHTML = "Could not lookup namespaces. Please check the selected project.";
            document.forms[0].elements["namespaces"].style.visibility = "hidden";
        }
    }
}
