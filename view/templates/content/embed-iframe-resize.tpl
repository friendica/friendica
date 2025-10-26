{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<iframe id="{{$id}}" srcdoc="{{$src}}&lt;script&gt;
function sendResize() {
    parent.postMessage({type:'resize',height:document.body.scrollHeight},'*');
}
window.onload = function() {
    sendResize();
    setTimeout(sendResize, 2000);
};
&lt;/script&gt;" width="{{$width}}" frameborder="0" allow="fullscreen, picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen sandbox="allow-same-origin allow-scripts"></iframe>
<script>
  window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'resize') {
      var iframe = document.getElementById('{{$id}}');
      if (iframe) {
        iframe.style.height = (event.data.height + 20) + 'px';
      }
    }
  });
</script>
