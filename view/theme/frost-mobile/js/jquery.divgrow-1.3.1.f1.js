/*
* Copyright (c) 2010 Simon Hibbard
* 
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use,
* copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the
* Software is furnished to do so, subject to the following
* conditions:

* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
* OTHER DEALINGS IN THE SOFTWARE. 
*/

/*
* Version: V1.3.1-f1
* Release: 22-12-2010
* Based on jQuery 1.4.2
*
* 2012-10-29: Modified by Zach Prezkuta to allow dynamic full heights
*/

(function ($) {
    var divgrowid = 0;
    $.fn.divgrow = function (options) {
        var options = $.extend({}, { initialHeight: 100, moreText: "+ Show More", lessText: "- Show Less", speed: 1000, showBrackets: true }, options);

        return this.each(function () {
            divgrowid++;

            obj = $(this);

            //var fullHeight = obj.height() + 10;

            obj.css('height', options.initialHeight).css('overflow', 'hidden');
            if (options.showBrackets) {
                obj.after('<p class="divgrow-brackets">[&hellip;]</p><a href="#" class="divgrow-showmore' + " divgrow-obj-" + divgrowid + '"' + '></a>');
            }
            else {
                obj.after('<a href="#" class="divgrow-showmore' + " divgrow-obj-" + divgrowid + '"' + '></a>');
            }
            $("a.divgrow-showmore").html(options.moreText);

            $("." + "divgrow-obj-" + divgrowid).toggle(function () {
                //alert(obj.attr('class'));
                // Set the height from the elements rel value
                //var height = $(this).prevAll("div:first").attr('rel');

				var fullHeight = $(this).prevAll("div:first")[0].scrollHeight + 10;
                $(this).prevAll("div:first").animate({ height: fullHeight + "px" }, options.speed, function () { // Animation complete.

                    // Hide the overlay text when expanded, change the link text
                    if (options.showBrackets) {
                        $(this).nextAll("p.divgrow-brackets:first").fadeOut();
                    }
                    $(this).nextAll("a.divgrow-showmore:first").html(options.lessText);

                });


            }, function () {

                $(this).prevAll("div:first").stop(true, false).animate({ height: options.initialHeight }, options.speed, function () { // Animation complete.

                    // show the overlay text while closed, change the link text
                    if (options.showBrackets) {
                        $(this).nextAll("p.divgrow-brackets:first").stop(true, false).fadeIn();
                    }
                    $(this).nextAll("a.divgrow-showmore:first").stop(true, false).html(options.moreText);

                });
            });

        });
    };
})(jQuery);





