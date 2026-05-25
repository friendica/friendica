# NOTE: This is the latest version of ScrollSpy, which includes a ton of bug fixes and efficiency improvements. It's recommended that you use this version for now instead of the official (which hasn't been updated in a while).

# jQuery-ScrollSpy

An adaptation of the Mootools Scrollspy (http://davidwalsh.name/mootools-scrollspy) plugin for jQuery

(c) 2011 Samuel Alexander (https://github.com/sxalexander/jquery-scrollspy)

(c) 2015 SoftwareSpot

Released under The MIT License.

## Description:

ScrollSpy is a simple jQuery plugin for firing events based on where the user has scrolled to in a page.

## Homepage:

https://github.com/softwarespot/jquery-scrollspy

## Source:

Hosted at GitHub; browse at:

  https://github.com/softwarespot/jquery-scrollspy/tree/master

Or clone from:

    git://github.com/softwarespot/jquery-scrollspy.git

## Usage:

1. Insert the necessary elements in to your document's `<head>` section, e.g.:

```html
    <script src="jquery.min.js"></script>
    <script src="jquery.scrollspy.min.js"></script>
```

2. Initialise ScrollSpy once the DOM has been loaded:

```javascript
<script>
    $(function() {

            var $nav = $('#nav');

            $('#sticky-navigation').scrollspy({
                min: $nav.offset().top,
                onEnter: function(element, position) {
                    $nav.addClass('fixed');
                },
                onLeave: function(element, position) {
                    $nav.removeClass('fixed');
                }
            });

        });
</script>
```

Check out the /examples for more info !

## Documentation:

ScrollSpy function signature:
```javascript
    $('container').scrollspy(options, action)
```

Default options for ScrollSpy:
```javascript
// default options for ScrollSpy
var defaults = {
    // the offset to be applied to the left and top positions of the container
    buffer: 0,

    // the element to apply the 'scrolling' event to (default window)
    container: window,

    // the maximum value of the X or Y coordinate, depending on mode the selected
    max: 0,

    // the maximum value of the X or Y coordinate, depending on mode the selected
    min: 0,

    // whether to listen to the X (horizontal) or Y (vertical) scrolling
    mode: 'vertical',

    // namespace to append to the 'scroll' event
    namespace: 'scrollspy',

    // call the following callback function every time the user enters the min / max zone
    onEnter: null,

    // call the following callback function every time the user leaves the min / max zone
    onLeave: null,

    // call the following callback function every time the user leaves the top zone
    onLeaveTop: null,

    // call the following callback function every time the user leaves the bottom zone
    onLeaveBottom: null,

    // call the following callback function on each scroll event within the min and max parameters
    onTick: null,

    // call the following callback function on each scroll event when the element is inside the viewable view port
    onView: null
};
```

Events are triggered by ScrollSpy are:

    scrollTick: Fires on each scroll event within the min and max parameters:
        position: an object with the current X and Y position.
        inside: a Boolean value for whether or not the user is within the min and max parameters
        enters: the number of times the min / max has been entered.
        leaves: the number of times the min / max has been left.

    scrollEnter: Fires every time the user enters the min / max zone:
            position: an object with the current X and Y position.
            enters: the number of times the min / max has been entered.

    scrollLeave: Fires every time the user leaves the min / max zone:
            position: an object with the current X and Y position.
            leaves: the number of times the min / max has been left.

    scrollLeaveTop: Fires every time the user leaves the top zone:
            position: an object with the current X and Y position.
            leaves: the number of times the min / max has been left.

    scrollLeaveBottom: Fires every time the user leaves the bottom zone:
            position: an object with the current X and Y position.
            leaves: the number of times the min / max has been left.

    scrollView: Fires every time the element is inside the viewable view port:
            position: an object with the current X and Y position.
            leaves: the number of times the min / max has been left.

### Tidy up

To destroy ScrollSpy for a particular container, simple pass 'destroy' as the action parameter. The only options that will be honoured are `container` and `namespace`.

## A note about forking:

By forking this project you hereby grant permission for any commits to your fork to be
merged back into this repository and, with attribution, be released under the terms of
the MIT License.

## Contribute

To contribute to the project, you will first need to install [node](https://nodejs.org) globally on your system. Once installation has completed, change the working directory to the plugin's location and run the following command:

```shell
    npm install
```

After installation of the local modules, you're ready to start contributing to the project. Before you submit your PR, please don't forget to call `gulp`, which will run against [JSHint](http://jshint.com) for any errors, but will also minify the plugin.

##### Watch
Call the following command to start 'watching' for any changes to the main JavaScript file(s). This will automatically invoke JSHint and Uglify.
```shell
    gulp watch
```

##### JSHint
Call the following command to invoke JSHint and check that the changes meet the requirements set in .jshintrc.
```shell
    gulp jshint
```

##### Uglify
Call the following command to invoke Uglify, which will minify the main JavaScript file(s) and output to a .min.js file respectively.
```shell
    gulp uglify
```

##### Build
Call the following command to invoke both JSHint and Uglify.
```shell
    gulp
```
