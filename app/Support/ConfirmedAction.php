<?php

namespace App\Support;

class ConfirmedAction
{
    public function __construct(
        public readonly string $actionKey,
        public readonly string $subjectType,
        public readonly int $subjectId,
        public readonly string $phrase,
        public readonly string $modalName,
    ) {}
}
