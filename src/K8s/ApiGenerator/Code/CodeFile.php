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

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

readonly class CodeFile
{
    public function __construct(
        private PhpNamespace $phpNamespace,
        private ClassType $classType
    ) {
    }

    public function getPhpNamespace(): PhpNamespace
    {
        return $this->phpNamespace;
    }

    public function getFullFileName(): string
    {
        return sprintf(
            '%s.php',
            implode(DIRECTORY_SEPARATOR, explode('\\', $this->getFqcn()))
        );
    }

    private function getFqcn(): string
    {
        return sprintf(
            '%s\\%s',
            $this->phpNamespace->getName(),
            $this->classType->getName()
        );
    }
}
