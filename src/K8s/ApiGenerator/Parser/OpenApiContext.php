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

namespace K8s\ApiGenerator\Parser;

use K8s\ApiGenerator\Parser\Metadata\Metadata;
use Swagger\Annotations\AbstractAnnotation;
use Swagger\Annotations\Definition;
use Swagger\Annotations\Swagger;

readonly class OpenApiContext
{
    public function __construct(
        private AbstractAnnotation $subject,
        private Swagger $openApi
    ) {
    }

    public function getSubject(): AbstractAnnotation
    {
        return $this->subject;
    }

    public function findRef(string $ref): Definition|null
    {
        $ref = $this->openApi->ref($ref);

        if ($ref && !$ref instanceof Definition) {
            throw new \RuntimeException(sprintf(
                'Expected a Definition, got: %s',
                get_class($ref)
            ));
        }

        return $ref;
    }
}
