<?php

declare(strict_types=1);

/**
 * SAIS - Introduction Phase Builder
 *
 * Builds introduction phases from recommended scope and priority items.
 */

final class IntroductionPhaseBuilder
{
    /** @var array<string, mixed> */
    private array $introductionConfig;

    /**
     * @param array<string, mixed> $introductionConfig
     */
    public function __construct(array $introductionConfig)
    {
        $this->introductionConfig = $introductionConfig;
    }

    /**
     * Build introduction phases.
     *
     * @param array<int, string> $recommendedScope
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<int, array<string, mixed>>
     */
    public function build(
        array $recommendedScope,
        array $priorityItems = [],
        array $estimateItems = []
    ): array {
        $scopeItems = $this->normalizeScope($recommendedScope);

        if ($scopeItems === []) {
            $scopeItems = [
                '導入前改善',
            ];
        }

        $phases = [];

        foreach ($scopeItems as $index => $scope) {
            $templateKey = $this->detectTemplateKey($scope);
            $template = $this->getTemplate($templateKey);
            $relatedPriorityItems = $this->filterPriorityItemsByScope($priorityItems, $scope);
            $relatedEstimateItems = $this->filterEstimateItemsByScope($estimateItems, $scope);

            $phases[] = [
                'phase_id' => 'phase_' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'phase_name' => (string) ($template['phase_name'] ?? $scope),
                'scope' => (string) ($template['scope'] ?? $scope),
                'tasks' => $this->buildPhaseTasks($scope, $relatedPriorityItems, $relatedEstimateItems),
                'priority' => $this->detectPhasePriority($index, $relatedPriorityItems),
                'expected_effect' => $this->buildExpectedEffect($scope),
                'required_check' => $this->buildRequiredCheck($relatedEstimateItems),
                'sass_connection' => (string) ($template['sass_connection'] ?? 'SASS導入前改善'),
            ];
        }

        return $phases;
    }

    /**
     * @param array<int, string> $recommendedScope
     * @return array<int, string>
     */
    private function normalizeScope(array $recommendedScope): array
    {
        return array_values(array_unique(array_filter(
            $recommendedScope,
            static fn (mixed $scope): bool => is_string($scope) && trim($scope) !== ''
        )));
    }

    private function detectTemplateKey(string $scope): string
    {
        if (str_contains($scope, '導入前')) {
            return 'sass_pre_improvement';
        }

        if (str_contains($scope, '情報構造')) {
            return 'structure_improvement';
        }

        if (str_contains($scope, 'コンテンツ')) {
            return 'content_improvement';
        }

        if (str_contains($scope, '導線')) {
            return 'flow_improvement';
        }

        if (str_contains($scope, '運用')) {
            return 'operation_improvement';
        }

        return 'sass_pre_improvement';
    }

    /**
     * @return array<string, mixed>
     */
    private function getTemplate(string $templateKey): array
    {
        $templates = $this->introductionConfig['phase_templates'] ?? [];

        if (is_array($templates) && isset($templates[$templateKey]) && is_array($templates[$templateKey])) {
            return $templates[$templateKey];
        }

        return [
            'phase_name' => '導入前改善',
            'scope' => 'SASS導入前改善',
            'sass_connection' => 'SASS導入前改善',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     * @return array<int, array<string, mixed>>
     */
    private function filterPriorityItemsByScope(array $priorityItems, string $scope): array
    {
        $category = $this->scopeToCategory($scope);

        return array_values(array_filter(
            $priorityItems,
            static fn (array $item): bool => (string) ($item['category'] ?? '') === $category
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<int, array<string, mixed>>
     */
    private function filterEstimateItemsByScope(array $estimateItems, string $scope): array
    {
        return array_values(array_filter(
            $estimateItems,
            static fn (array $item): bool => str_contains((string) ($item['scope'] ?? ''), $scope)
                || str_contains($scope, (string) ($item['scope'] ?? ''))
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<int, string>
     */
    private function buildPhaseTasks(string $scope, array $priorityItems, array $estimateItems): array
    {
        $tasks = [];

        foreach ($priorityItems as $item) {
            $name = (string) ($item['indicator_name'] ?? '');

            if ($name !== '') {
                $tasks[] = $name . 'の確認';
            }
        }

        foreach ($estimateItems as $item) {
            $title = (string) ($item['title'] ?? '');

            if ($title !== '') {
                $tasks[] = $title . 'の実施範囲確認';
            }
        }

        if ($tasks === []) {
            $tasks = $this->getDefaultTasksByScope($scope);
        }

        return array_values(array_unique($tasks));
    }

    /**
     * @return array<int, string>
     */
    private function getDefaultTasksByScope(string $scope): array
    {
        $category = $this->scopeToCategory($scope);
        $tasksMap = $this->introductionConfig['default_tasks_by_category'] ?? [];

        if (is_array($tasksMap) && isset($tasksMap[$category]) && is_array($tasksMap[$category])) {
            return array_values(array_filter(
                $tasksMap[$category],
                static fn (mixed $task): bool => is_string($task) && trim($task) !== ''
            ));
        }

        return [
            '対象範囲の確認',
            '導入条件の確認',
            '実施順序の整理',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     */
    private function detectPhasePriority(int $index, array $priorityItems): string
    {
        if ($index === 0) {
            return 'high';
        }

        if ($priorityItems !== []) {
            return 'medium';
        }

        return 'low';
    }

    private function buildExpectedEffect(string $scope): string
    {
        if (str_contains($scope, '情報構造')) {
            return '情報構造が整理され、サイト全体の理解がしやすくなります。';
        }

        if (str_contains($scope, 'コンテンツ')) {
            return '情報量が増え、サービスの判断材料が伝わりやすくなります。';
        }

        if (str_contains($scope, '導線')) {
            return '訪問者が次の行動へ進みやすくなります。';
        }

        if (str_contains($scope, '運用')) {
            return '導入後の改善運用につなげやすくなります。';
        }

        return 'SASS導入前の確認と改善準備が進めやすくなります。';
    }

    /**
     * @param array<int, array<string, mixed>> $estimateItems
     * @return array<int, string>
     */
    private function buildRequiredCheck(array $estimateItems): array
    {
        $checks = [];

        foreach ($estimateItems as $item) {
            $required = $item['required_check'] ?? [];

            if (!is_array($required)) {
                continue;
            }

            foreach ($required as $check) {
                if (is_string($check) && trim($check) !== '') {
                    $checks[] = trim($check);
                }
            }
        }

        return array_values(array_unique($checks));
    }

    private function scopeToCategory(string $scope): string
    {
        if (str_contains($scope, '情報構造')) {
            return 'depth';
        }

        if (str_contains($scope, 'コンテンツ')) {
            return 'volume';
        }

        if (str_contains($scope, '内部リンク') || str_contains($scope, '情報接続')) {
            return 'relationship';
        }

        if (str_contains($scope, '導線')) {
            return 'flow';
        }

        return 'depth';
    }
}