<?php

declare(strict_types=1);

/**
 * SAIS - Proposal Data Builder
 *
 * Builds client-facing proposal data sections for SAIS output JSON.
 */

final class ProposalDataBuilder
{
    /** @var array<string, mixed> */
    private array $outputConfig;

    /**
     * @param array<string, mixed> $outputConfig
     */
    public function __construct(array $outputConfig)
    {
        $this->outputConfig = $outputConfig;
    }

    /**
     * Build proposal data.
     *
     * @param array<string, mixed> $target
     * @param array<string, mixed> $proposal
     * @param array<string, mixed> $estimate
     * @param array<string, mixed> $introductionPlan
     * @param array<string, mixed> $sassScopeCandidate
     * @param array<int, mixed> $additionalCheckItems
     * @return array<string, mixed>
     */
    public function build(
        array $target,
        array $proposal,
        array $estimate,
        array $introductionPlan,
        array $sassScopeCandidate,
        array $additionalCheckItems = []
    ): array {
        return [
            'cover' => $this->buildCover($target, $proposal),
            'executive_summary' => $this->buildExecutiveSummary($proposal),
            'diagnosis_summary' => $this->buildDiagnosisSummary($proposal),
            'proposal_overview' => $this->buildProposalOverview($proposal),
            'priority_improvement_items' => $this->buildPriorityImprovementItems($proposal),
            'recommended_actions' => $proposal['recommended_actions'] ?? [],
            'estimate_section' => $this->buildEstimateSection($estimate),
            'introduction_plan_section' => $this->buildIntroductionPlanSection($introductionPlan),
            'sass_scope_section' => $this->buildSassScopeSection($sassScopeCandidate),
            'additional_check_section' => $this->buildAdditionalCheckSection($additionalCheckItems),
            'next_action' => $proposal['suggested_next_step'] ?? '改善対象と導入範囲を確認し、見積内容の確認へ進みます。',
        ];
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    private function buildCover(array $target, array $proposal): array
    {
        return [
            'title' => (string) ($proposal['title'] ?? '診断結果をもとにした導入提案'),
            'target_name' => (string) ($target['domain'] ?? $target['url'] ?? ''),
            'target_url' => (string) ($target['url'] ?? ''),
            'system' => 'SAIS',
            'project' => 'SEEN',
            'document_type' => 'Introduction Proposal',
        ];
    }

    /**
     * @param array<string, mixed> $proposal
     */
    private function buildExecutiveSummary(array $proposal): string
    {
        $clientMessage = $proposal['client_message'] ?? '';

        if (is_string($clientMessage) && trim($clientMessage) !== '') {
            return trim($clientMessage);
        }

        $summary = $proposal['summary'] ?? '';

        if (is_string($summary) && trim($summary) !== '') {
            return trim($summary);
        }

        return 'SADS診断結果をもとに、導入提案を整理しました。';
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    private function buildDiagnosisSummary(array $proposal): array
    {
        return [
            'diagnosis_connection' => (string) ($proposal['diagnosis_connection'] ?? ''),
            'priority_reason' => (string) ($proposal['priority_reason'] ?? ''),
            'confidence_note' => (string) ($proposal['confidence_note'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    private function buildProposalOverview(array $proposal): array
    {
        return [
            'title' => (string) ($proposal['title'] ?? ''),
            'summary' => (string) ($proposal['summary'] ?? ''),
            'scope_summary' => (string) ($proposal['scope_summary'] ?? ''),
            'additional_check_note' => (string) ($proposal['additional_check_note'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<int, array<string, mixed>>
     */
    private function buildPriorityImprovementItems(array $proposal): array
    {
        $actions = $proposal['recommended_actions'] ?? [];
        $effects = $proposal['expected_effects'] ?? [];

        if (!is_array($actions)) {
            return [];
        }

        $items = [];

        foreach ($actions as $index => $action) {
            if (!is_array($action)) {
                continue;
            }

            $effect = isset($effects[$index]) && is_array($effects[$index])
                ? $effects[$index]
                : [];

            $items[] = [
                'rank' => (int) ($action['priority_rank'] ?? ($index + 1)),
                'indicator_id' => (string) ($action['indicator_id'] ?? ''),
                'title' => (string) ($action['title'] ?? ''),
                'category' => (string) ($action['category'] ?? 'unknown'),
                'action' => (string) ($action['action'] ?? ''),
                'expected_effect' => (string) ($effect['expected_effect'] ?? ''),
                'impact' => (string) ($action['impact'] ?? 'medium'),
                'difficulty' => (string) ($action['difficulty'] ?? 'medium'),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $estimate
     * @return array<string, mixed>
     */
    private function buildEstimateSection(array $estimate): array
    {
        return [
            'summary' => (string) ($estimate['estimate_summary'] ?? ''),
            'items' => $estimate['estimate_items'] ?? [],
            'scope' => $estimate['scope'] ?? [],
            'required_check' => $estimate['required_check'] ?? [],
            'pricing_note' => (string) ($estimate['pricing_note'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $introductionPlan
     * @return array<string, mixed>
     */
    private function buildIntroductionPlanSection(array $introductionPlan): array
    {
        return [
            'summary' => (string) ($introductionPlan['plan_summary'] ?? ''),
            'phases' => $introductionPlan['phases'] ?? [],
            'tasks' => $introductionPlan['tasks'] ?? [],
            'sass_connection' => $introductionPlan['sass_connection'] ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $sassScopeCandidate
     * @return array<string, mixed>
     */
    private function buildSassScopeSection(array $sassScopeCandidate): array
    {
        return [
            'summary' => (string) ($sassScopeCandidate['summary'] ?? ''),
            'candidates' => $sassScopeCandidate['candidates'] ?? [],
            'recommended_modules' => $sassScopeCandidate['recommended_modules'] ?? [],
            'operation_candidates' => $sassScopeCandidate['operation_candidates'] ?? [],
            'implementation_notes' => $sassScopeCandidate['implementation_notes'] ?? [],
        ];
    }

    /**
     * @param array<int, mixed> $additionalCheckItems
     * @return array<string, mixed>
     */
    private function buildAdditionalCheckSection(array $additionalCheckItems): array
    {
        $items = [];

        foreach ($additionalCheckItems as $item) {
            if (is_string($item) && trim($item) !== '') {
                $items[] = [
                    'message' => trim($item),
                ];

                continue;
            }

            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return [
            'has_additional_check' => count($items) > 0,
            'items' => $items,
        ];
    }
}