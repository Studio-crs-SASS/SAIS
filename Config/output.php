<?php

declare(strict_types=1);

/**
 * SAIS - Output Config
 *
 * Defines SAIS output JSON structure.
 */

return [
    'status' => [
        'success' => 'success',
        'partial' => 'partial',
        'failed' => 'failed',
    ],

    'root_fields' => [
        'status',
        'system',
        'version',
        'project',
        'target',
        'source',
        'proposal',
        'estimate',
        'introduction_plan',
        'sass_scope_candidate',
        'additional_check_items',
        'proposal_data',
        'sass_bridge',
        'warnings',
        'metadata',
    ],

    'failed_root_fields' => [
        'status',
        'system',
        'version',
        'project',
        'target',
        'source',
        'errors',
        'warnings',
        'metadata',
    ],

    'proposal_data_fields' => [
        'cover',
        'executive_summary',
        'diagnosis_summary',
        'proposal_overview',
        'priority_improvement_items',
        'recommended_actions',
        'estimate_section',
        'introduction_plan_section',
        'sass_scope_section',
        'additional_check_section',
        'next_action',
    ],

    'sass_bridge_fields' => [
        'system',
        'target_system',
        'project',
        'target',
        'sass_scope_candidate',
        'priority_tasks',
        'recommended_modules',
        'operation_candidates',
        'implementation_notes',
        'additional_checks',
        'metadata',
    ],

    'metadata_fields' => [
        'generated_at',
        'engine',
        'engine_version',
        'source_engine',
        'target_engine',
        'document_set',
    ],

    'defaults' => [
        'system' => 'SAIS',
        'version' => '1.0',
        'project' => 'SEEN',
        'source_engine' => 'SADS',
        'target_engine' => 'SASS',
        'document_set' => 'SAIS Documents Ver.1.0',
    ],
];