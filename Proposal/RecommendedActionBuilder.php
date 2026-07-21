<?php

declare(strict_types=1);

/**
 * SAIS - Recommended Action Builder
 *
 * Builds recommended actions from proposal inputs.
 */

final class RecommendedActionBuilder
{
    /**
     * Build recommended actions.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @return array<int, array<string, mixed>>
     */
    public function build(array $proposalInputs): array
    {
        $actions = [];

        foreach ($proposalInputs as $index => $input) {
            if (!is_array($input)) {
                continue;
            }

            $actions[] = [
                'priority_rank' => $this->normalizeRank($input['priority_rank'] ?? ($index + 1)),
                'indicator_id' => (string) ($input['indicator_id'] ?? 'unknown_' . ($index + 1)),
                'category' => (string) ($input['category'] ?? 'unknown'),
                'title' => (string) ($input['title'] ?? '改善項目'),
                'action' => $this->buildActionText($input),
                'impact' => (string) ($input['impact'] ?? 'medium'),
                'difficulty' => (string) ($input['difficulty'] ?? 'medium'),
                'recommended_service_scope' => (string) ($input['recommended_service_scope'] ?? ''),
            ];
        }

        usort(
            $actions,
            static fn (array $a, array $b): int => ((int) ($a['priority_rank'] ?? 999)) <=> ((int) ($b['priority_rank'] ?? 999))
        );

        return $actions;
    }

    /**
     * Build top actions.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @return array<int, array<string, mixed>>
     */
    public function buildTop(array $proposalInputs, int $limit = 5): array
    {
        return array_slice($this->build($proposalInputs), 0, max(1, $limit));
    }

    /**
     * Build action summary.
     *
     * @param array<int, array<string, mixed>> $actions
     * @return array<string, mixed>
     */
    public function buildSummary(array $actions): array
    {
        return [
            'total' => count($actions),
            'high_impact_count' => count(array_filter(
                $actions,
                static fn (array $action): bool => ($action['impact'] ?? '') === 'high'
            )),
            'high_difficulty_count' => count(array_filter(
                $actions,
                static fn (array $action): bool => ($action['difficulty'] ?? '') === 'high'
            )),
            'primary_action_title' => isset($actions[0]['title']) ? (string) $actions[0]['title'] : '',
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function buildActionText(array $input): string
    {
        $action = $input['action'] ?? '';

        if (is_string($action) && trim($action) !== '') {
            return trim($action);
        }

        $title = $input['title'] ?? '改善項目';

        return (is_scalar($title) ? (string) $title : '改善項目') . 'を確認し、必要な改善対応を行います。';
    }

    private function normalizeRank(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(1, (int) $value);
        }

        return 999;
    }
}