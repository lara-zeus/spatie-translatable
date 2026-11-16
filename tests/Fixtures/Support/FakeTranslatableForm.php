<?php

namespace Tests\Fixtures\Support;

use Illuminate\Validation\ValidationException;

class FakeTranslatableForm
{
    public array $data;

    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    public function validate(): void
    {
        throw ValidationException::withMessages(['title' => 'Title is required']);
    }

    public function getState(): array
    {
        return $this->data;
    }

    public function fill(array $data): void
    {
        $this->data = $data;
    }

    public function partialRawState(array $state): static
    {
        $this->data = [...$this->data, ...$state];

        return $this;
    }

    public function getStatePath(): string
    {
        return 'data';
    }

    public function hydrateStatePartially(array $statePaths): void {}

    public function getValidationRules(): array
    {
        return [];
    }

    public function getRawState(): array
    {
        return $this->data;
    }

    public function callBeforeStateDehydrated(array &$state): void {}

    public function dehydrateState(array &$state): void {}

    public function mutateDehydratedState(array &$state): void {}
}
