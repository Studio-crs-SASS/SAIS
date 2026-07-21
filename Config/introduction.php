<?php

declare(strict_types=1);

/**
 * SAIS - Introduction Config
 *
 * Defines introduction plan generation rules.
 */

return [
    'introduction_plan_fields' => [
        'plan_summary',
        'phases',
        'tasks',
        'priority',
        'expected_effects',
        'required_check',
        'sass_connection',
    ],

    'phase_fields' => [
        'phase_id',
        'phase_name',
        'scope',
        'tasks',
        'priority',
        'expected_effect',
        'required_check',
        'sass_connection',
    ],

    'phase_templates' => [
        'sass_pre_improvement' => [
            'phase_name' => '導入前改善',
            'scope' => 'SASS導入前改善',
            'sass_connection' => 'SASS導入前改善',
        ],

        'structure_improvement' => [
            'phase_name' => '情報構造改善',
            'scope' => '情報構造改善',
            'sass_connection' => 'SASS構造改善',
        ],

        'content_improvement' => [
            'phase_name' => 'コンテンツ改善',
            'scope' => 'コンテンツ改善',
            'sass_connection' => 'SASSコンテンツ改善',
        ],

        'flow_improvement' => [
            'phase_name' => '導線改善',
            'scope' => '導線改善',
            'sass_connection' => 'SASS導線改善',
        ],

        'operation_improvement' => [
            'phase_name' => '運用改善',
            'scope' => 'SASS運用改善',
            'sass_connection' => 'SASS運用改善',
        ],
    ],

    'priority_labels' => [
        'high' => '優先対応',
        'medium' => '通常対応',
        'low' => '補助対応',
    ],

    'default_tasks_by_category' => [
        'depth' => [
            '見出し構造の確認',
            'ページ構成の整理',
            '情報配置の見直し',
        ],

        'volume' => [
            '本文量の確認',
            'サービス説明の追加',
            '事例・FAQの追加検討',
        ],

        'relationship' => [
            '内部リンクの確認',
            '関連ページ接続の整理',
            '文脈接続の見直し',
        ],

        'flow' => [
            'CTA配置の確認',
            '問い合わせ導線の確認',
            '表示速度の改善範囲確認',
        ],
    ],

    'default_messages' => [
        'plan_summary' => 'SADS診断結果をもとに、優先改善項目から段階的に導入計画を整理します。',
        'required_check_note' => '導入前に対象範囲と必要素材を確認します。',
    ],
];