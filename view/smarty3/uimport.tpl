<form action="uimport" method="post" id="uimport-form" enctype="multipart/form-data">
<h1>{{$import.title}}</h1>
    <p>{{$import.intro}}</p>
    <p>{{$import.instruct}}</p>
    <p><b>{{$import.warn}}</b></p>
     {{include file="field_custom.tpl" field=$import.field}}
     
     
	<div id="register-submit-wrapper">
		<input type="submit" name="submit" id="register-submit-button" value="{{$regbutt}}" />
	</div>
	<div id="register-submit-end" ></div>    
</form>
