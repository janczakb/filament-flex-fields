<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizer
{
    protected ?SymfonyHtmlSanitizer $sanitizer = null;

    public function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return $this->sanitizer()->sanitize($html);
    }

    protected function sanitizer(): SymfonyHtmlSanitizer
    {
        if ($this->sanitizer !== null) {
            return $this->sanitizer;
        }

        $allowedClassAttributes = [
            'class',
            'role',
            'aria-hidden',
            'aria-selected',
            'aria-label',
            'title',
            'loading',
            'data-fff-user-select-names',
            'data-fff-user-select-tags',
        ];

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowElement('div', $allowedClassAttributes)
            ->allowElement('span', $allowedClassAttributes)
            ->allowElement('img', ['class', 'src', 'alt', 'loading', 'width', 'height'])
            ->allowElement('svg', ['class', 'viewBox', 'fill', 'xmlns', 'width', 'height', 'aria-hidden', 'role'])
            ->allowElement('path', ['d', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin'])
            ->allowElement('circle', ['cx', 'cy', 'r', 'fill', 'stroke'])
            ->allowElement('rect', ['x', 'y', 'width', 'height', 'fill', 'rx', 'ry'])
            ->allowElement('g', ['class', 'fill', 'transform'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->forceHttpsUrls(false);

        $this->sanitizer = new SymfonyHtmlSanitizer($config);

        return $this->sanitizer;
    }
}
