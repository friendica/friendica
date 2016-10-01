<?php

require_once('include/crypto.php');

function xrd_init(&$a)
{
    $uri = urldecode(notags(trim($_GET['uri'])));

    if (substr($uri, 0, 4) === 'http') {
        $acct = false;
        $name = basename($uri);
    } else {
        $acct = true;
        $local = str_replace('acct:', '', $uri);
        if (substr($local, 0, 2) == '//') {
            $local = substr($local, 2);
        }

        $name = substr($local, 0, strpos($local, '@'));
    }

    $r = q("SELECT * FROM `user` WHERE `nickname` = '%s' LIMIT 1",
        dbesc($name)
    );
    if (! count($r)) {
        killme();
    }

    $salmon_key = salmon_key($r[0]['spubkey']);

    header('Access-Control-Allow-Origin: *');
    header("Content-type: text/xml");

    $tpl = get_markup_template('xrd_diaspora.tpl');
    $dspr = replace_macros($tpl, array(
        '$baseurl' => $a->get_baseurl(),
        '$dspr_guid' => $r[0]['guid'],
        '$dspr_key' => base64_encode(pemtorsa($r[0]['pubkey']))
    ));

    $tpl = get_markup_template('xrd_person.tpl');

    $profile_url = $a->get_baseurl().'/profile/'.$r[0]['nickname'];

    if ($acct) {
        $alias = $profile_url;
    } else {
        $alias = 'acct:'.$r[0]['nickname'].'@'.$a->get_hostname();

        if ($a->get_path()) {
            $alias .= '/'.$a->get_path();
        }
    }

    $o = replace_macros($tpl, array(
        '$nick'        => $r[0]['nickname'],
        '$accturi'     => $uri,
        '$alias'       => $alias,
        '$profile_url' => $profile_url,
        '$hcard_url'   => $a->get_baseurl() . '/hcard/'         . $r[0]['nickname'],
        '$atom'        => $a->get_baseurl() . '/dfrn_poll/'     . $r[0]['nickname'],
        '$zot_post'    => $a->get_baseurl() . '/post/'          . $r[0]['nickname'],
        '$poco_url'    => $a->get_baseurl() . '/poco/'          . $r[0]['nickname'],
        '$photo'       => $a->get_baseurl() . '/photo/profile/' . $r[0]['uid']      . '.jpg',
        '$dspr'        => $dspr,
        '$salmon'      => $a->get_baseurl() . '/salmon/'        . $r[0]['nickname'],
        '$salmen'      => $a->get_baseurl() . '/salmon/'        . $r[0]['nickname'] . '/mention',
        '$subscribe'   => $a->get_baseurl() . '/follow?url={uri}',
        '$modexp'      => 'data:application/magic-public-key,'  . $salmon_key,
        '$bigkey'      =>  salmon_key($r[0]['pubkey'])
    ));


    $arr = array('user' => $r[0], 'xml' => $o);
    call_hooks('personal_xrd', $arr);

    echo $arr['xml'];
    killme();
}
