<?php

declare(strict_types=1);

/**
 * SAIS - Client Message Builder
 *
 * Builds client-facing proposal messages.
 */

final class ClientMessageBuilder
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
     * Build client message.
     *
     * @param array<int, array<string, mixed>> $priorityItems
     * @param array<int, string> $recommendedScope
     * @param array<string, mixed> $additionalCheckResult
     */
    public function build(
        string $clientSummary,
        array $priorityItems = [],
        array $recommendedScope = [],
        array $additionalCheckResult = []
    ): string {
        $primaryName = $this->extractPrimaryName($priorityItems);
        $scopeText = $this->buildScopeText($recommendedScope);
        $checkNote = $this->extractProposalNote($additionalCheckResult);

        if ($clientSummary !== '' && $primaryName !== '') {
            return $clientSummary . ' 特に' . $primaryName . 'を優先し、' . $scopeText . 'として進める提案です。' . $checkNote;
        }

        if ($primaryName !== '') {
            return '診断結果をもとに、特に' . $primaryName . 'を優先して改善内容を整理しました。' . $scopeText . 'として進める提案です。' . $checkNote;
        }

        $defaultMessage = $this->proposalConfig['default_messages']['client_message']
            ?? '診断結果をもとに、優先的に改善すべき項目を整理しました。';

        return $defaultMessage . $checkNote;
    }

    /**
     * Build next step message.
     */
    public function buildNextStep(): string
    {
        return $this->proposalConfig['default_messages']['suggested_next_step']
            ?? '改善対象と導入範囲を確認し、見積内容の確認へ進みます。';
    }

    /**
     * Build confidence note.
     *
     * @param array<string, mixed> $confidence
     */
    public function buildConfidenceNote(array $confidence = []): string
    {
        $display = $confidence['display'] ?? null;

        if (is_numeric($display)) {
            $value = (float) $display;

            if ($value >= 85.0) {
                return $this->proposalConfig['confidence_note']['high']
                    ?? '診断結果は安定しており、提案作成に利用しやすい状態です。';
            }

            if ($value >= 70.0) {
                return $this->proposalConfig['confidence_note']['medium']
                    ?? '一部確認を行うことで、より安全に提案へ進めます。';
            }
        }

        return $this->proposalConfig['confidence_note']['low']
            ?? '提案前に追加確認を行う必要があります。';
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     */
    private function extractPrimaryName(array $priorityItems): string
    {
        $primary = $priorityItems[0] ?? null;

        if (!is_array($primary)) {
            return '';
        }

        $name = $primary['indicator_name'] ?? $primary['title'] ?? '';

        return is_scalar($name) ? (string) $name : '';
    }

    /**
     * @param array<int, string> $recommendedScope
     */
    private function buildScopeText(array $recommendedScope): string
    {
        $items = array_values(array_unique(array_filter(
            $recommendedScope,
            static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
        )));

        if ($items === []) {
            return '導入範囲を確認';
        }

        return implode('、', $items);
    }

    /**
     * @param array<string, mixed> $additionalCheckResult
     */
    private function extractProposalNote(array $additionalCheckResult): string
    {
        $note = $additionalCheckResult['proposal_note'] ?? '';

        if (is_string($note) && trim($note) !== '') {
            return ' ' . trim($note);
        }

        return '';
    }
}