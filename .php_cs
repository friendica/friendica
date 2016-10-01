<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('doc')
    ->exclude('images')
    ->exclude('js')
    ->exclude('library')
    ->exclude('mods')
    ->exclude('spec')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->finder($finder)
;
