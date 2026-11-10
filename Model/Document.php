<?php

namespace Omnilang\Model;

use Omnilang\Exception\InvalidArgumentException;

/** A file to translate, or translated: its bytes, its name, its media type (from its extension when not given). */
final readonly class Document
{
    public const TYPES = [
        'txt' => 'text/plain',
        'html' => 'text/html',
        'htm' => 'text/html',
        'xml' => 'application/xml',
        'xlf' => 'application/xliff+xml',
        'xliff' => 'application/xliff+xml',
        'json' => 'application/json',
        'md' => 'text/markdown',
        'srt' => 'application/x-subrip',
        'csv' => 'text/csv',
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'epub' => 'application/epub+zip',
    ];

    public string $mimeType;

    public function __construct(
        public string $content,
        public string $filename,
        ?string $mimeType = null,
    ) {
        if ('' === trim($filename) || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException(\sprintf('A document needs a file name without a folder, not "%s".', $filename));
        }
        $this->mimeType = $mimeType ?? self::TYPES[$this->extension()] ?? 'application/octet-stream';
    }

    public static function fromFile(string $path, ?string $filename = null): self
    {
        $content = is_file($path) ? file_get_contents($path) : false;
        if (false === $content) {
            throw new InvalidArgumentException(\sprintf('No file to read at "%s".', $path));
        }

        return new self($content, $filename ?? basename($path));
    }

    /** "docx" of "report.DOCX". */
    public function extension(): string
    {
        return strtolower(pathinfo($this->filename, \PATHINFO_EXTENSION));
    }

    public function size(): int
    {
        return \strlen($this->content);
    }

    /** The same document under another name ("report.fr.docx"), its type kept. */
    public function renamed(string $filename): self
    {
        return new self($this->content, $filename, $this->mimeType);
    }
}
