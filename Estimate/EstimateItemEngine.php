<?php

declare(strict_types=1);

/**
 * SAIS - Estimate Item Engine
 *
 * Builds estimate items from proposal inputs and priority items.
 */

require_once __DIR__ . '/PricingStatusResolver.php';
require_once __DIR__ . '/RequiredCheckBuilder.php';
require_once __DIR__ . '/EstimateSummaryBuilder.php';

final class EstimateItemEngine
{
    /** @var array<string, mixed> */
    private array $estimateConfig;

    private PricingStatusResolver $pricingStatusResolver;
    private RequiredCheckBuilder $requiredCheckBuilder;
    private EstimateSummaryBuilder $summaryBuilder;

    /**
     * @param array<string, mixed> $estimateConfig
     */
    public function __construct(array $estimateConfig)
    {
        $this->estimateConfig = $estimateConfig;
        $this->pricingStatusResolver = new PricingStatusResolver($estimateConfig);
        $this->requiredCheckBuilder = new RequiredCheckBuilder($estimateConfig);
        $this->summaryBuilder = new EstimateSummaryBuilder($estimateConfig);
    }

    /**
     * Build estimate.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, string> $recommendedScope
     * @param array<string, mixed> $additionalCheckResult
     * @return array<string, mixed>
     */
    public function build(
        array $proposalInputs,
        array $priorityItems = [],
        array $recommendedScope = [],
        array $additionalCheckResult = []
    ): array {
        $additionalChecks = $this->extractAdditionalChecks($additionalCheckResult);
        $estimateItems = $this->buildEstimateItems($proposalInputs, $priorityItems, $recommendedScope, $additionalChecks);
        $requiredCheck = $this->requiredCheckBuilder->build($estimateItems, $additionalChecks);
        $summary = $this->summaryBuilder->build($estimateItems, $requiredCheck);

        return [
            'estimate_summary' => $summary['estimate_summary'],
            'estimate_items' => $estimateItems,
            'scope' => $this->buildScope($recommendedScope),
            'priority' => $this->buildPriority($priorityItems),
            'difficulty_summary' => $summary['difficulty_summary'],
            'impact_summary' => $summary['impact_summary'],
            'required_check' => $requiredCheck,
            'pricing_note' => $summary['pricing_note'],
            'summary_data' => $summary['summary_data'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $proposalInputs
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, string> $recommendedScope
     * @param array<int, string> $additionalChecks
     * @return array<int, array<string, mixed>>
     */
    private function buildEstimateItems(
        array $proposalInputs,
        array $priorityItems,
        array $recommendedScope,
        array $additionalChecks
    ): array {
        $items = [];

        foreach ($proposalInputs as $index => $input) {
            if (!is_array($input)) {
                continue;
            }

            $priorityItem = $priorityItems[$index] ?? [];
            $category = (string) ($input['category'] ?? $priorityItem['category'] ?? 'unknown');
            $title = (string) ($input['title'] ?? $priorityItem['indicator_name'] ?? '改善項目');
            $scope = $this->detectScope($input, $recommendedScope, $category);
            $requiredCheckForItem = $this->requiredCheckBuilder->buildForItem($input, $additionalChecks);

            $baseItem = [
                'item_id' => 'estimate_item_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'source_indicator_id' => (string) ($input['indicator_id'] ?? $priorityItem['indicator_id'] ?? ''),
                'category' => $category,
                'title' => $title,
                'work_item' => $this->buildWorkItem($input, $title),
                'scope' => $scope,
                'priority_rank' => $this->normalizeRank($input['priority_rank'] ?? $priorityItem['rank'] ?? ($index + 1)),
                'impact' => (string) ($input['impact'] ?? 'medium'),
                'difficulty' => (string) ($input['difficulty'] ?? 'medium'),
                'expected_effect' => (string) ($input['expected_effect'] ?? ''),
                'required_check' => $requiredCheckForItem,
            ];

            $baseItem['pricing_status'] = $this->pricingStatusResolver->resolve($baseItem, $requiredCheckForItem);

            $items[] = $baseItem;
        }

        usort(
            $items,
            static fn (array $a, array $b): int => ((int) ($a['priority_rank'] ?? 999)) <=> ((int) ($b['priority_rank'] ?? 999))
        );

        return $items;
    }

    /**
     * @param array<string, mixed> $input
     * @param array<int, string> $recommendedScope
     */
    private function detectScope(array $input, array $recommendedScope, string $category): string
    {
        $inputScope = $input['recommended_service_scope'] ?? '';

        if (is_string($inputScope) && trim($inputScope) !== '') {
            return trim($inputScope);
        }

        foreach ($recommendedScope as $scope) {
            if (!is_string($scope)) {
                continue;
            }

            if ($category === 'depth' && str_contains($scope, '情報構造')) {
                return $scope;
            }

            if ($category === 'volume' && str_contains($scope, 'コンテンツ')) {
                return $scope;
            }

            if ($category === 'relationship' && (str_contains($scope, '内部リンク') || str_contains($scope, '情報接続'))) {
                return $scope;
            }

            if ($category === 'flow' && str_contains($scope, '導線')) {
                return $scope;
            }
        }

        return $recommendedScope[0] ?? '導入範囲確認';
    }

    /**
     * @param array<string, mixed> $input
     */
    private function buildWorkItem(array $input, string $title): string
    {
        $action = $input['action'] ?? '';

        if (is_string($action) && trim($action) !== '') {
            return trim($action);
        }

        return $title . 'に関する改善作業';
    }

    /**
     * @param array<int, string> $recommendedScope
     * @return array<int, array<string, mixed>>
     */
    private function buildScope(array $recommendedScope): array
    {
        $items = [];

        foreach (array_values(array_unique($recommendedScope)) as $index => $scope) {
            if (!is_string($scope) || trim($scope) === '') {
                continue;
            }

            $items[] = [
                'scope_id' => 'estimate_scope_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'scope_name' => trim($scope),
                'pricing_status' => 'scope_needs_confirmation',
            ];
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     * @return array<int, array<string, mixed>>
     */
    private function buildPriority(array $priorityItems): array
    {
        $items = [];

        foreach ($priorityItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'rank' => $this->normalizeRank($item['rank'] ?? 999),
                'indicator_id' => (string) ($item['indicator_id'] ?? ''),
                'indicator_name' => (string) ($item['indicator_name'] ?? ''),
                'category' => (string) ($item['category'] ?? 'unknown'),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $additionalCheckResult
     * @return array<int, string>
     */
    private function extractAdditionalChecks(array $additionalCheckResult): array
    {
        $checks = $additionalCheckResult['estimate_required_check'] ?? [];

        if (!is_array($checks)) {
            return [];
        }

        return array_values(array_filter(
            $checks,
            static fn (mixed $check): bool => is_string($check) && trim($check) !== ''
        ));
    }

    private function normalizeRank(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return 999;
    }
}