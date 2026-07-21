<?php

declare(strict_types=1);

/**
 * SAIS - Expected Effect Builder
 *
 * Builds expected effects from proposal inputs.
 */

final class ExpectedEffectBuilder
{
    /**
     * Build expected effects.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @return array<int, array<string, mixed>>
     */
    public function build(array $proposalInputs): array
    {
        $effects = [];

        foreach ($proposalInputs as $index => $input) {
            if (!is_array($input)) {
                continue;
            }

            $effects[] = [
                'priority_rank' => $this->normalizeRank($input['priority_rank'] ?? ($index + 1)),
                'indicator_id' => (string) ($input['indicator_id'] ?? 'unknown_' . ($index + 1)),
                'category' => (string) ($input['category'] ?? 'unknown'),
                'title' => (string) ($input['title'] ?? '改善項目'),
                'expected_effect' => $this->buildEffectText($input),
                'impact' => (string) ($input['impact'] ?? 'medium'),
                'difficulty' => (string) ($input['difficulty'] ?? 'medium'),
            ];
        }

        usort(
            $effects,
            static fn (array $a, array $b): int => ((int) ($a['priority_rank'] ?? 999)) <=> ((int) ($b['priority_rank'] ?? 999))
        );

        return $effects;
    }

    /**
     * Build top expected effects.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @return array<int, array<string, mixed>>
     */
    public function buildTop(array $proposalInputs, int $limit = 5): array
    {
        return array_slice($this->build($proposalInputs), 0, max(1, $limit));
    }

    /**
     * Build expected effect summary.
     *
     * @param array<int, array<string, mixed>> $effects
     * @return array<string, mixed>
     */
    public function buildSummary(array $effects): array
    {
        $primary = $effects[0] ?? null;

        return [
            'total' => count($effects),
            'primary_indicator_id' => is_array($primary) ? (string) ($primary['indicator_id'] ?? '') : '',
            'primary_effect' => is_array($primary) ? (string) ($primary['expected_effect'] ?? '') : '',
            'high_impact_count' => count(array_filter(
                $effects,
                static fn (array $effect): bool => ($effect['impact'] ?? '') === 'high'
            )),
        ];
    }

    /**
     * Build combined message.
     *
     * @param array<int, array<string, mixed>> $effects
     */
    public function buildCombinedMessage(array $effects): string
    {
        $topEffects = array_slice($effects, 0, 3);
        $messages = [];

        foreach ($topEffects as $effect) {
            if (!is_array($effect)) {
                continue;
            }

            $text = (string) ($effect['expected_effect'] ?? '');

            if ($text !== '') {
                $messages[] = $text;
            }
        }

        if ($messages === []) {
            return '改善対応により、サイト全体の分かりやすさと導線の改善が期待できます。';
        }

        return implode(' ', $messages);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function buildEffectText(array $input): string
    {
        $effect = $input['expected_effect'] ?? '';

        if (is_string($effect) && trim($effect) !== '') {
            return trim($effect);
        }

        $category = (string) ($input['category'] ?? 'unknown');

        return match ($category) {
            'depth' => '情報構造が整理され、訪問者とAIの双方が内容を把握しやすくなります。',
            'volume' => '情報量が増え、サービス内容や判断材料が伝わりやすくなります。',
            'relationship' => 'ページ同士の接続が整理され、サイト全体の理解が深まりやすくなります。',
            'flow' => '訪問者が次の行動へ進みやすくなり、問い合わせや予約につながりやすくなります。',
            default => 'サイト全体の分かりやすさと導入準備が整いやすくなります。',
        };
    }

    private function normalizeRank(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return 999;
    }
}