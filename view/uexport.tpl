<h3>$title</h3>


{{ for $options as $o }}
<dl>
    <dt><a href="$baseurl/$o.0">$o.1</a></dt>
    <dd>$o.2</dd>
</dl>
{{ endfor }}