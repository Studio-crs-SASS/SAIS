<?php

declare(strict_types=1);

/**
 * SAIS - Proposal Config
 *
 * Defines proposal generation rules.
 */

return [
    'proposal_fields' => [
        'title',
        'summary',
        'diagnosis_connection',
        'priority_reason',
        'recommended_actions',
        'expected_effects',
        'scope_summary',
        'confidence_note',
        'additional_check_note',
        'suggested_next_step',
        'client_message',
    ],

    'category_policy' => [
        'depth' => [
            'label' => 'Depth',
            'jp_label' => '情報構造',
            'proposal_direction' => [
                '情報が整理される',
                'サイト全体の把握がしやすくなる',
                'AIと訪問者の双方が内容を理解しやすくなる',
                'SASS導入前の土台が整う',
            ],
            'title' => '情報構造改善を中心とした導入提案',
        ],

        'volume' => [
            'label' => 'Volume',
            'jp_label' => '情報量',
            'proposal_direction' => [
                'サービス理解が深まる',
                '比較検討がしやすくなる',
                '顧客が判断しやすくなる',
                '提案書の説得材料が増える',
            ],
            'title' => 'コンテンツ強化を中心とした導入提案',
        ],

        'relationship' => [
            'label' => 'Relationship',
            'jp_label' => '情報接続',
            'proposal_direction' => [
                'ページ同士のつながりが強まる',
                'サイト全体の理解が深まる',
                '回遊しやすくなる',
                'SASS運用時の情報接続がしやすくなる',
            ],
            'title' => '情報接続改善を中心とした導入提案',
        ],

        'flow' => [
            'label' => 'Flow',
            'jp_label' => '行動導線',
            'proposal_direction' => [
                '訪問者が次の行動へ進みやすくなる',
                '問い合わせや予約につながりやすくなる',
                '離脱を抑えやすくなる',
                'SASS導入後の成果確認がしやすくなる',
            ],
            'title' => '行動導線改善を中心とした導入提案',
        ],
    ],

    'impact_policy' => [
        'high' => '提案の中心項目',
        'medium' => '提案の補助項目',
        'low' => '軽微な改善項目',
    ],

    'difficulty_policy' => [
        'low' => '軽微な改善',
        'medium' => '通常改善',
        'high' => '構造改善または導入前確認が必要な改善',
    ],

    'default_messages' => [
        'diagnosis_connection' => 'SADS診断で確認された改善優先項目をもとに、導入提案を整理します。',
        'additional_check_none' => '現時点で大きな追加確認項目はありません。',
        'additional_check_exists' => '提案前に確認すべき項目があります。',
        'suggested_next_step' => '改善対象と導入範囲を確認し、見積内容の確認へ進みます。',
        'client_message' => '診断結果をもとに、優先的に改善すべき項目を整理しました。まずは改善対象と導入範囲を確認する提案です。',
    ],

    'confidence_note' => [
        'high' => '診断結果は安定しており、提案作成に利用しやすい状態です。',
        'medium' => '一部確認を行うことで、より安全に提案へ進めます。',
        'low' => '提案前に追加確認を行う必要があります。',
    ],
];