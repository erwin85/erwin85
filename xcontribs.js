var pop = document.getElementById('popup');

var xoffset = -110;
var yoffset = -30;

document.onmousemove = function(e) {
  var x, y, right, bottom;
  
  try { x = e.pageX; y = e.pageY; } // FF
  catch(e) { x = event.x; y = event.y; } // IE

  right = (document.documentElement.clientWidth || document.body.clientWidth || document.body.scrollWidth);
  bottom = (window.scrollY || document.documentElement.scrollTop || document.body.scrollTop) + (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || document.body.scrollHeight);

  x += xoffset;
  y += yoffset;

  if(x > right-pop.offsetWidth)
    x = right-pop.offsetWidth;
 
  if(y > bottom-pop.offsetHeight)
    y = bottom-pop.offsetHeight;

  pop.style.top = y+'px';
  pop.style.left = x+'px';

}

function popup(text) {
  pop.innerHTML = text;
  pop.style.display = 'block';
}

function popout() {
  pop.style.display = 'none';
}

function popupProject(project, timestamp, edits, rights) {
    content = '<table style="width: 250px; background-color:#eee;"><tbody>';
    content += '<tr><td>Project:</td><td style="text-align:right;">' + project + '</td></tr>';
    content += '<tr><td>Registration:</td><td style="text-align:right;">' + timestamp + '</td></tr>';
    content += '<tr><td>Edits:</td><td style="text-align:right;">' + edits + '</td></tr>';
    content += '<tr><td>Rights:</td><td style="text-align:right;">' + rights + '</td></tr>';
    content += '</tbody></table>';
    popup(content);
}
