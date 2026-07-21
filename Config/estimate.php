<?php

declare(strict_types=1);

/**
 * SAIS - Estimate Config
 *
 * Defines estimate item generation rules.
 */

return [
    'estimate_fields' => [
        'estimate_summary',
        'estimate_items',
        'scope',
        'priority',
        'difficulty_summary',
        'impact_summary',
        'required_check',
        'pricing_note',
    ],

    'estimate_item_fields' => [
        'item_id',
        'source_indicator_id',
        'category',
        'title',
        'work_item',
        'scope',
        'priority_rank',
        'impact',
        'difficulty',
        'expected_effect',
        'required_check',
        'pricing_status',
    ],

    'pricing_status' => [
        'needs_manual_pricing' => '人間による金額確認が必要',
        'scope_needs_confirmation' => '範囲確認後に金額設定',
        'ready_for_pricing' => '見積項目として整理済み',
        'not_priced' => '金額対象外または説明項目',
    ],

    'scope_categories' => [
        'sass_pre_improvement' => 'SASS導入前改善',
        'sass_structure_improvement' => 'SASS構造改善',
        'sass_operation_improvement' => 'SASS運用改善',
        'information_structure_improvement' => '情報構造改善',
        'content_improvement' => 'コンテンツ改善',
        'internal_link_improvement' => '内部リンク改善',
        'flow_improvement' => '導線改善',
        'display_speed_improvement' => '表示速度改善',
    ],

    'required_check_by_category' => [
        'depth' => [
            '対象ページ数',
            'ページ構成',
            '見出し数',
            '既存テンプレート',
            '修正範囲',
        ],

        'volume' => [
            '追加文章量',
            '原稿作成有無',
            '画像点数',
            '動画有無',
            '事例数',
            '更新頻度',
        ],

        'relationship' => [
            '対象ページ数',
            '内部リンク本数',
            '関連ページ数',
            '重複箇所',
            '外部リンク有無',
            'SNS接続有無',
        ],

        'flow' => [
            'CTA数',
            'フォーム有無',
            '予約導線有無',
            '購入導線有無',
            '画像容量',
            'サーバー環境',
            '表示速度改善範囲',
        ],
    ],

    'difficulty_summary' => [
        'low' => '軽微な改善項目が中心のため、比較的進めやすい構成です。',
        'medium' => '通常改善項目が中心で、対象範囲の確認後に見積を確定しやすい構成です。',
        'high' => '構造改善または追加確認が必要な項目を含むため、見積前に範囲確認が必要です。',
    ],

    'impact_summary' => [
        'high' => '成果への影響が大きい改善項目を含むため、優先的な対応を推奨します。',
        'medium' => '複数の改善を組み合わせることで、サイト全体の分かりやすさを高める構成です。',
        'low' => '軽微な整備を中心に、既存サイトの安定性を高める構成です。',
    ],

    'default_messages' => [
        'estimate_summary' => '今回の見積項目は、SADS診断で確認された優先改善項目をもとに整理しています。',
        'pricing_note' => '本見積項目は、SADS診断結果をもとにした見積素材です。正式な金額は、対象ページ数、作業範囲、素材有無、導入条件を確認したうえで確定します。',
    ],
];