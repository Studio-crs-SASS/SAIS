<?php

declare(strict_types=1);

/**
 * SAIS - Scope Organizer
 *
 * Organizes recommended_scope from SADS SAISBridge Output.
 */

final class ScopeOrganizer
{
    /**
     * Organize recommended scope.
     *
     * @param array<int, mixed> $recommendedScope
     * @return array<string, mixed>
     */
    public function organize(array $recommendedScope): array
    {
        $items = $this->normalizeScopes($recommendedScope);
        $uniqueItems = $this->uniqueScopes($items);

        return [
            'items' => $uniqueItems,
            'estimate_scope' => $this->buildEstimateScope($uniqueItems),
            'introduction_scope' => $this->buildIntroductionScope($uniqueItems),
            'sass_scope_candidates' => $this->buildSassScopeCandidates($uniqueItems),
            'summary' => [
                'total' => count($uniqueItems),
                'has_sass_pre_improvement' => $this->containsKeyword($uniqueItems, 'SASS導入前改善'),
                'has_sass_operation_improvement' => $this->containsKeyword($uniqueItems, 'SASS運用改善'),
                'has_structure_improvement' => $this->containsKeyword($uniqueItems, '情報構造改善'),
                'has_flow_improvement' => $this->containsKeyword($uniqueItems, '導線改善'),
            ],
        ];
    }

    /**
     * @param array<int, mixed> $recommendedScope
     * @return array<int, string>
     */
    private function normalizeScopes(array $recommendedScope): array
    {
        $items = [];

        foreach ($recommendedScope as $scope) {
            if (is_string($scope)) {
                $value = trim($scope);

                if ($value !== '') {
                    $items[] = $value;
                }

                continue;
            }

            if (is_array($scope)) {
                $value = $scope['scope'] ?? $scope['name'] ?? $scope['label'] ?? null;

                if (is_scalar($value)) {
                    $value = trim((string) $value);

                    if ($value !== '') {
                        $items[] = $value;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * @param array<int, string> $items
     * @return array<int, string>
     */
    private function uniqueScopes(array $items): array
    {
        return array_values(array_unique($items));
    }

    /**
     * @param array<int, string> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildEstimateScope(array $items): array
    {
        $result = [];

        foreach ($items as $index => $scope) {
            $result[] = [
                'scope_id' => 'estimate_scope_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'scope_name' => $scope,
                'pricing_status' => 'scope_needs_confirmation',
            ];
        }

        return $result;
    }

    /**
     * @param array<int, string> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildIntroductionScope(array $items): array
    {
        $result = [];

        foreach ($items as $index => $scope) {
            $result[] = [
                'scope_id' => 'introduction_scope_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'scope_name' => $scope,
                'phase_candidate' => $this->detectPhaseCandidate($scope),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, string> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildSassScopeCandidates(array $items): array
    {
        $result = [];

        foreach ($items as $index => $scope) {
            $result[] = [
                'candidate_id' => 'sass_scope_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'scope_name' => $scope,
                'sass_connection_type' => $this->detectSassConnectionType($scope),
                'note' => $scope . 'をSASS導入候補範囲として整理します。',
            ];
        }

        return $result;
    }

    private function detectPhaseCandidate(string $scope): string
    {
        if (str_contains($scope, '導入前')) {
            return '導入前改善';
        }

        if (str_contains($scope, '情報構造')) {
            return '情報構造改善';
        }

        if (str_contains($scope, 'コンテンツ')) {
            return 'コンテンツ改善';
        }

        if (str_contains($scope, '導線')) {
            return '導線改善';
        }

        if (str_contains($scope, '運用')) {
            return '運用改善';
        }

        return '導入範囲確認';
    }

    private function detectSassConnectionType(string $scope): string
    {
        if (str_contains($scope, '導入前')) {
            return 'pre_improvement';
        }

        if (str_contains($scope, '情報構造')) {
            return 'structure_improvement';
        }

        if (str_contains($scope, 'コンテンツ')) {
            return 'content_improvement';
        }

        if (str_contains($scope, '内部リンク') || str_contains($scope, '情報接続')) {
            return 'relationship_improvement';
        }

        if (str_contains($scope, '導線')) {
            return 'flow_improvement';
        }

        if (str_contains($scope, '運用')) {
            return 'operation_improvement';
        }

        return 'pre_improvement';
    }

    /**
     * @param array<int, string> $items
     */
    private function containsKeyword(array $items, string $keyword): bool
    {
        foreach ($items as $item) {
            if (str_contains($item, $keyword)) {
                return true;
            }
        }

        return false;
    }
}