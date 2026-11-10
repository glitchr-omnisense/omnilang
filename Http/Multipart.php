<?php

namespace Omnilang\Http;

use Omnilang\Model\Document;

/**
 * A multipart/form-data body (RFC 7578) of fields and files, for the
 * engines that take a document as an upload (DeepL, Azure, LibreTranslate)
 * - written here, so that a provider needs no symfony/mime. The boundary
 * is made of the content, so that the same request is the same bytes.
 */
final class Multipart
{
    /**
     * @param array<string, string|int|bool|null> $fields
     * @param array<string, Document>             $files  by field name
     *
     * @return array{headers: array<string, string>, body: string}
     */
    public static function encode(array $fields, array $files = []): array
    {
        $seed = serialize([$fields, array_map(static fn (Document $d) => [$d->filename, hash('sha256', $d->content)], $files)]);
        do {
            $boundary = 'omnilang-'.substr(hash('sha256', $seed), 0, 32);
            $seed .= '.';
        } while (self::contains($boundary, $fields, $files));

        $body = '';
        foreach ($fields as $name => $value) {
            if (null === $value) {
                continue;
            }
            $value = \is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            $body .= '--'.$boundary."\r\n".'Content-Disposition: form-data; name="'.self::quote($name).'"'."\r\n\r\n".$value."\r\n";
        }
        foreach ($files as $name => $document) {
            $body .= '--'.$boundary."\r\n"
                .'Content-Disposition: form-data; name="'.self::quote($name).'"; filename="'.self::quote($document->filename).'"'."\r\n"
                .'Content-Type: '.$document->mimeType."\r\n\r\n"
                .$document->content."\r\n";
        }
        $body .= '--'.$boundary."--\r\n";

        return ['headers' => ['Content-Type' => 'multipart/form-data; boundary='.$boundary], 'body' => $body];
    }

    /**
     * The fields and files of a body this class wrote (what a test reads
     * back from a recorded request).
     *
     * @return array<string, array{value: string, filename: ?string, type: ?string}>
     */
    public static function decode(string $contentType, string $body): array
    {
        if (!preg_match('~boundary=("?)([^";]+)\1~', $contentType, $m)) {
            return [];
        }
        $parts = [];
        foreach (explode('--'.$m[2], $body) as $chunk) {
            if (!str_contains($chunk, "\r\n\r\n")) {
                continue;
            }
            [$head, $value] = explode("\r\n\r\n", ltrim($chunk, "\r\n"), 2);
            if (!preg_match('~name="([^"]*)"~', $head, $name)) {
                continue;
            }
            preg_match('~filename="([^"]*)"~', $head, $file);
            preg_match('~Content-Type:\s*(\S+)~i', $head, $type);
            $parts[$name[1]] = ['value' => substr($value, 0, -2), 'filename' => $file[1] ?? null, 'type' => $type[1] ?? null];
        }

        return $parts;
    }

    private static function quote(string $value): string
    {
        return str_replace(['"', "\r", "\n"], ['%22', '%0D', '%0A'], $value);
    }

    /**
     * @param array<string, string|int|bool|null> $fields
     * @param array<string, Document>             $files
     */
    private static function contains(string $boundary, array $fields, array $files): bool
    {
        foreach ($fields as $value) {
            if (\is_string($value) && str_contains($value, $boundary)) {
                return true;
            }
        }
        foreach ($files as $document) {
            if (str_contains($document->content, $boundary)) {
                return true;
            }
        }

        return false;
    }
}
