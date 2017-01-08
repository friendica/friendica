# Themes

* [Home](help)

To change the look of friendica you have to touch the themes.
The current default theme is [duepunto zero](https://github.com/friendica/friendica/tree/master/view/theme/duepuntozero) but there are numerous others.
Have a look at [friendica-themes.com](http://friendica-themes.com) for an overview of the existing themes.
In case none of them suits your needs, there are several ways to change a theme.
If you need help theming, there is a forum @[ftdevs@friendica.eu](https://friendica.eu/profile/ftdevs) where you can ask theme specific questions and present your themes.

So, how to work on the UI of friendica.

You can either directly edit an existing theme.
But you might loose your changes when the theme is updated by the friendica team.

If you are almost happy with an existing theme, the easiest way to cover your needs is to create a new theme, inheritating most of the properties of the parent theme and change just minor stuff.
The below for a more detailed description of theme heritage.

Some themes also allow users to select *variants* of the theme.
Those theme variants most often contain an additional [CSS](https://en.wikipedia.org/wiki/CSS) file to override some styling of the default theme values.
From the themes in the main repository *duepunto zero* and *vier* are using this methods for variations.
Quattro is using a slightly different approach.

Third you can start your theme from scratch.
Which is the most complex way to change friendicas look.
But it leaves you the most freedom.
So below for a *detailed* description and the meaning of some special files.

### Styling

If you want to change the styling of a theme, have a look at the themes CSS file.
In most cases, you can found these in

    /view/theme/**your-theme-name**/style.css

sometimes, there is also a file called style.php in the theme directory.
This is only needed if the theme allowes the user to change certain things of the theme dynamically.
Say the font size or set a background image.

### Templates

If you want to change the structure of the theme, you need to change the templates used by the theme.
Friendica themes are using [SMARTY3](http://www.smarty.net/) for templating.
The default template can be found in

    /view/templates

if you want to override any template within your theme create your version of the template in

    /view/theme/**your-theme-name**/templates

any template that exists there will be used instead of the default one.

### Javascript

The same rule applies to the JavaScript files found in

    /js

they will be overwritten by files in

    /view/theme/**your-theme-name**/js.

## Expand an existing Theme

### Theme Variations

Many themes are more *theme families* then only one theme.
*duepunto zero* and *vier* allow easily to add new theme variation.
We will go through the process of creating a new variation for *duepunto zero*.
The same  (well almost, some names change) procedure applies to the *vier* theme.
And similar steps are needed for *quattro* but this theme is using [lessc](http://lesscss.org/#docs) to maintaine the CSS files..

In

    /view/theme/duepuntozero/deriv

you find a couple of CSS files that define color derivations from the duepunto theme.
These resemble some of the now as unsupported marked themes, that were inherited by the duepunto theme.
Darkzero and Easter Bunny for example.

The selection of the colorset is done in a combination of a template for a new form in the settings and aome functions in the theme.php file.
The template (theme_settings.tpl)

    {{include file="field_select.tpl" field=$colorset}}
    <div class="settings-submit-wrapper">
        <input type="submit" value="{{$submit}}" class="settings-submit" name="duepuntozero-settings-submit" />
    </div>

defines a formular consisting of a [select](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/select) pull-down which contains all aviable variants and s submit button.
See the documentation about [SMARTY3 templates](/help/snarty3-templates.md) for a summary of friendica specific blocks other then the select element.
But we don't really need to change anything at the template itself.

The template alone wont work though.
You make friendica aware of its existance and tell it how to use the template file, by defining a config.php file.
It needs to define at lest the following functions

* theme_content
* theme_post

and may also define functions for the admin interface

* theme_admin
* theme_admin_post.

theme_content and theme_admin are used to make the form available in the settings, repectively the admin panel.
The _post functions handle the processing of the send form, in this case they save to selected variand in friendicas database.

To make your own variation appear in the menu, all you need to do is to create a new CSS file in the deriv directoy and include it in the array in the config.php:

    $colorset = array(
        'default'=>t('default'),
        'greenzero'=>t('greenzero'),
        'purplezero'=>t('purplezero'),
        'easterbunny'=>t('easterbunny'),
        'darkzero'=>t('darkzero'),
        'comix'=>t('comix'),
        'slackr'=>t('slackr'),
    );

the 1st part of the line is the name of the CSS file (without the .css) the 2nd part is the common name of the variant.
Calling the t() function with the common name makes the string translateable.
The selected 1st part will be saved in the database by the theme_post function.

    function theme_post(App &$a){
        // non local users shall not pass
        if (! local_user()) {
            return;
        }
        // if the one specific submit button was pressed then proceed
        if (isset($_POST['duepuntozero-settings-submit'])){
            // and save the selection key into the personal config of the user
            set_pconfig(local_user(), 'duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
        }
    }

Now that this information is set in the database, what should friendica do with it?
For this, have a look at the theme.php file of the *duepunto zero*.
There you'll find somethink alike

        $colorset = get_pconfig( local_user(), 'duepuntozero','colorset');
        if (!$colorset)
            $colorset = get_config('duepuntozero', 'colorset');
        if ($colorset) {
            if ($colorset == 'greenzero')
                $a->page['htmlhead'] .= '<link rel="stylesheet" href="view/theme/duepuntozero/deriv/greenzero.css" type="text/css" media="screen" />'."\n";
            /* some more variants */
        }

which tells friendica to get the personal config of a user.
Check if it is set and if not look for the global config.
And finally if a config for the colorset was found, apply it by adding a link to the CSS file into the HTML header of the page.
So you'll just need to add a if selection, fitting your variant keyword and link to the CSS file of it.

Done.
Now you can use the variant on your system.
But remember once the theme.php or the config.php you have to readd your variant to them.
If you think your color variation could be benifical for other friendica users as well, feel free to generate a pull request at github so we can include your work into the repository.

### Inheritation

Say, you like the duepuntozero but you want to have the content of the outer columns  left and right exchanged.
That would be not a color variation as shown above.
Instead we will create a new theme, duepuntozero_lr, inherit the properties of duepuntozero and make small changes to the underlying php files.

So create a directory called duepunto_lr and create a file called theme.php with your favorite text editor.
The content of this file should be something like

    <?php
    /* meta informations for the theme, see below */
    function duepuntozero_lr_init(App &$a) {
        $a-> theme_info = array(
            'extends' => 'duepuntozero'.
        );
        set_template_engine($a, 'smarty3');
        /* and more stuff e.g. the JavaScript function for the header */
    }

Next take the default.php file found in the /view direcotry and exchange the aside and right_aside elements.
So the central part of the file now looks like this:

    <body>
        <?php if(x($page,'nav')) echo $page['nav']; ?>
        <aside><?php if(x($page,'right_aside')) echo $page['right_aside']; ?></aside>
        <section><?php if(x($page,'content')) echo $page['content']; ?>
                <div id="page-footer"></div>
        </section>
        <right_aside><?php if(x($page,'aside')) echo $page['aside']; ?></right_aside>
        <footer><?php if(x($page,'footer')) echo $page['footer']; ?></footer>
    </body>

Finally we need a style.css file, inheriting the definitions from the parent theme and containing out changes for the new theme.
***Note***:You need to create the style.css and at lest import the base CSS file from the parent theme.

    @import url('../duepuntozero/style.css');

Done.
But I agree it is not really useful at this state.
Nevertheless, to use it, you just need to activate in the admin panel.
That done, you can select it in the settings like any other activated theme.

## Creating a Theme from Scratch

Keep patient.
Basically what you have to do is identifying which template you have to change so it looks more like what you want.
Adopt the CSS of the theme accordingly.
And iterate the process until you have the theme the way you want it.

*Use the source Luke.* and don't hesitate to ask in @[ftdevs](https://friendica.eu/profile/ftdevs) or @[helpers](https://helpers.pyxis.uberspace.de/profile/helpers).

## Special Files

### unsupported

If a file with this name (which might be empty) exists in the theme directory, the theme is marked as *unsupported*.
An unsupported theme may not be selected by a user in the settings.
Users who are already using it wont notice anything.

### README(.md)

The contents of this file, with or without the .md which indicates [Markdown](https://daringfireball.net/projects/markdown/) syntax, will be displayed at most repository hosting services and in the theme page within the admin panel of friendica.

This file should contain information you want to let others know about your theme.

### screenshot.[png|jpg]

If you want to have a preview image of your theme displayed in the settings you should take a screenshot and save it with this name.
Supported formats are PNG and JPEG.

### theme.php

This is the main definition file of the theme.
In the header of that file, some meta information are stored.
For example, have a look at the theme.php of the *quattro* theme:

    <?php
    /**
     * Name: Quattro
     * Version: 0.6
     * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
     * Maintainer: Fabio <http://kirgroup.com/profile/fabrixxm>
     * Maintainer: Tobias <https://f.diekershoff.de/profile/tobias>
     */

You see the definition of the themes name, it's version and the initial author of the theme.
These three information should be listed.
If the original author is not anymore working on the theme, but a maintainer has taken over, the maintainer should be listed as well.
The information from the theme header will be displayed in the admin panelö.

Next crucial part of the theme.php file is a definition of an init function.
The name of the function is <theme-name>_init.
So in the case of quattro it is

    function quattro_init(App &$a) {
      $a->theme_info = array();
      set_template_engine($a, 'smarty3');
    }

Here we have set the basic theme information, in this case they are empthy.
But the array needs to be set.
And we have set the template engine that should be used by friendica for this theme.
At the moment you should use the *smarty3* engine.
There once was a friendica specific templating engine as well but that is not used anymore.
If you like to use another templating engine, please implement it.

When you want to inherit stuff from another theme you have to *announce* this in the theme_info:

    $a->theme_info = array(
      'extends' => 'duepuntozero',
    );

which declares *duepuntozero* as parent of the theme.

If you want to add something to the HTML header of the theme, one way to do so is by adding it to the theme.php file.
To do so, add something alike

    $a->page['htmlhead'] .= <<< EOT
    /* stuff you want to add to the header */
    EOT;

The $a variable holds the friendica application.
So you can access the properties of this friendica session from the theme.php file as well.

### default.php

This file covers the structure of the underlying HTML layout.
The default file is in

    /view/default.php

if you want to change it, say adding a 4th column for banners of your favourite FLOSS projects, place a new default.php file in your theme directory.
As with the theme.php file, you can use the properties of the $a variable with holds the friendica application to decide what content is displayed.