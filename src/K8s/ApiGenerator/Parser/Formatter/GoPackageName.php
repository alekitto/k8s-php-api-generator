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

namespace K8s\ApiGenerator\Parser\Formatter;

readonly class GoPackageName
{
    public function __construct(private string $goPackageName, private string $phpNamespace, private string $phpName)
    {
    }

    public function getGoPackageName(): string
    {
        return $this->goPackageName;
    }

    public function getPhpNamespace(): string
    {
        return $this->phpNamespace;
    }

    public function getPhpName(): string
    {
        return $this->phpName;
    }

    public function getPhpFqcn(): string
    {
        return sprintf(
            '%s\\%s',
            $this->phpNamespace,
            $this->phpName
        );
    }

    public function __toString(): string
    {
        return $this->getPhpFqcn();
    }
}
