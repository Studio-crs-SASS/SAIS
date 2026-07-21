<?php

declare(strict_types=1);

/**
 * SAIS - Input Config
 *
 * Defines required input structure from SADS SAISBridge Output.
 */

return [
    'source' => [
        'system' => 'SADS',
        'target_system' => 'SAIS',
        'project' => 'SEEN',
        'input_name' => 'SADS SAISBridge Output',
    ],

    'required_root_fields' => [
        'system',
        'target_system',
        'project',
        'version',
        'target',
        'proposal_inputs',
        'priority_items',
        'client_summary',
        'confidence',
        'recommended_scope',
        'additional_check_items',
        'metadata',
    ],

    'field_types' => [
        'system' => 'string',
        'target_system' => 'string',
        'project' => 'string',
        'version' => 'string',
        'target' => 'object',
        'proposal_inputs' => 'array',
        'priority_items' => 'array',
        'client_summary' => 'string',
        'confidence' => 'object',
        'recommended_scope' => 'array',
        'additional_check_items' => 'array',
        'metadata' => 'object',
    ],

    'required_values' => [
        'system' => 'SADS',
        'target_system' => 'SAIS',
        'project' => 'SEEN',
    ],

    'optional_target_fields' => [
        'url',
        'domain',
        'checked_at',
    ],

    'proposal_input_fields' => [
        'indicator_id',
        'category',
        'title',
        'summary',
        'action',
        'expected_effect',
        'impact',
        'difficulty',
        'priority_rank',
        'recommended_service_scope',
    ],

    'priority_item_fields' => [
        'rank',
        'indicator_id',
        'indicator_name',
        'category',
        'internal_score',
        'display_score',
        'recommendation',
        'reason',
        'improvement_comment',
    ],

    'confidence_fields' => [
        'internal',
        'display',
        'level',
        'missing_data_count',
        'estimated_indicator_count',
    ],
];