<?php

declare(strict_types=1);

/**
 * SAIS - Pricing Status Resolver
 *
 * Resolves pricing status without finalizing actual price.
 */

final class PricingStatusResolver
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
     * Resolve pricing status.
     *
     * @param array<string, mixed> $item
     * @param array<int, string> $requiredCheck
     * @return array<string, mixed>
     */
    public function resolve(array $item, array $requiredCheck = []): array
    {
        $difficulty = (string) ($item['difficulty'] ?? 'medium');
        $scope = (string) ($item['recommended_service_scope'] ?? $item['scope'] ?? '');
        $title = (string) ($item['title'] ?? '');

        $statusKey = $this->detectStatusKey($difficulty, $scope, $title, $requiredCheck);

        return [
            'key' => $statusKey,
            'label' => $this->getStatusLabel($statusKey),
            'needs_human_confirmation' => $this->needsHumanConfirmation($statusKey),
        ];
    }

    /**
     * Resolve multiple items.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<int, string> $requiredCheck
     * @return array<int, array<string, mixed>>
     */
    public function resolveMany(array $items, array $requiredCheck = []): array
    {
        $results = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $results[] = [
                'index' => $index,
                'source_indicator_id' => (string) ($item['indicator_id'] ?? $item['source_indicator_id'] ?? ''),
                'pricing_status' => $this->resolve($item, $requiredCheck),
            ];
        }

        return $results;
    }

    /**
     * Build pricing note.
     */
    public function buildPricingNote(): string
    {
        return $this->estimateConfig['default_messages']['pricing_note']
            ?? '正式な金額は、対象ページ数、作業範囲、素材有無、導入条件を確認したうえで確定します。';
    }

    /**
     * @param array<int, string> $requiredCheck
     */
    private function detectStatusKey(string $difficulty, string $scope, string $title, array $requiredCheck): string
    {
        if (trim($title) === '') {
            return 'not_priced';
        }

        if ($difficulty === 'high') {
            return 'needs_manual_pricing';
        }

        if ($requiredCheck !== []) {
            return 'scope_needs_confirmation';
        }

        if ($scope === '') {
            return 'scope_needs_confirmation';
        }

        return 'ready_for_pricing';
    }

    private function getStatusLabel(string $statusKey): string
    {
        $statusLabels = $this->estimateConfig['pricing_status'] ?? [];

        if (is_array($statusLabels) && isset($statusLabels[$statusKey]) && is_string($statusLabels[$statusKey])) {
            return $statusLabels[$statusKey];
        }

        return match ($statusKey) {
            'needs_manual_pricing' => '人間による金額確認が必要',
            'scope_needs_confirmation' => '範囲確認後に金額設定',
            'ready_for_pricing' => '見積項目として整理済み',
            'not_priced' => '金額対象外または説明項目',
            default => '範囲確認後に金額設定',
        };
    }

    private function needsHumanConfirmation(string $statusKey): bool
    {
        return in_array(
            $statusKey,
            [
                'needs_manual_pricing',
                'scope_needs_confirmation',
            ],
            true
        );
    }
}