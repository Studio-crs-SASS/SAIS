<?php

declare(strict_types=1);

/**
 * SAIS - Estimate Summary Builder
 *
 * Builds estimate summaries without finalizing actual price.
 */

final class EstimateSummaryBuilder
{
    /** @var array<string, mixed> */
    private array $estimateConfig;

    /**
     * @param array<string, mixed> $estimateConfig
     */
    public function __construct(array $estimateConfig)
    {
        $this->estimateConfig = $estimateConfig;
    }

    /**
     * Build estimate summary.
     *
     * @param array<int, array<string, mixed>> $estimateItems
     * @param array<int, string> $requiredCheck
     * @return array<string, mixed>
     */
    public function build(array $estimateItems, array $requiredCheck = []): array
    {
        return [
            'estimate_summary' => $this->buildEstimateSummary($estimateItems),
            'difficulty_summary' => $this->buildDifficultySummary($estimateItems),
            'impact_summary' => $this->buildImpactSummary($estimateItems),
            'required_check_summary' => $this->buildRequiredCheckSummary($requiredCheck),
            'pricing_note' => $this->buildPricingNote(),
            'summary_data' => [
                'total_items' => count($estimateItems),
                'required_check_count' => count($requiredCheck),
                'needs_confirmation_count' => $this->countNeedsConfirmation($estimateItems),
                'main_difficulty' => $this->detectMainValue($estimateItems, 'difficulty'),
                'main_impact' => $this->detectMainValue($estimateItems, 'impact'),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $estimateItems
     */
    private function buildEstimateSummary(array $estimateItems): string
    {
        $default = $this->estimateConfig['default_messages']['estimate_summary']
            ?? '今回の見積項目は、SADS診断で確認された優先改善項目をもとに整理しています。';

        if ($estimateItems === []) {
            return $default;
        }

        return $default . ' 見積項目は' . count($estimateItems) . '件です。';
    }

    /**
     * @param array<int, array<string, mixed>> $estimateItems
     */
    private function buildDifficultySummary(array $estimateItems): string
    {
        $mainDifficulty = $this->detectMainValue($estimateItems, 'difficulty');
        $messages = $this->estimateConfig['difficulty_summary'] ?? [];

        if (is_array($messages) && isset($messages[$mainDifficulty]) && is_string($messages[$mainDifficulty])) {
            return $messages[$mainDifficulty];
        }

        return '対象範囲の確認後に見積を整理します。';
    }

    /**
     * @param array<int, array<string, mixed>> $estimateItems
     */
    private function buildImpactSummary(array $estimateItems): string
    {
        $mainImpact = $this->detectMainValue($estimateItems, 'impact');
        $messages = $this->estimateConfig['impact_summary'] ?? [];

        if (is_array($messages) && isset($messages[$mainImpact]) && is_string($messages[$mainImpact])) {
            return $messages[$mainImpact];
        }

        return '改善項目を組み合わせて、サイト全体の分かりやすさを高めます。';
    }

    /**
     * @param array<int, string> $requiredCheck
     */
    private function buildRequiredCheckSummary(array $requiredCheck): string
    {
        if ($requiredCheck === []) {
            return '正式見積前の追加確認項目はありません。';
        }

        return '正式見積前に、' . implode('、', array_slice($requiredCheck, 0, 5)) . 'などを確認します。';
    }

    private function buildPricingNote(): string
    {
        return $this->estimateConfig['default_messages']['pricing_note']
            ?? '正式な金額は、対象ページ数、作業範囲、素材有無、導入条件を確認したうえで確定します。';
    }

    /**
     * @param array<int, array<string, mixed>> $estimateItems
     */
    private function countNeedsConfirmation(array $estimateItems): int
    {
        $count = 0;

        foreach ($estimateItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $pricingStatus = $item['pricing_status'] ?? [];

            if (!is_array($pricingStatus)) {
                continue;
            }

            if (($pricingStatus['needs_human_confirmation'] ?? false) === true) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function detectMainValue(array $items, string $field): string
    {
        $counts = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $value = $item[$field] ?? 'medium';

            if (!is_scalar($value)) {
                $value = 'medium';
            }

            $value = strtolower(trim((string) $value));

            if ($value === '') {
                $value = 'medium';
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        if ($counts === []) {
            return 'medium';
        }

        arsort($counts);

        $first = array_key_first($counts);

        return is_string($first) ? $first : 'medium';
    }
}