//Based on Luxo's contributions.php
var alreadyload = false;

function stopnow()//protect database for overload
{
    if(alreadyload == false)
    {
        var hasstopped = false;
      
        if(self.stop) {
            stop();
            hasstopped = true;
        } else if(document.execCommand) {
            document.execCommand('Stop'); //IE-Hack
            hasstopped = true;
        }
        
        alreadyload = true;

        if(hasstopped == true)
        {
            var Textknoten = document.createTextNode("ERROR: maximal load time exceeded!");
            var knoten = document.getElementById("laden");

            while (knoten.hasChildNodes()) {
                knoten.removeChild(knoten.lastChild);
            }
            knoten.appendChild(Textknoten);
            knoten.style.color = "red";
            knoten.style.fontSize = "large";

            window.alert("ERROR: maximal load time exceeded!");
        }
    }
}

window.setTimeout("stopnow()", 320000);

window.onload = function (){
    alreadyload = true;
    document.getElementById("progress").style.display = 'none';
}

//actuallload("53 %", "(Scanning lv.wikipedia.org)","53");
function updateProgress(procent, status, bar)
{
    var docuid = document.getElementById("procent");
    var docuidload = document.getElementById("projectload");


    docuid.firstChild.data = procent;
    docuidload.firstChild.data = status;

    //window.defaultStatus = status;
    if(showbalk) {
        document.getElementById("bar" + bar).style.color = 'rgb(102, 102, 102)';
    }
}
