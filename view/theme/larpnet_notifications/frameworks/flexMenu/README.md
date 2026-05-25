#flexMenu 1.3
flexMenu is a jQuery plugin that lets you create responsive menus that automatically collapse into a "more" drop-down when they run out of space.  When there's only space to display one or two items, all the items collapse into a "menu" drop-down.

[Demo](http://352media.github.com/flexMenu/)

[Source on GitHub](https://github.com/352Media/flexMenu)

Written by [Ryan DeBeasi](http://www.ryandebeasi.com/) and [our fantastic contributors](https://github.com/352Media/flexMenu/graphs/contributors).

##Usage

First, download flexmenu.js from GitHub or install it with `bower install flexmenu`. Then, add a script tag that references flexMenu. For example: `<script src="flexmenu.js"></script>`.

Create an unordered list that contains your menu items. In CSS, use `display: inline-block;` or `float: left;` to get the  `li` elements to line up horizontally.

Finally, call flexMenu on an unordered list that contains your menu items:

```javascript
$('ul.menu.flex').flexMenu();
```

###AMD/RequireJS

The plugin can be loaded using an AMD loader such as RequireJS:

```javascript
require(['jquery', 'flexmenu'], function ($) {
    $('ul.menu.flex').flexMenu();
});
```

##Dependencies

###jQuery
I've tested the plugin in jQuery 1.7-1.9. It probably works on older versions, but I haven't tested on those.

###Modernizr
[Modernizr](http://modernizr.com/) is optional. If it's available, flexMenu will use it to detect touch support. If touch support is available, we'll toggle the menu on click. If touch support is not available, we'll toggle the menu on hover in/out. That way, we can avoid triggering the funky [simulated mouseover/mouseout](http://developer.apple.com/library/ios/#DOCUMENTATION/AppleApplications/Reference/SafariWebContent/HandlingEvents/HandlingEvents.html#//apple_ref/doc/uid/TP40006511-SW17) that happens on many touchscreen devices.

If Modernizr is not available, we'll always toggle the menu on click.

The zip for flexMenu includes a build of Modernizr contains only touch detection and the has-js/no-js class. Feel free to use this build, or go with more full-featured build if you're using other features. Or, if you do want to always toggle the menu on click, there is no need to include Modernizr at all - just [set up a js/no-js class](http://paulirish.com/2009/avoiding-the-fouc-v3/) and you'll be good to go.

##Advanced usage

If you're feeling fancy, you can include any of the following options when calling flexMenu:

###threshold
(integer, defaults to 2)
If there are this many items or fewer in the list, we will not display a "View More" link and will instead let the list break to the next line. This is useful in cases where adding a "view more" link would actually cause more things to break  to the next line.

###cutoff
(integer, defaults to 2)
If there is space for this many or fewer items outside of our "more" popup, just move everything into the more menu. In that case, also use linkTextAll and linkTitleAll instead of linkText and linkTitle. To disable this feature, just set this value to 0.

###linkText
(string, defaults to 'More')
What text should we display on the "view  more" link?

###linkTitle
(string, defaults to 'View More')
What should the title of the "view more" button be?

###linkTextAll
(string, defaults to 'Menu')
If we hit the cutoff and collapse all the links into the popup, what text should we display on the "menu" link?

###linkTitleAll
(string, defaults to 'Menu')
If we hit the cutoff and collapse all the links into the popup, what should the title of the "menu" link be?

###showOnHover
(boolean, defaults to 'true')
Should we we show the menu on hover? If not, we'll require a click. If we're on a touch device - or if Modernizr is not available - we'll ignore this setting and only show the menu on click. The reason for this is that touch devices emulate hover events in unpredictable ways, causing some taps to do nothing.

###undo
(boolean, defaults to 'false')
If this is true, we'll move the list items back to where they were before, and remove the "View More" link. This is useful if you actually _do_ want list items to stack in some cases, or if you want to recalculate the menu.

###popupAbsolute
(boolean, defaults to 'true')
Should we absolutely position the popup? Usually this is a good idea. That way, the popup can appear over other content and spill outside a parent that has overflow: hidden set. If you want to do something different from this in CSS, just set this option to false.

###popupClass
(string, defaults to '')
If this is set, this class will be added to the `flexMenu-popup` element.

##License

flexMenu is licensesed under the MIT License, and is free for commercial or personal use.

Copyright &copy; 2012-2014 352 Inc. & Contributors

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
