<?php

declare(strict_types=1);

/**
 * SAIS - Paths Config
 *
 * Defines SAIS directory paths.
 */

$basePath = dirname(__DIR__);

return [
    'base_path' => $basePath,

    'directories' => [
        'bridge' => $basePath . '/Bridge',
        'check' => $basePath . '/Check',
        'classification' => $basePath . '/Classification',
        'config' => $basePath . '/Config',
        'data' => $basePath . '/Data',
        'docs' => $basePath . '/Docs',
        'estimate' => $basePath . '/Estimate',
        'input' => $basePath . '/Input',
        'introduction' => $basePath . '/Introduction',
        'output' => $basePath . '/Output',
        'priority' => $basePath . '/Priority',
        'proposal' => $basePath . '/Proposal',
        'public' => $basePath . '/Public',
        'scope' => $basePath . '/Scope',
        'storage' => $basePath . '/Storage',
        'tests' => $basePath . '/Tests',
    ],

    'data_paths' => [
        'input' => $basePath . '/Data/input',
        'output' => $basePath . '/Data/output',
        'samples' => $basePath . '/Data/samples',
    ],

    'storage_paths' => [
        'cache' => $basePath . '/Storage/cache',
        'logs' => $basePath . '/Storage/logs',
        'reports' => $basePath . '/Storage/reports',
    ],

    'test_paths' => [
        'integration' => $basePath . '/Tests/Integration',
        'samples' => $basePath . '/Tests/Samples',
        'unit' => $basePath . '/Tests/Unit',
    ],

    'files' => [
        'bootstrap' => $basePath . '/bootstrap.php',
        'readme' => $basePath . '/README.md',
        'api' => $basePath . '/Public/api.php',
        'sample_input' => $basePath . '/Data/samples/sads_sais_bridge_sample.json',
        'sample_output' => $basePath . '/Data/samples/sais_output_sample.json',
        'api_test_output' => $basePath . '/Data/output/sais_api_test_output.json',
        'integration_test' => $basePath . '/Tests/Integration/sais_api_pipeline_test.php',
    ],
];