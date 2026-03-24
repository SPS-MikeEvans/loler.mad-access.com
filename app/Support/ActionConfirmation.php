<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ActionConfirmation
{
    private const SESSION_KEY = 'action_confirmations';

    private const TTL_MINUTES = 30;

    public function issue(string $actionKey, string $subjectType, int $subjectId, string $phrase): array
    {
        $payload = [
            'user_id' => auth()->id(),
            'action_key' => $actionKey,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'phrase' => $phrase,
            'issued_at' => now()->toIso8601String(),
        ];

        session()->put($this->sessionPath($actionKey, $subjectType, $subjectId), $payload);

        return $payload;
    }

    public function matches(string $actionKey, string $subjectType, int $subjectId, string $phrase): bool
    {
        $payload = session($this->sessionPath($actionKey, $subjectType, $subjectId));

        if (! is_array($payload)) {
            return false;
        }

        $issuedAt = isset($payload['issued_at']) ? Carbon::parse($payload['issued_at']) : null;

        if (! $issuedAt || $issuedAt->lt(now()->subMinutes(self::TTL_MINUTES))) {
            return false;
        }

        return (int) ($payload['user_id'] ?? 0) === (int) auth()->id()
            && ($payload['action_key'] ?? null) === $actionKey
            && ($payload['subject_type'] ?? null) === $subjectType
            && (int) ($payload['subject_id'] ?? 0) === $subjectId
            && ($payload['phrase'] ?? null) === $phrase;
    }

    public function consume(string $actionKey, string $subjectType, int $subjectId): void
    {
        session()->forget($this->sessionPath($actionKey, $subjectType, $subjectId));
    }

    public function modalName(string $actionKey, int $subjectId): string
    {
        return 'confirm-'.str_replace(['.', ':', '_'], '-', $actionKey).'-'.$subjectId;
    }

    private function sessionPath(string $actionKey, string $subjectType, int $subjectId): string
    {
        return self::SESSION_KEY.'.'.auth()->id().'.'.$actionKey.'.'.$subjectType.'.'.$subjectId;
    }
}
