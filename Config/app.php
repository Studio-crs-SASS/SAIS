<?php

declare(strict_types=1);

/**
 * SAIS - Application Config
 *
 * Project: SEEN
 * Role: Introduction Proposal Engine
 */

return [
    'system' => [
        'name' => 'SAIS',
        'official_name' => 'Syu AI Introduction System',
        'project' => 'SEEN',
        'phase' => 'Phase 4',
        'version' => '1.0',
        'type' => 'Satellite Engine',
        'role' => 'Introduction Proposal Engine',
    ],

    'connection' => [
        'input_system' => 'SADS',
        'input_name' => 'SADS SAISBridge Output',
        'target_system' => 'SASS',
        'target_name' => 'Syu AI Symbiosis System',
        'flow' => [
            'SWCS',
            'SADS',
            'SAIS',
            'SASS',
        ],
    ],

    'policy' => [
        'docs_first' => true,
        'does_not_recalculate_sads_score' => true,
        'does_not_execute_swcs' => true,
        'does_not_execute_sass' => true,
        'does_not_finalize_pricing' => true,
        'does_not_finalize_contract' => true,
    ],

    'output' => [
        'status_success' => 'success',
        'status_partial' => 'partial',
        'status_failed' => 'failed',
        'format' => 'json',
        'charset' => 'utf-8',
    ],
];