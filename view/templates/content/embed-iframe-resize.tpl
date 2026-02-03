{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<span class="embedded-media">
  <iframe class="embed" id="{{$id}}" srcdoc="&lt;!doctype html&gt;&lt;html&gt;&lt;head&gt;&lt;style&gt;html,body{margin:0;padding:0;height:100%;}&lt;/style&gt;&lt;/head&gt;&lt;body&gt;{{$src}}&lt;script&gt;
  window.onload = function() {
      parent.postMessage({type:'resize-{{$id}}',height:document.body.scrollHeight},'*');
      setTimeout(function() {parent.postMessage({type:'resize-{{$id}}',height:document.body.scrollHeight},'*');}, 2000);
  };
  &lt;/script&gt;&lt;/body&gt;&lt;/html&gt;" width="{{$width}}" scrolling="no" frameborder="0" allow="fullscreen, picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen sandbox="allow-same-origin allow-scripts allow-popups"></iframe>
  <script>
    window.addEventListener('message', function(event) {
      if (event.data && event.data.type === 'resize-{{$id}}') {
        if (document.getElementById('{{$id}}')) {
          document.getElementById('{{$id}}').style.height = event.data.height + 'px';
        }
      }
    });
  </script>
</span>