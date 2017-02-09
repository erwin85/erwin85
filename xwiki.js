function showHide(domain)
{
    var node = document.getElementById(domain);
    var link = document.getElementById('l-' + domain);
    if (!node || !link) {
        return;
    }

    if (node.style.display == 'none') {
        node.style.display = 'block';
        link.innerHTML = 'Hide';
    } else {
        node.style.display = 'none';
        link.innerHTML = 'Show';
    }
}

function noLinks(content) {
    document.getElementById('nolinks').innerHTML = content;
}
