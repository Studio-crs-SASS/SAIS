<?php

declare(strict_types=1);

/**
 * SAIS - Warnings and Errors Config
 *
 * Defines SAIS warning and error types.
 */

return [
    'errors' => [
        'INVALID_JSON' => [
            'code' => 'INVALID_JSON',
            'message' => 'Input JSON is invalid.',
            'impact' => 'processing_stopped',
        ],

        'INVALID_SOURCE_SYSTEM' => [
            'code' => 'INVALID_SOURCE_SYSTEM',
            'message' => 'Input system must be SADS.',
            'impact' => 'processing_stopped',
        ],

        'INVALID_TARGET_SYSTEM' => [
            'code' => 'INVALID_TARGET_SYSTEM',
            'message' => 'Target system must be SAIS.',
            'impact' => 'processing_stopped',
        ],

        'INVALID_PROJECT' => [
            'code' => 'INVALID_PROJECT',
            'message' => 'Project must be SEEN.',
            'impact' => 'processing_stopped',
        ],

        'MISSING_PROPOSAL_INPUTS' => [
            'code' => 'MISSING_PROPOSAL_INPUTS',
            'message' => 'proposal_inputs is required.',
            'impact' => 'processing_stopped',
        ],

        'MISSING_PRIORITY_ITEMS' => [
            'code' => 'MISSING_PRIORITY_ITEMS',
            'message' => 'priority_items is required.',
            'impact' => 'processing_stopped',
        ],

        'MISSING_CONFIDENCE' => [
            'code' => 'MISSING_CONFIDENCE',
            'message' => 'confidence is required.',
            'impact' => 'processing_stopped',
        ],

        'INVALID_INPUT_STRUCTURE' => [
            'code' => 'INVALID_INPUT_STRUCTURE',
            'message' => 'Input structure is invalid.',
            'impact' => 'processing_stopped',
        ],

        'PROPOSAL_BUILD_FAILED' => [
            'code' => 'PROPOSAL_BUILD_FAILED',
            'message' => 'Proposal generation failed.',
            'impact' => 'processing_stopped',
        ],

        'ESTIMATE_BUILD_FAILED' => [
            'code' => 'ESTIMATE_BUILD_FAILED',
            'message' => 'Estimate generation failed.',
            'impact' => 'processing_stopped',
        ],

        'INTRODUCTION_PLAN_BUILD_FAILED' => [
            'code' => 'INTRODUCTION_PLAN_BUILD_FAILED',
            'message' => 'Introduction plan generation failed.',
            'impact' => 'processing_stopped',
        ],

        'SASS_SCOPE_BUILD_FAILED' => [
            'code' => 'SASS_SCOPE_BUILD_FAILED',
            'message' => 'SASS scope candidate generation failed.',
            'impact' => 'processing_stopped',
        ],

        'OUTPUT_BUILD_FAILED' => [
            'code' => 'OUTPUT_BUILD_FAILED',
            'message' => 'Output JSON generation failed.',
            'impact' => 'processing_stopped',
        ],
    ],

    'warnings' => [
        'LOW_CONFIDENCE_INPUT' => [
            'code' => 'LOW_CONFIDENCE_INPUT',
            'message' => 'Input confidence is below recommended level.',
            'impact' => 'proposal_needs_confirmation',
        ],

        'MISSING_PROPOSAL_INPUT' => [
            'code' => 'MISSING_PROPOSAL_INPUT',
            'message' => 'Some proposal input fields are missing.',
            'impact' => 'proposal_partial',
        ],

        'MISSING_PRIORITY_ITEMS' => [
            'code' => 'MISSING_PRIORITY_ITEMS',
            'message' => 'priority_items is empty or incomplete.',
            'impact' => 'priority_needs_confirmation',
        ],

        'MISSING_RECOMMENDED_SCOPE' => [
            'code' => 'MISSING_RECOMMENDED_SCOPE',
            'message' => 'recommended_scope is empty or incomplete.',
            'impact' => 'scope_needs_confirmation',
        ],

        'ADDITIONAL_CHECK_REQUIRED' => [
            'code' => 'ADDITIONAL_CHECK_REQUIRED',
            'message' => 'Additional check items are required before proposal confirmation.',
            'impact' => 'additional_check_required',
        ],

        'ESTIMATE_NEEDS_CONFIRMATION' => [
            'code' => 'ESTIMATE_NEEDS_CONFIRMATION',
            'message' => 'Estimate items require human confirmation before pricing.',
            'impact' => 'estimate_needs_confirmation',
        ],

        'SASS_SCOPE_NEEDS_CONFIRMATION' => [
            'code' => 'SASS_SCOPE_NEEDS_CONFIRMATION',
            'message' => 'SASS scope candidate requires confirmation before implementation.',
            'impact' => 'sass_scope_needs_confirmation',
        ],
    ],

    'confidence_thresholds' => [
        'high' => 0.85,
        'medium' => 0.70,
        'low' => 0.00,
    ],
];