<?php

declare(strict_types=1);

/**
 * SAIS - Scope Config
 *
 * Defines SASS scope candidate rules.
 */

return [
    'sass_scope_candidate_fields' => [
        'summary',
        'candidates',
        'recommended_modules',
        'operation_candidates',
        'implementation_notes',
    ],

    'candidate_fields' => [
        'scope_name',
        'source_recommendation',
        'priority',
        'impact',
        'difficulty',
        'sass_connection_type',
        'note',
    ],

    'sass_connection_types' => [
        'pre_improvement' => 'SASS導入前改善',
        'structure_improvement' => 'SASS構造改善',
        'content_improvement' => 'SASSコンテンツ改善',
        'relationship_improvement' => 'SASS情報接続改善',
        'flow_improvement' => 'SASS導線改善',
        'operation_improvement' => 'SASS運用改善',
    ],

    'category_to_connection' => [
        'depth' => 'structure_improvement',
        'volume' => 'content_improvement',
        'relationship' => 'relationship_improvement',
        'flow' => 'flow_improvement',
    ],

    'recommended_modules_by_category' => [
        'depth' => [
            'Structure Module',
            'Page Architecture Module',
        ],

        'volume' => [
            'Content Module',
            'Knowledge Expansion Module',
        ],

        'relationship' => [
            'Internal Link Module',
            'Knowledge Connection Module',
        ],

        'flow' => [
            'Flow Module',
            'CTA Module',
            'Performance Support Module',
        ],
    ],

    'operation_candidates_by_category' => [
        'depth' => [
            'ページ構造の定期確認',
            '見出し構成の運用改善',
        ],

        'volume' => [
            'コンテンツ追加運用',
            'FAQ・事例更新運用',
        ],

        'relationship' => [
            '内部リンク定期改善',
            '関連ページ接続運用',
        ],

        'flow' => [
            'CTA導線改善運用',
            '表示速度確認運用',
        ],
    ],

    'default_messages' => [
        'summary' => 'SADS診断結果とSAIS提案内容をもとに、SASS導入候補範囲を整理します。',
        'implementation_note' => 'SASS導入前に対象範囲、優先順位、運用条件を確認します。',
    ],
];