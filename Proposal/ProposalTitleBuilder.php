<?php

declare(strict_types=1);

/**
 * SAIS - Proposal Title Builder
 *
 * Builds proposal title from priority items and proposal inputs.
 */

final class ProposalTitleBuilder
{
    /** @var array<string, mixed> */
    private array $proposalConfig;

    /**
     * @param array<string, mixed> $proposalConfig
     */
    public function __construct(array $proposalConfig)
    {
        $this->proposalConfig = $proposalConfig;
    }

    /**
     * Build proposal title.
     *
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, array<string, mixed>> $proposalInputs
     */
    public function build(array $priorityItems = [], array $proposalInputs = []): string
    {
        $category = $this->detectPrimaryCategory($priorityItems, $proposalInputs);

        $categoryPolicy = $this->proposalConfig['category_policy'] ?? [];

        if (is_array($categoryPolicy) && isset($categoryPolicy[$category]) && is_array($categoryPolicy[$category])) {
            $title = $categoryPolicy[$category]['title'] ?? null;

            if (is_string($title) && $title !== '') {
                return $title;
            }
        }

        return '診断結果をもとにした導入提案';
    }

    /**
     * Build combined title when multiple important categories exist.
     *
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, array<string, mixed>> $proposalInputs
     */
    public function buildWithCombination(array $priorityItems = [], array $proposalInputs = []): string
    {
        $categories = $this->detectTopCategories($priorityItems, $proposalInputs);

        if (count($categories) >= 2) {
            $first = $this->categoryToJapaneseLabel($categories[0]);
            $second = $this->categoryToJapaneseLabel($categories[1]);

            return $first . 'と' . $second . 'を組み合わせた導入提案';
        }

        return $this->build($priorityItems, $proposalInputs);
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, array<string, mixed>> $proposalInputs
     */
    private function detectPrimaryCategory(array $priorityItems, array $proposalInputs): string
    {
        if (isset($priorityItems[0]['category']) && is_scalar($priorityItems[0]['category'])) {
            return $this->normalizeCategory((string) $priorityItems[0]['category']);
        }

        if (isset($proposalInputs[0]['category']) && is_scalar($proposalInputs[0]['category'])) {
            return $this->normalizeCategory((string) $proposalInputs[0]['category']);
        }

        return 'unknown';
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, array<string, mixed>> $proposalInputs
     * @return array<int, string>
     */
    private function detectTopCategories(array $priorityItems, array $proposalInputs): array
    {
        $categories = [];

        foreach ($priorityItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $category = $this->normalizeCategory((string) ($item['category'] ?? 'unknown'));

            if ($category !== 'unknown') {
                $categories[] = $category;
            }

            if (count(array_unique($categories)) >= 2) {
                break;
            }
        }

        if (count(array_unique($categories)) < 2) {
            foreach ($proposalInputs as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $category = $this->normalizeCategory((string) ($item['category'] ?? 'unknown'));

                if ($category !== 'unknown') {
                    $categories[] = $category;
                }

                if (count(array_unique($categories)) >= 2) {
                    break;
                }
            }
        }

        return array_values(array_unique($categories));
    }

    private function normalizeCategory(string $category): string
    {
        $value = strtolower(trim($category));

        return match ($value) {
            'depth' => 'depth',
            'volume' => 'volume',
            'relationship' => 'relationship',
            'flow' => 'flow',
            default => 'unknown',
        };
    }

    private function categoryToJapaneseLabel(string $category): string
    {
        $categoryPolicy = $this->proposalConfig['category_policy'] ?? [];

        if (is_array($categoryPolicy) && isset($categoryPolicy[$category]) && is_array($categoryPolicy[$category])) {
            $label = $categoryPolicy[$category]['jp_label'] ?? null;

            if (is_string($label) && $label !== '') {
                return $label;
            }
        }

        return match ($category) {
            'depth' => '情報構造',
            'volume' => 'コンテンツ',
            'relationship' => '情報接続',
            'flow' => '行動導線',
            default => '改善項目',
        };
    }
}