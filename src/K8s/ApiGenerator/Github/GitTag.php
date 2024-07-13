<?php

/**
 * This file is part of the k8s/api-generator library.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace K8s\ApiGenerator\Github;

readonly class GitTag
{
    public function __construct(private array $tag)
    {
    }

    public function getRef(): string
    {
        return $this->tag['ref'];
    }

    public function getCommonName(): string
    {
        return substr($this->tag['ref'], 10);
    }

    public function isStable(): bool
    {
        $version = strtolower($this->getCommonName());

        return !str_contains($version, '-rc')
            && !str_contains($version, '-beta')
            && !str_contains($version, '-dev')
            && !str_contains($version, '-alpha');
    }

    public function startsWith(string $name): bool
    {
        return str_starts_with($this->getCommonName(), $name);
    }
}
