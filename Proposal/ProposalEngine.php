<?php

declare(strict_types=1);

/**
 * SAIS - Proposal Engine
 *
 * Builds proposal data from SADS SAISBridge Output.
 */

require_once __DIR__ . '/ProposalTitleBuilder.php';
require_once __DIR__ . '/ProposalSummaryBuilder.php';
require_once __DIR__ . '/RecommendedActionBuilder.php';
require_once __DIR__ . '/ExpectedEffectBuilder.php';
require_once __DIR__ . '/ClientMessageBuilder.php';

final class ProposalEngine
{
    /** @var array<string, mixed> */
    private array $proposalConfig;

    private ProposalTitleBuilder $titleBuilder;
    private ProposalSummaryBuilder $summaryBuilder;
    private RecommendedActionBuilder $actionBuilder;
    private ExpectedEffectBuilder $effectBuilder;
    private ClientMessageBuilder $messageBuilder;

    /**
     * @param array<string, mixed> $proposalConfig
     */
    public function __construct(array $proposalConfig)
    {
        $this->proposalConfig = $proposalConfig;
        $this->titleBuilder = new ProposalTitleBuilder($proposalConfig);
        $this->summaryBuilder = new ProposalSummaryBuilder();
        $this->actionBuilder = new RecommendedActionBuilder();
        $this->effectBuilder = new ExpectedEffectBuilder();
        $this->messageBuilder = new ClientMessageBuilder($proposalConfig);
    }

    /**
     * Build proposal.
     *
     * @param array<int, array<string, mixed>> $proposalInputs
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, string> $recommendedScope
     * @param array<string, mixed> $confidence
     * @param array<string, mixed> $additionalCheckResult
     * @return array<string, mixed>
     */
    public function build(
        array $proposalInputs,
        array $priorityItems,
        string $clientSummary = '',
        array $recommendedScope = [],
        array $confidence = [],
        array $additionalCheckResult = []
    ): array {
        $title = $this->titleBuilder->buildWithCombination($priorityItems, $proposalInputs);
        $recommendedActions = $this->actionBuilder->buildTop($proposalInputs, 5);
        $expectedEffects = $this->effectBuilder->buildTop($proposalInputs, 5);

        return [
            'title' => $title,
            'summary' => $this->summaryBuilder->build($clientSummary, $title, $priorityItems),
            'diagnosis_connection' => $this->summaryBuilder->buildDiagnosisConnection(),
            'priority_reason' => $this->summaryBuilder->buildPriorityReason($priorityItems),
            'recommended_actions' => $recommendedActions,
            'expected_effects' => $expectedEffects,
            'scope_summary' => $this->summaryBuilder->buildScopeSummary($recommendedScope),
            'confidence_note' => $this->messageBuilder->buildConfidenceNote($confidence),
            'additional_check_note' => $this->buildAdditionalCheckNote($additionalCheckResult),
            'suggested_next_step' => $this->messageBuilder->buildNextStep(),
            'client_message' => $this->messageBuilder->build(
                $clientSummary,
                $priorityItems,
                $recommendedScope,
                $additionalCheckResult
            ),
            'summary_data' => [
                'action_summary' => $this->actionBuilder->buildSummary($recommendedActions),
                'effect_summary' => $this->effectBuilder->buildSummary($expectedEffects),
                'combined_effect_message' => $this->effectBuilder->buildCombinedMessage($expectedEffects),
            ],
        ];
    }

    /**
     * Build minimal proposal when inputs are thin.
     *
     * @return array<string, mixed>
     */
    public function buildFallback(): array
    {
        $defaultMessages = $this->proposalConfig['default_messages'] ?? [];

        return [
            'title' => '診断結果をもとにした導入提案',
            'summary' => 'SADS診断結果をもとに、導入提案を整理します。',
            'diagnosis_connection' => $defaultMessages['diagnosis_connection']
                ?? 'SADS診断で確認された改善優先項目をもとに、導入提案を整理します。',
            'priority_reason' => 'SADS診断結果で確認された優先項目をもとに、提案の中心を整理します。',
            'recommended_actions' => [],
            'expected_effects' => [],
            'scope_summary' => '今回の導入範囲は、提案前の確認をもとに整理します。',
            'confidence_note' => $this->proposalConfig['confidence_note']['low']
                ?? '提案前に追加確認を行う必要があります。',
            'additional_check_note' => $defaultMessages['additional_check_none']
                ?? '現時点で大きな追加確認項目はありません。',
            'suggested_next_step' => $defaultMessages['suggested_next_step']
                ?? '改善対象と導入範囲を確認し、見積内容の確認へ進みます。',
            'client_message' => $defaultMessages['client_message']
                ?? '診断結果をもとに、優先的に改善すべき項目を整理しました。',
            'summary_data' => [
                'action_summary' => [
                    'total' => 0,
                    'high_impact_count' => 0,
                    'high_difficulty_count' => 0,
                    'primary_action_title' => '',
                ],
                'effect_summary' => [
                    'total' => 0,
                    'primary_indicator_id' => '',
                    'primary_effect' => '',
                    'high_impact_count' => 0,
                ],
                'combined_effect_message' => '改善対応により、サイト全体の分かりやすさと導線の改善が期待できます。',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $additionalCheckResult
     */
    private function buildAdditionalCheckNote(array $additionalCheckResult): string
    {
        $note = $additionalCheckResult['proposal_note'] ?? '';

        if (is_string($note) && trim($note) !== '') {
            return trim($note);
        }

        return $this->proposalConfig['default_messages']['additional_check_none']
            ?? '現時点で大きな追加確認項目はありません。';
    }
}