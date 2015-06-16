<?php

$config = Symfony\CS\Config\Config::create()
    // use SYMFONY_LEVEL:
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    // and extra fixers:
    ->fixers([]);

if (null === $input->getArgument('path')) {
    $config
        ->finder(
            Symfony\CS\Finder\DefaultFinder::create()
                ->in('src/')
        );
}

return $config;