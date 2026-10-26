<?php

namespace Aldhi88\StarterKit\Services\Cms;

use Aldhi88\StarterKit\Contracts\Cms\CmsRepositoryInterface;
use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class CmsRichContent
{
    private readonly HtmlSanitizer $sanitizer;

    public function __construct(private readonly CmsRepositoryInterface $records)
    {
        $config = (new HtmlSanitizerConfig)->withMaxInputLength(500000)
            ->allowRelativeLinks()->allowRelativeMedias()
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel', 'cms-media'])
            ->allowMediaSchemes(['http', 'https', 'cms-media'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer');
        foreach (['p', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'br', 'blockquote', 'pre', 'code', 'ul', 'ol', 'li', 'sub', 'sup', 'hr'] as $element) {
            $config = $config->allowElement($element, ['class', 'style', ...($element === 'li' ? ['data-list'] : [])]);
        }
        $config = $config->allowElement('a', ['href', 'title', 'target', 'class', 'style'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'class', 'style']);
        $this->sanitizer = new HtmlSanitizer($config);
    }

    /** @return array{html:string,mediaIds:list<int>} */
    public function prepare(string $html): array
    {
        return $this->normalize($html, true);
    }

    public function render(string $html): string
    {
        return $this->normalize($html, false)['html'];
    }

    /** @return array{html:string,mediaIds:list<int>} */
    private function normalize(string $html, bool $writing): array
    {
        $safe = $this->sanitizer->sanitize($html);
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>'.$safe.'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            return ['html' => '', 'mediaIds' => []];
        }
        $references = [];
        foreach (iterator_to_array($body->getElementsByTagName('*')) as $element) {
            if (in_array('ql-ui', explode(' ', $element->getAttribute('class')), true)) {
                $element->parentNode?->removeChild($element);

                continue;
            }
            $this->format($element);
            if ($element->tagName === 'a') {
                $element->setAttribute('rel', 'noopener noreferrer');
                if (! in_array($element->getAttribute('target'), ['_blank', '_self'], true)) {
                    $element->removeAttribute('target');
                }
            }
            if (! in_array($element->tagName, ['img', 'a'], true)) {
                continue;
            }
            $attribute = $element->tagName === 'img' ? 'src' : 'href';
            $source = $element->getAttribute($attribute);
            $id = $this->mediaIdentifier($source);
            if ($id !== null) {
                $references[] = [$element, $attribute, $id];
            } elseif ($attribute === 'href' && str_starts_with(strtolower($source), 'cms-media:')) {
                if ($writing) {
                    throw ValidationException::withMessages(['body' => 'Tautan media tidak valid. Pilih file dari pustaka media CMS.']);
                }
                $element->removeAttribute('href');
            } elseif ($element->tagName === 'img') {
                if ($writing) {
                    throw ValidationException::withMessages(['body' => 'Pilih gambar dari pustaka media CMS atau unggah gambar melalui editor.']);
                }
                $element->parentNode?->removeChild($element);
            }
        }
        $ids = array_values(array_unique(array_column($references, 2)));
        if (count($ids) > 100) {
            throw ValidationException::withMessages(['body' => 'Satu konten maksimal menggunakan 100 file media.']);
        }
        $media = $ids === [] ? collect() : $this->records->bodyMedia($ids, $writing)->keyBy('public_id');
        $mediaIds = [];
        foreach ($references as [$element, $attribute, $publicId]) {
            $file = $media->get($publicId);
            if (! $file || ($attribute === 'src' && ! str_starts_with((string) $file->getAttribute('mime_type'), 'image/'))) {
                if ($writing) {
                    throw ValidationException::withMessages(['body' => 'Media dalam konten tidak tersedia. Pilih file aktif dari pustaka media.']);
                }
                if ($attribute === 'src') {
                    $element->parentNode?->removeChild($element);
                } else {
                    $element->removeAttribute('href');
                }

                continue;
            }
            $mediaIds[] = (int) $file->getKey();
            $element->setAttribute($attribute, $writing ? 'cms-media:'.$publicId : route('starter.cms.media', ['publicId' => $publicId]));
            if ($attribute === 'src') {
                foreach (['width', 'height'] as $dimension) {
                    $value = $element->getAttribute($dimension);
                    if (preg_match('/^[1-9][0-9]{0,4}$/D', $value) !== 1 || (int) $value > 10000) {
                        $element->removeAttribute($dimension);
                    }
                }
            }
        }
        $this->lists($document, $body);
        $output = '';
        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return ['html' => trim($output), 'mediaIds' => array_values(array_unique($mediaIds))];
    }

    private function mediaIdentifier(string $source): ?string
    {
        if (preg_match('/^cms-media:([0-9A-HJKMNP-TV-Z]{26})$/Di', $source, $match) === 1) {
            return strtoupper($match[1]);
        }
        if (str_contains($source, '\\') || preg_match('/[\x00-\x20\x7f]/', $source)) {
            return null;
        }
        $parts = parse_url($source);
        if (! is_array($parts) || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        if (preg_match('#/cms/media/([0-9A-HJKMNP-TV-Z]{26})$#Di', $parts['path'] ?? '', $match) !== 1) {
            return null;
        }
        $id = strtoupper($match[1]);
        $expected = parse_url(route('starter.cms.media', ['publicId' => $id]));
        if ($parts['path'] !== ($expected['path'] ?? '')) {
            return null;
        }
        if (isset($parts['host']) && (strtolower($parts['host']) !== strtolower($expected['host'] ?? '') || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ($parts['port'] ?? null) !== ($expected['port'] ?? null))) {
            return null;
        }
        if (! isset($parts['host']) && ! str_starts_with($source, '/')) {
            return null;
        }

        return $id;
    }

    private function format(DOMElement $element): void
    {
        $styles = [];
        foreach (explode(';', $element->getAttribute('style')) as $declaration) {
            $pair = explode(':', $declaration, 2);
            if (count($pair) !== 2) {
                continue;
            }
            [$property, $value] = array_map(fn (string $part): string => strtolower(trim($part)), $pair);
            $allowed = match ($property) {
                'text-align' => in_array($value, ['left', 'right', 'center', 'justify'], true),
                'direction' => in_array($value, ['ltr', 'rtl'], true),
                'font-size' => in_array($value, ['0.75em', '1.5em', '2.5em'], true),
                'font-family' => in_array(trim($value, "'\""), ['serif', 'monospace', 'sans-serif'], true),
                'padding-left' => preg_match('/^(?:[2-9]|1[0246])em$/D', $value) === 1,
                'color', 'background-color' => preg_match('/^(?:#[0-9a-f]{3}(?:[0-9a-f]{3})?|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)|black|white|red|green|blue|yellow|orange|purple|gray|grey|transparent)$/D', $value) === 1,
                default => false,
            };
            if ($allowed) {
                $styles[$property] = $value;
            }
        }
        foreach (preg_split('/\s+/', $element->getAttribute('class')) ?: [] as $class) {
            if (preg_match('/^ql-align-(center|right|justify)$/D', $class, $match)) {
                $styles['text-align'] = $match[1];
            } elseif (preg_match('/^ql-indent-([1-8])$/D', $class, $match)) {
                $styles['padding-left'] = ((int) $match[1] * 2).'em';
            } elseif (preg_match('/^ql-font-(serif|monospace)$/D', $class, $match)) {
                $styles['font-family'] = $match[1];
            } elseif (preg_match('/^ql-size-(small|large|huge)$/D', $class, $match)) {
                $styles['font-size'] = ['small' => '0.75em', 'large' => '1.5em', 'huge' => '2.5em'][$match[1]];
            } elseif ($class === 'ql-direction-rtl') {
                $styles['direction'] = 'rtl';
            }
        }
        $element->removeAttribute('class');
        $element->removeAttribute('style');
        if ($styles !== []) {
            $element->setAttribute('style', implode(';', array_map(fn (string $key, string $value): string => $key.':'.$value, array_keys($styles), $styles)));
        }
    }

    private function lists(DOMDocument $document, DOMElement $body): void
    {
        foreach (iterator_to_array($body->getElementsByTagName('ol')) as $list) {
            $items = array_filter(iterator_to_array($list->childNodes), fn ($item): bool => $item instanceof DOMElement && $item->tagName === 'li');
            if (! array_filter($items, fn (DOMElement $item): bool => $item->hasAttribute('data-list'))) {
                continue;
            }
            $group = null;
            foreach ($items as $item) {
                $tag = $item->getAttribute('data-list') === 'bullet' ? 'ul' : 'ol';
                if (! $group || $group->tagName !== $tag) {
                    $group = $document->createElement($tag);
                    $list->parentNode?->insertBefore($group, $list);
                }
                $item->removeAttribute('data-list');
                $group->appendChild($item);
            }
            $list->parentNode?->removeChild($list);
        }
        foreach ($body->getElementsByTagName('li') as $item) {
            $item->removeAttribute('data-list');
        }
    }
}
