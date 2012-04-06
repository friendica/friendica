javascript: (function() {
    					the_url = 'localhost/view/theme/diabook/fpostit/fpostit.php?url=' + encodeURIComponent(window.location.href) + '&title=' + encodeURIComponent(document.title) + '&text=' + encodeURIComponent(''+(window.getSelection ? window.getSelection() : document.getSelection ? document.getSelection() : document.selection.createRange().text));
    						a_funct = function() {
        						if (!window.open(the_url, 'fpostit', 'location=yes,links=no,scrollbars=no,toolbar=no,width=600,height=300')) location.href = the_url};
    							if (/Firefox/.test(navigator.userAgent)) {setTimeout(a_funct, 0)} 
    							else {a_funct()}})()"