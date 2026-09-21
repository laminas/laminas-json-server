<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;
use Boundwize\StructArmed\Rule\Rules\Class_\MustBeFinalRule;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layerPattern('tests', '/^LaminasTest\\\\Json\\\\Server\\\\.*Test$/')
    ->rule(
        'tests_classes.must_be_final',
        new MustBeFinalRule(layer: 'tests')
    )
    ->layer('Exception', 'src/Exception')
    ->layer('Error', 'src/Error.php')
    ->layer('Request', 'src/Request.php')
    ->layer('RequestHttp', 'src/Request')
    ->layer('Response', 'src/Response.php')
    ->layer('ResponseHttp', 'src/Response')
    ->layer('Smd', ['src/Smd.php', 'src/Smd'])
    ->layer('Client', 'src/Client.php')
    ->layer('Server', 'src/Server.php')
    ->layer('Cache', 'src/Cache.php')
    ->ruleset([
        'Exception'    => [],
        'Error'        => [],
        'Request'      => [],
        'Response'     => ['Exception', 'Error'],
        'RequestHttp'  => ['+Request'],
        'ResponseHttp' => ['+Response'],
        'Smd'          => ['Exception'],
        'Client'       => ['Request', '+Response'],
        'Server'       => ['+RequestHttp', '+ResponseHttp', '+Smd'],
        'Cache'        => ['+Server'],
    ]);
