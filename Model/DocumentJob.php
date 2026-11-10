<?php

namespace Omnilang\Model;

/**
 * A document's translation, as the engine follows it. An asynchronous
 * engine (DeepL) gives an id and a key to ask for its status, then its
 * result once done; a synchronous one (Google, Azure, Amazon,
 * LibreTranslate) answers done at once, $result filled.
 *
 * $key is the engine's secret for this job (DeepL's document_key): keep it
 * where the job is kept, never show it; toArray() leaves it out.
 */
final readonly class DocumentJob
{
    public function __construct(
        public string $id,
        public string $provider,
        public DocumentStatus $status,
        public Language $target,
        public ?Language $source = null,
        #[\SensitiveParameter]
        public ?string $key = null,
        public ?string $filename = null,
        public ?int $secondsRemaining = null,
        public ?int $billedCharacters = null,
        public ?string $error = null,
        public ?Document $result = null,
        public ?Language $detected = null,
    ) {
    }

    public function isDone(): bool
    {
        return DocumentStatus::Done === $this->status;
    }

    public function failed(): bool
    {
        return DocumentStatus::Error === $this->status;
    }

    /** The same job at a later status, as the engine says it. */
    public function with(DocumentStatus $status, ?int $secondsRemaining = null, ?int $billedCharacters = null, ?string $error = null, ?Document $result = null): self
    {
        return new self($this->id, $this->provider, $status, $this->target, $this->source, $this->key, $this->filename, $secondsRemaining, $billedCharacters ?? $this->billedCharacters, $error, $result ?? $this->result, $this->detected);
    }

    /** @return array<string, mixed> the job without its key */
    public function toArray(): array
    {
        return [
            'id' => $this->id, 'provider' => $this->provider, 'status' => $this->status->value, 'target' => $this->target->code, 'source' => $this->source?->code,
            'detected' => $this->detected?->code, 'filename' => $this->filename, 'secondsRemaining' => $this->secondsRemaining, 'billedCharacters' => $this->billedCharacters,
            'error' => $this->error, 'result' => null === $this->result ? null : ['filename' => $this->result->filename, 'mimeType' => $this->result->mimeType, 'size' => $this->result->size()],
        ];
    }
}
