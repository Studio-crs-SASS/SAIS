<?php

declare(strict_types=1);

/**
 * SAIS - SASS Scope Candidate Engine
 *
 * Builds SASS introduction scope candidates.
 */

final class SASSScopeCandidateEngine
{
    /** @var array<string, mixed> */
    private array $scopeConfig;

    /**
     * @param array<string, mixed> $scopeConfig
     */
    public function __construct(array $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Build SASS scope candidate.
     *
     * @param array<int, array<string, mixed>> $scopeCandidates
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<string, mixed>
     */
    public function build(array $scopeCandidates, array $priorityItems = [], array $estimateItems = []): array
    {
        $candidates = $this->normalizeCandidates($scopeCandidates, $priorityItems);

        return [
            'summary' => $this->scopeConfig['default_messages']['summary']
                ?? 'SASS導入候補範囲を整理します。',
            'candidates' => $candidates,
            'recommended_modules' => $this->buildRecommendedModules($candidates),
            'operation_candidates' => $this->buildOperationCandidates($candidates),
            'implementation_notes' => $this->buildImplementationNotes($candidates, $estimateItems),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $scopeCandidates
     * @param array<int, array<string, mixed>> $priorityItems
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCandidates(array $scopeCandidates, array $priorityItems): array
    {
        $items = [];

        foreach ($scopeCandidates as $index => $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $scopeName = (string) ($candidate['scope_name'] ?? '');
            $connectionType = (string) ($candidate['sass_connection_type'] ?? 'pre_improvement');
            $priorityItem = $priorityItems[$index] ?? [];

            $items[] = [
                'scope_name' => $scopeName,
                'source_recommendation' => (string) ($candidate['source_recommendation'] ?? ($priorityItem['indicator_id'] ?? '')),
                'priority' => (int) ($candidate['priority'] ?? ($priorityItem['rank'] ?? ($index + 1))),
                'impact' => (string) ($candidate['impact'] ?? 'medium'),
                'difficulty' => (string) ($candidate['difficulty'] ?? 'medium'),
                'sass_connection_type' => $connectionType,
                'note' => (string) ($candidate['note'] ?? $scopeName . 'をSASS導入候補範囲として扱います。'),
            ];
        }

        usort(
            $items,
            static fn (array $a, array $b): int => ((int) ($a['priority'] ?? 999)) <=> ((int) ($b['priority'] ?? 999))
        );

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, string>
     */
    private function buildRecommendedModules(array $candidates): array
    {
        $modules = [];
        $moduleMap = $this->scopeConfig['recommended_modules_by_category'] ?? [];

        foreach ($candidates as $candidate) {
            $category = $this->connectionTypeToCategory((string) ($candidate['sass_connection_type'] ?? ''));

            if (isset($moduleMap[$category]) && is_array($moduleMap[$category])) {
                foreach ($moduleMap[$category] as $module) {
                    if (is_string($module)) {
                        $modules[] = $module;
                    }
                }
            }
        }

        return array_values(array_unique($modules));
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, string>
     */
    private function buildOperationCandidates(array $candidates): array
    {
        $operations = [];
        $operationMap = $this->scopeConfig['operation_candidates_by_category'] ?? [];

        foreach ($candidates as $candidate) {
            $category = $this->connectionTypeToCategory((string) ($candidate['sass_connection_type'] ?? ''));

            if (isset($operationMap[$category]) && is_array($operationMap[$category])) {
                foreach ($operationMap[$category] as $operation) {
                    if (is_string($operation)) {
                        $operations[] = $operation;
                    }
                }
            }
        }

        return array_values(array_unique($operations));
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<int, string>
     */
    private function buildImplementationNotes(array $candidates, array $estimateItems): array
    {
        $notes = [];

        $defaultNote = $this->scopeConfig['default_messages']['implementation_note']
            ?? 'SASS導入前に対象範囲、優先順位、運用条件を確認します。';

        $notes[] = $defaultNote;

        foreach ($candidates as $candidate) {
            $scopeName = (string) ($candidate['scope_name'] ?? '');

            if ($scopeName !== '') {
                $notes[] = $scopeName . 'の対象範囲を確認します。';
            }
        }

        foreach ($estimateItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = (string) ($item['title'] ?? '');

            if ($title !== '') {
                $notes[] = $title . 'の見積条件を確認します。';
            }
        }

        return array_values(array_unique($notes));
    }

    private function connectionTypeToCategory(string $connectionType): string
    {
        return match ($connectionType) {
            'structure_improvement' => 'depth',
            'content_improvement' => 'volume',
            'relationship_improvement' => 'relationship',
            'flow_improvement' => 'flow',
            'operation_improvement' => 'flow',
            default => 'depth',
        };
    }
}