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

namespace K8s\ApiGenerator\Code;

class CodeOptions
{
    private string $annotationsBaseNamespace = 'K8s\\Core\\Annotation';

    public function __construct(
        private readonly string $apiVersion,
        private readonly string $generatorVersion,
        private readonly string $rootNamespace,
        private readonly string $srcDir,
    ) {
    }

    public function getAnnotationsNamespace(): string
    {
        return $this->annotationsBaseNamespace;
    }

    public function getRootNamespace(): string
    {
        return $this->rootNamespace;
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    public function getSrcDir(): string
    {
        return $this->srcDir;
    }

    public function getGeneratorVersion(): string
    {
        return $this->generatorVersion;
    }
}
