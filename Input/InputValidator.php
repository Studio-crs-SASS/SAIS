<?php

declare(strict_types=1);

/**
 * SAIS - Input Validator
 *
 * Validates SADS SAISBridge Output for SAIS.
 */

final class InputValidator
{
    /** @var array<string, mixed> */
    private array $inputConfig;

    /** @var array<string, mixed> */
    private array $warningsConfig;

    /**
     * @param array<string, mixed> $inputConfig
     * @param array<string, mixed> $warningsConfig
     */
    public function __construct(array $inputConfig, array $warningsConfig)
    {
        $this->inputConfig = $inputConfig;
        $this->warningsConfig = $warningsConfig;
    }

    /**
     * Validate input.
     *
     * @param array<string, mixed> $input
     * @return array{valid: bool, errors: array<int, array<string, mixed>>, warnings: array<int, array<string, mixed>>}
     */
    public function validate(array $input): array
    {
        $errors = [];
        $warnings = [];

        if ($input === []) {
            $errors[] = $this->buildError('INVALID_INPUT_STRUCTURE', ['input']);

            return [
                'valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        $errors = array_merge($errors, $this->validateRequiredRootFields($input));
        $errors = array_merge($errors, $this->validateRequiredValues($input));
        $errors = array_merge($errors, $this->validateFieldTypes($input));

        $warnings = array_merge($warnings, $this->validateOptionalWarnings($input));

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, array<string, mixed>>
     */
    private function validateRequiredRootFields(array $input): array
    {
        $errors = [];
        $requiredFields = $this->inputConfig['required_root_fields'] ?? [];

        if (!is_array($requiredFields)) {
            return [
                $this->buildError('INVALID_INPUT_STRUCTURE', ['required_root_fields']),
            ];
        }

        foreach ($requiredFields as $field) {
            if (!is_string($field)) {
                continue;
            }

            if (!array_key_exists($field, $input)) {
                $errors[] = $this->buildMissingFieldError($field);
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, array<string, mixed>>
     */
    private function validateRequiredValues(array $input): array
    {
        $errors = [];
        $requiredValues = $this->inputConfig['required_values'] ?? [];

        if (!is_array($requiredValues)) {
            return $errors;
        }

        foreach ($requiredValues as $field => $expectedValue) {
            if (!is_string($field)) {
                continue;
            }

            if (!array_key_exists($field, $input)) {
                continue;
            }

            if ($input[$field] !== $expectedValue) {
                $errors[] = match ($field) {
                    'system' => $this->buildError('INVALID_SOURCE_SYSTEM', [$field]),
                    'target_system' => $this->buildError('INVALID_TARGET_SYSTEM', [$field]),
                    'project' => $this->buildError('INVALID_PROJECT', [$field]),
                    default => $this->buildError('INVALID_INPUT_STRUCTURE', [$field]),
                };
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, array<string, mixed>>
     */
    private function validateFieldTypes(array $input): array
    {
        $errors = [];
        $fieldTypes = $this->inputConfig['field_types'] ?? [];

        if (!is_array($fieldTypes)) {
            return $errors;
        }

        foreach ($fieldTypes as $field => $type) {
            if (!is_string($field) || !is_string($type)) {
                continue;
            }

            if (!array_key_exists($field, $input)) {
                continue;
            }

            if (!$this->matchesType($input[$field], $type)) {
                $errors[] = $this->buildError('INVALID_INPUT_STRUCTURE', [$field]);
            }
        }

        return $errors;
    }

    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value),
            default => true,
        };
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, array<string, mixed>>
     */
    private function validateOptionalWarnings(array $input): array
    {
        $warnings = [];

        if (isset($input['confidence']) && is_array($input['confidence'])) {
            $display = $input['confidence']['display'] ?? null;

            if (is_numeric($display) && (float) $display < 70.0) {
                $warnings[] = $this->buildWarning('LOW_CONFIDENCE_INPUT', ['confidence']);
            }
        }

        if (isset($input['proposal_inputs']) && is_array($input['proposal_inputs']) && count($input['proposal_inputs']) === 0) {
            $warnings[] = $this->buildWarning('MISSING_PROPOSAL_INPUT', ['proposal_inputs']);
        }

        if (isset($input['priority_items']) && is_array($input['priority_items']) && count($input['priority_items']) === 0) {
            $warnings[] = $this->buildWarning('MISSING_PRIORITY_ITEMS', ['priority_items']);
        }

        if (isset($input['recommended_scope']) && is_array($input['recommended_scope']) && count($input['recommended_scope']) === 0) {
            $warnings[] = $this->buildWarning('MISSING_RECOMMENDED_SCOPE', ['recommended_scope']);
        }

        if (isset($input['additional_check_items']) && is_array($input['additional_check_items']) && count($input['additional_check_items']) > 0) {
            $warnings[] = $this->buildWarning('ADDITIONAL_CHECK_REQUIRED', ['additional_check_items']);
        }

        return $warnings;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMissingFieldError(string $field): array
    {
        return match ($field) {
            'proposal_inputs' => $this->buildError('MISSING_PROPOSAL_INPUTS', [$field]),
            'priority_items' => $this->buildError('MISSING_PRIORITY_ITEMS', [$field]),
            'confidence' => $this->buildError('MISSING_CONFIDENCE', [$field]),
            default => $this->buildError('INVALID_INPUT_STRUCTURE', [$field]),
        };
    }

    /**
     * @param array<int, string> $affectedItems
     * @return array<string, mixed>
     */
    private function buildError(string $code, array $affectedItems = []): array
    {
        $errors = $this->warningsConfig['errors'] ?? [];

        $template = is_array($errors) && isset($errors[$code]) && is_array($errors[$code])
            ? $errors[$code]
            : [
                'code' => $code,
                'message' => 'Unknown error.',
                'impact' => 'processing_stopped',
            ];

        return [
            'code' => $template['code'] ?? $code,
            'message' => $template['message'] ?? 'Unknown error.',
            'affected_items' => $affectedItems,
            'impact' => $template['impact'] ?? 'processing_stopped',
        ];
    }

    /**
     * @param array<int, string> $affectedItems
     * @return array<string, mixed>
     */
    private function buildWarning(string $code, array $affectedItems = []): array
    {
        $warnings = $this->warningsConfig['warnings'] ?? [];

        $template = is_array($warnings) && isset($warnings[$code]) && is_array($warnings[$code])
            ? $warnings[$code]
            : [
                'code' => $code,
                'message' => 'Unknown warning.',
                'impact' => 'needs_confirmation',
            ];

        return [
            'code' => $template['code'] ?? $code,
            'message' => $template['message'] ?? 'Unknown warning.',
            'affected_items' => $affectedItems,
            'impact' => $template['impact'] ?? 'needs_confirmation',
        ];
    }
}