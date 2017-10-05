*******************
Text_LanguageDetect
*******************
PHP library to identify human languages from text samples.
Returns confidence scores for each.


Installation
============

PEAR
----
::

    $ pear install Text_LanguageDetect

Composer
--------
::

    $ composer require pear/text_languagedetect


Usage
=====
Also see the examples in the ``docs/`` directory and
the `official documentation`__.

__ http://pear.php.net/package/Text_LanguageDetect/docs

Language detection
------------------
Simple language detection::

    <?php
    require_once 'Text/LanguageDetect.php';

    $text = 'Was wäre, wenn ich Ihnen das jetzt sagen würde?';

    $ld = new Text_LanguageDetect();
    $language = $ld->detectSimple($text);

    echo $language;
    //output: german

Show the three most probable languages with their confidence score::

    <?php
    require_once 'Text/LanguageDetect.php';

    $text = 'Was wäre, wenn ich Ihnen das jetzt sagen würde?';

    $ld = new Text_LanguageDetect();
    //3 most probable languages
    $results = $ld->detect($text, 3);

    foreach ($results as $language => $confidence) {
        echo $language . ': ' . number_format($confidence, 2) . "\n";
    }

    //output:
    //german: 0.35
    //dutch: 0.25
    //swedish: 0.20
    ?>


Language code
-------------
Instead of returning the full language name, ISO 639-2 two and three
letter codes can be returned::

    <?php
    require_once 'Text/LanguageDetect.php';
    $ld = new Text_LanguageDetect();

    //will output the ISO 639-1 two-letter language code
    // "de"
    $ld->setNameMode(2);
    echo $ld->detectSimple('Das ist ein kleiner Text') . "\n";

    //will output the ISO 639-2 three-letter language code
    // "deu"
    $ld->setNameMode(3);
    echo $ld->detectSimple('Das ist ein kleiner Text') . "\n";
    ?>


Supported languages
===================
- albanian
- arabic
- azeri
- bengali
- bulgarian
- cebuano
- croatian
- czech
- danish
- dutch
- english
- estonian
- farsi
- finnish
- french
- german
- hausa
- hawaiian
- hindi
- hungarian
- icelandic
- indonesian
- italian
- kazakh
- kyrgyz
- latin
- latvian
- lithuanian
- macedonian
- mongolian
- nepali
- norwegian
- pashto
- pidgin
- polish
- portuguese
- romanian
- russian
- serbian
- slovak
- slovene
- somali
- spanish
- swahili
- swedish
- tagalog
- turkish
- ukrainian
- urdu
- uzbek
- vietnamese
- welsh


Links
=====
Homepage
  http://pear.php.net/package/Text_LanguageDetect
Bug tracker
  http://pear.php.net/bugs/search.php?cmd=display&package_name[]=Text_LanguageDetect
Documentation
  http://pear.php.net/package/Text_LanguageDetect/docs
Unit test status
  https://travis-ci.org/pear/Text_LanguageDetect

  .. image:: https://travis-ci.org/pear/Text_LanguageDetect.svg?branch=master
     :target: https://travis-ci.org/pear/Text_LanguageDetect
