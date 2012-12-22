<?xml version="1.0" encoding="UTF-8"?>
<me:env xmlns:me="http://salmon-protocol.org/ns/magic-env">
<me:data type="application/atom+xml">
{{$data}}
</me:data>
<me:encoding>{{$encoding}}</me:encoding>
<me:alg>{{$algorithm}}</me:alg>
<me:sig key_id="{{$keyhash}}">{{$signature}}</me:sig>
</me:env>
