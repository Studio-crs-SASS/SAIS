<?php

declare(strict_types=1);

/**
 * SAIS - Proposal Summary Builder
 *
 * Builds proposal summary from client summary, title, and priority items.
 */

final class ProposalSummaryBuilder
{
    /**
     * Build proposal summary.
     *
     * @param array<int, array<string, mixed>> $priorityItems
     */
    public function build(string $clientSummary, string $proposalTitle, array $priorityItems = []): string
    {
        $primaryItemName = $this->extractPrimaryItemName($priorityItems);
        $primaryCategory = $this->extractPrimaryCategory($priorityItems);

        if ($clientSummary !== '' && $primaryItemName !== '') {
            return $clientSummary . ' ' . $primaryItemName . 'を中心に、' . $proposalTitle . 'として整理します。';
        }

        if ($primaryItemName !== '') {
            return $primaryItemName . 'を中心に、' . $proposalTitle . 'として整理します。';
        }

        if ($primaryCategory !== 'unknown') {
            return $this->categoryToMessage($primaryCategory) . 'を中心に、導入提案を整理します。';
        }

        return 'SADS診断結果をもとに、導入提案を整理します。';
    }

    /**
     * Build diagnosis connection sentence.
     */
    public function buildDiagnosisConnection(): string
    {
        return 'SADS診断で確認された改善優先項目をもとに、導入提案を整理します。';
    }

    /**
     * Build priority reason.
     *
     * @param array<int, array<string, mixed>> $priorityItems
     */
    public function buildPriorityReason(array $priorityItems = []): string
    {
        $primaryItemName = $this->extractPrimaryItemName($priorityItems);
        $primaryCategory = $this->extractPrimaryCategory($priorityItems);

        if ($primaryItemName !== '') {
            return $primaryItemName . 'の優先度が高いため、最初に確認する改善対象として扱います。';
        }

        if ($primaryCategory !== 'unknown') {
            return $this->categoryToMessage($primaryCategory) . 'の優先度が高いため、提案の中心として扱います。';
        }

        return 'SADS診断結果で確認された優先項目をもとに、提案の中心を整理します。';
    }

    /**
     * Build scope summary.
     *
     * @param array<int, string> $recommendedScope
     */
    public function buildScopeSummary(array $recommendedScope = []): string
    {
        $items = array_values(array_unique(array_filter(
            $recommendedScope,
            static fn (mixed $item): bool => is_string($item) && trim($item) !== ''
        )));

        if ($items === []) {
            return '今回の導入範囲は、提案前の確認をもとに整理します。';
        }

        return '今回の提案では、' . implode('、', $items) . 'を中心範囲とします。';
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     */
    private function extractPrimaryItemName(array $priorityItems): string
    {
        $primary = $priorityItems[0] ?? null;

        if (!is_array($primary)) {
            return '';
        }

        $name = $primary['indicator_name'] ?? $primary['title'] ?? '';

        return is_scalar($name) ? (string) $name : '';
    }

    /**
     * @param array<int, array<string, mixed>> $priorityItems
     */
    private function extractPrimaryCategory(array $priorityItems): string
    {
        $primary = $priorityItems[0] ?? null;

        if (!is_array($primary)) {
            return 'unknown';
        }

        $category = $primary['category'] ?? 'unknown';

        return is_scalar($category) ? strtolower(trim((string) $category)) : 'unknown';
    }

    private function categoryToMessage(string $category): string
    {
        return match ($category) {
            'depth' => '情報構造',
            'volume' => 'コンテンツ',
            'relationship' => '情報接続',
            'flow' => '行動導線',
            default => '改善項目',
        };
    }
}