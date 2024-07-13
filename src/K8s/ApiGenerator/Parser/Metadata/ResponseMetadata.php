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

namespace K8s\ApiGenerator\Parser\Metadata;

use Swagger\Annotations\Response;

readonly class ResponseMetadata
{
    public function __construct(private Response $response, private DefinitionMetadata|null $definition = null)
    {
    }

    public function isSuccess(): bool
    {
        return $this->response->response >= 200
            && $this->response->response < 300;
    }

    public function isStringResponse(): bool
    {
        return $this->response->schema->type === 'string';
    }

    public function getDefinition(): DefinitionMetadata|null
    {
        return $this->definition;
    }
}
