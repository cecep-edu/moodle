function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

if (getParameterByName("cname") != "") {
    document.getElementById('id_message').value = 'Tengo una duda con el curso ' + getParameterByName("cname") + "\r\r";
}

