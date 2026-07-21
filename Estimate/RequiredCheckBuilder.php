<?php

declare(strict_types=1);

/**
 * SAIS - Required Check Builder
 *
 * Builds required check items for estimate confirmation.
 */

final class RequiredCheckBuilder
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
     * Build required check items from category and additional checks.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<int, string> $additionalChecks
     * @return array<int, string>
     */
    public function build(array $items, array $additionalChecks = []): array
    {
        $checks = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $category = (string) ($item['category'] ?? 'unknown');
            $checks = array_merge($checks, $this->getChecksByCategory($category));
        }

        foreach ($additionalChecks as $check) {
            if (is_string($check) && trim($check) !== '') {
                $checks[] = trim($check);
            }
        }

        return array_values(array_unique($checks));
    }

    /**
     * Build required check items for a single estimate item.
     *
     * @param array<string, mixed> $item
     * @param array<int, string> $additionalChecks
     * @return array<int, string>
     */
    public function buildForItem(array $item, array $additionalChecks = []): array
    {
        $category = (string) ($item['category'] ?? 'unknown');
        $checks = $this->getChecksByCategory($category);

        foreach ($additionalChecks as $check) {
            if (is_string($check) && trim($check) !== '') {
                $checks[] = trim($check);
            }
        }

        return array_values(array_unique($checks));
    }

    /**
     * Build required check summary.
     *
     * @param array<int, string> $checks
     * @return array<string, mixed>
     */
    public function buildSummary(array $checks): array
    {
        return [
            'total' => count($checks),
            'has_required_check' => count($checks) > 0,
            'items' => array_values($checks),
            'message' => $this->buildMessage($checks),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getChecksByCategory(string $category): array
    {
        $map = $this->estimateConfig['required_check_by_category'] ?? [];

        if (is_array($map) && isset($map[$category]) && is_array($map[$category])) {
            return array_values(array_filter(
                $map[$category],
                static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
            ));
        }

        return [
            '対象範囲',
            '作業内容',
            '素材有無',
        ];
    }

    /**
     * @param array<int, string> $checks
     */
    private function buildMessage(array $checks): string
    {
        if ($checks === []) {
            return '見積前の追加確認項目はありません。';
        }

        return '正式見積前に、' . implode('、', array_slice($checks, 0, 5)) . 'などを確認します。';
    }
}