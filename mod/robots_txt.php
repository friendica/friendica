<?php
/**
 * return the default robots.txt
 * @version 0.1.0
 */

/**
 * Simple robots.txt
 * @inheritdoc (?)
 */
function robots_txt_init(App $a) {

    /** @var string[] globally disallowed url */
    $allDisalloweds=array(
        '/settings/',
        '/admin/',
        '/message/',
    );

    header("Content-Type: text/plain");
    echo "User-agent: *\n";
    echo "Disallow:\n";
    echo "\n";
    echo "User-agent: *\n";
    foreach($allDisalloweds as $disallowed) {
        echo "Disallow: {$disallowed}\n";
    }
    killme();
}
