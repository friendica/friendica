<style>
figure { border: 4px #eeeeee solid; }
figure img { padding: 2px; }
figure figcaption { background: #eeeeee; color: #444444; padding: 2px; font-style: italic;}
</style>

Creating posts
===========

* [Home](help)

Here you can find an overview of the different ways to create and edit your post.

One click on the Pencil & Paper icon in the top right of your Home or Network page, or the "Share" text box, and the post editor shows up.
Below are examples of the post editor in 3 of Friendica's common themes:

<figure>
<img src="doc/img/editor_frio.png" alt="frio editor">
<figcaption>Post editor, with the <b>Frio</b> (popular default) theme.</figcaption>
</figure>
<p style="clear:both;"></p>
<figure>
<img src="doc/img/editor_vier.png" alt="vier editor" width="675">
<figcaption>Post editor, with the <b>Vier</b> theme.</figcaption>
</figure>
<p style="clear:both;"></p>

Post title is optional, you can set it by clicking on "Set title".

Posts can optionally be in one or more categories. Write category names separated by a comma to file your new post.

The Big Empty Textarea is where you write your new post.
You can simply enter your text there and click the "Share" button, and your new post will be public on your profile page and shared to your contact.

If plain text is not so exciting to you, Friendica understands BBCode to spice up your posts: bold, italic, images, links, lists..
See [BBCode tags reference](help/BBCode) page to see all what you can do.

The icons under the text area are there to help you to write posts quickly, but vary depending on the theme:

With the Frio theme, the Underline, Italics and Bold buttons should be self-explanatory.

<img src="doc/img/camera.png" width="32" height="32" alt="editor" align="left"> Upload a picture from your computer. The image will be uploaded and correct bbcode tag will be added to your post.*  In the Frio theme, use the <b>Browser</b> tab instead to Upload and/or attach content to your post.
<p style="clear:both;"></p>

<img src="doc/img/paper_clip.png" width="32" height="32" alt="paper_clip" align="left"> This depends on the theme: For Frio, this is to attach remote content - put in a URL to embed in your post, including video or audio content.  For other themes: Add files from your computer. Same as picture, but for generic attachment to the post.*
<p style="clear:both;"></p>

<img src="doc/img/chain.png" width="32" height="32" alt="chain" align="left"> Add a web address (url). Enter a URL and Friendica will add to your post a link to the url and an excerpt from the web site, if possible.
<p style="clear:both;"></p>

<img src="doc/img/video.png" width="32" height="32" alt="video" align="left"> Add a video. Enter the url to a video (ogg) or to a video page on youtube or vimeo, and it will be embedded in your post with a preview. (In the Frio theme, this is done with the paperclip as mentioned above.) Friendica is using [HTML5](http://en.wikipedia.org/wiki/HTML5_video) for embedding content. Therefore, the supported files are depending on your browser and operating system (OS). Some filetypes are WebM, MP4 and OGG.*
<p style="clear:both;"></p>

<img src="doc/img/mic.png" width="32" height="32" alt="mic" align="left"> Add an audio. Same as video, but for audio. Depending on your browser and operation system MP3, OGG and AAC are supported. Additionally, you are able to add URLs from audiohosters like Soundcloud.

<p style="clear:both;"></p>

<img src="doc/img/globe.png" width="32" height="32" alt="globe" align="left"> <b>Or</b> <img src="doc/img/frio_location.png" width="32" height="32" alt="location" align="none"> Set your geographic location. This location will be added into a Google Maps search. That's why a note like "New York" or "10004" is already enough.
<p style="clear:both;"></p>
<br />

<p style="clear:both;"></p>

These icons can change depending on the theme. Some examples:

<table>
<tr>
    <td>Vier: </td>
    <td><img src="doc/img/vier_icons.png" alt="vier.png" style="vertical-align:middle;"></td>
    <td>&nbsp;</td>
</tr>
</table>
<i><b>*</b> how to [upload](help/FAQ#upload) files</i>
<p style="clear:both;">&nbsp;</p>

**<img src="doc/img/lock.png" width="32" height="32" alt="lock icon"  style="vertical-align:middle;"> The Lock / Permissions**

In Frio, the Permissions tab, or in other themes, the Lock button, is the most important feature in Friendica. If the lock is open, your post will be public, and will show up on your profile page when strangers visit it.

Click on it and the *Permission settings* window (aka "*Access Control Selector*" or "*ACL Selector*") pops up. There you can select who can see the post.

<figure>
<img src="doc/img/acl_win.png" alt="Permission settings window">
<figcaption>Permission settings window with some contact selected</figcaption>
</figure>

Click on "show" under contact name to hide the post to everyone but selected.

Click on "Visible to everybody" to make the post public again.

If you have defined some circles, you can check "show" for circles also. All contact in that circle will see the post.
If you want to hide the post to one contact of a circle selected for "show", click "don't show" under contact name.

Click again on "show" or "don't show" to switch it off.

You can search for contacts or circles with the search box.

See also [Circles and Privacy](help/Circles-and-Privacy)
