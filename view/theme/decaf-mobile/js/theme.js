// For Firefox < 3.6, which doesn't support document.readyState
// verify that document.readyState is undefined
// verify that document.addEventListener is there
// these two conditions are basically telling us
// we are using Firefox < 3.6
/*if(document.readyState == null && document.addEventListener){
    // on DOMContentLoaded event, supported since ages
    document.addEventListener("DOMContentLoaded", function DOMContentLoaded(){
        // remove the listener itself
        document.removeEventListener("DOMContentLoaded", DOMContentLoaded, false);
        // assign readyState as complete
        document.readyState = "complete";
    }, false);
    // set readyState = loading or interactive
    // it does not really matter for this purpose
    document.readyState = "loading";
}*/

document.addEventListener('DOMContentLoaded', function(){

	if(typeof window.AjaxUpload != "undefined") {
		var uploader = new window.AjaxUpload(
			window.imageUploadButton,
			{ action: 'wall_upload/'+window.nickname,
				name: 'userfile',
				onSubmit: function(file,ext) { $('#profile-rotator').show(); },
				onComplete: function(file,response) {
					var currentText = $(window.jotId).val();
					$(window.jotId).val(currentText + response);
					$('#profile-rotator').hide();
				}				 
			}
		);

		if(document.getElementById('wall-file-upload') != null) {
			var file_uploader = new window.AjaxUpload(
				'wall-file-upload',
				{ action: 'wall_attach/'+window.nickname,
					name: 'userfile',
					onSubmit: function(file,ext) { $('#profile-rotator').show(); },
					onComplete: function(file,response) {
						var currentText = $(window.jotId).val();
						$(window.jotId).val(currentText + response);
						$('#profile-rotator').hide();
					}				 
				}
			);
		}
	}

});

function confirmDelete(f) {
	response = confirm(window.delItem);
	if(response && typeof f == 'function') {
		f();
	}
	return response;
}

function changeHref(elemId, url) {
	elem = document.getElementById(elemId);
	elem.href = url;
}

function remove(elemId) {
	elem = document.getElementById(elemId);
	elem.parentNode.removeChild(elem);
}

function openClose(el) {}

// It's better to separate Javascript from the HTML, but the wall_thread
// items require more work to find since they contain the item ID in the id field
//document.getElementById('photo-album-edit-drop').onclick = function(){return confirmDelete(function(){remove('photo-album-edit-form-confirm');});}
//document.getElementById('photo-edit-delete-button').onclick = function(){return confirmDelete(function(){remove('photo-edit-form-confirm');});}

