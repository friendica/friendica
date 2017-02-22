<style>
figure { border: 4px #eeeeee solid; }
figure img { padding: 2px; }
figure figcaption { background: #eeeeee; color: #444444; padding: 2px; font-style: italic;}
</style>

Creating posts
===========

* [Home](help)

Here you can find an overview of the different ways to create and edit your post.

One click on "Share" text box on top of your Home or Network page, and the post editor shows up:

<figure>
<img src="doc/img/friendica_editor.png" alt="default editor">
<figcaption>Default post editor, with default Friendica theme (duepuntozero)</figcaption>
</figure>

Post title is optional, you can set it clicking on "Set title".

Posts can optionally be in one or more categories. Write categories name separated by a comma to file your new post.

The Big Empty Textarea is where you write your new post.
You can simply enter your text there and click "Share" button, and your new post will be public on your profile page and shared to your contact.

If plain text is not so exciting to you, Friendica understands BBCode to spice up your posts: bold, italic, images, links, lists..
See [BBCode tags reference](help/BBCode) page to see all what you can do.

The icons under the text area are there to help you to write posts quickly:

<img src="doc/img/camera.png" width="32" height="32" alt="editor" align="left" style="padding-bottom: 20px;"> Upload a picture from your computer. The image will be uploaded and correct bbcode tag will be added to your post.*
<p style="clear:both;"></p>

<img src="doc/img/paper_clip.png" width="32" height="32" alt="paper_clip" align="left"> Add files from your computer. Same as picture, but for generic attachment to the post.*
<p style="clear:both;"></p>

<img src="doc/img/chain.png" width="32" height="32" alt="chain" align="left"> Add a web address (url). Enter an url and Friendica will add to your post a link to the url and an excerpt from the web site, if possible.
<p style="clear:both;"></p>

<img src="doc/img/video.png" width="32" height="32" alt="video" align="left"> Add a video. Enter the url to a video (ogg) or to a video page on youtube or vimeo, and it will be embedded in your post with a preview. Friendica is using [HTML5](http://en.wikipedia.org/wiki/HTML5_video) for embedding content. Therefore, the supported files are depending on your browser and operating system (OS). Some filetypes are WebM, MP4 and OGG.*
<p style="clear:both;"></p>

<img src="doc/img/mic.png" width="32" height="32" alt="mic" align="left" style="padding-bottom: 20px;"> Add an audio. Same as video, but for audio. Depending on your browser and operation system MP3, OGG and AAC are supported. Additionally, you are able to add URLs from audiohosters like Soundcloud.

<p style="clear:both;"></p>

<img src="doc/img/globe.png" width="32" height="32" alt="globe" align="left"> Set your geographic location. This location will be added into a Google Maps search. That's why a note like "New York" or "10004" is already enough.
<p style="clear:both;"></p>

<i>* how to [upload](help/FAQ#upload) files</i>

Those icons can change with themes. Some examples:

<table>
<tr>
    <td>Darkbubble: </td>
    <td><img src="doc/img/editor_darkbubble.png" alt="darkbubble.png" style="vertical-align:middle;"></td>
    <td><i>(inkl. smoothly, testbubble)</i></td>
</tr>
<tr>
    <td>Frost: </td>
    <td><img src="doc/img/editor_frost.png" alt="frost.png" style="vertical-align:middle;"> </td>
    <td>&nbsp;</td>
</tr>
<tr>
    <td>Vier: </td>
    <td><img src="doc/img/editor_vier.png" alt="vier.png" style="vertical-align:middle;"></td>
    <td><i>(inkl. dispy)</i></td>
</tr>
</table>
<p style="clear:both;">&nbsp;</p>
<p style="clear:both;">&nbsp;</p>

**<img src="doc/img/lock.png" width="32" height="32" alt="lock icon"  style="vertical-align:middle;"> The lock**

The last button, the Lock, is the most important feature in Friendica. If the lock is open, your post will be public, and will shows up on your profile page when strangers visit it.

Click on it and the *Permission settings* window (aka "*Access Control Selector*" or "*ACL Selector*") pops up. There you can select who can see the post.

<figure>
<img src="doc/img/acl_win.png" alt="Permission settings window">
<figcaption>Permission settings window with some contact selected</figcaption>
</figure>

Click on "show" under contact name to hide the post to everyone but selected.

Click on "Visible to everybody" to make the post public again.

If you have defined some groups, you can check "show" for groups also. All contact in that group will see the post.
If you want to hide the post to one contact of a group selected for "show", click "don't show" under contact name.

Click again on "show" or "don't show" to switch it off.

You can search for contacts or groups with the search box.

See also [Group and Privacy](help/Groups-and-Privacy)