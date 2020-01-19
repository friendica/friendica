<div id="exception" class="generic-page-wrapper">
    <img class="hare" src="images/friendica-404_svg_flexy-o-hare.png"/>
    <h1>{{$title}}</h1>
    <p>{{$message}}</p>
{{if $trace}}
	<pre>{{$trace nofilter}}</pre>
{{/if}}
	<p><button type="button" onclick="window.history.back()" class="btn btn-primary">{{$back}}</button></p>
</div>
