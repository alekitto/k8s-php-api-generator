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

namespace K8s\ApiGenerator\Parser\MetadataGenerator;

use K8s\ApiGenerator\Parser\Formatter\ServiceGroupNameFormatter;
use K8s\ApiGenerator\Parser\Metadata\Metadata;
use K8s\ApiGenerator\Parser\Metadata\OperationMetadata;
use K8s\ApiGenerator\Parser\Metadata\ResponseMetadata;
use K8s\ApiGenerator\Parser\OpenApiContext;
use Swagger\Annotations\Operation;
use Swagger\Annotations\Path;
use Swagger\Annotations\Response;

class OperationMetadataGenerator
{
    public const OPERATIONS = [
        'get',
        'delete',
        'post',
        'patch',
        'put',
        'options',
        'head',
    ];

    public function __construct()
    {
    }

    /**
     * @return OperationMetadata[]
     */
    public function generate(OpenApiContext $openApiObject, Metadata $generatedApi): array
    {
        /** @var Path $path */
        $path = $openApiObject->getSubject();

        $serviceOperations = [];
        foreach (self::OPERATIONS as $httpOperation) {
            if (isset($path->$httpOperation)) {
                /** @var Operation $apiOperation */
                $apiOperation = $path->$httpOperation;
                $responses = $this->parseResponses(
                    $apiOperation->responses,
                    $openApiObject,
                    $generatedApi
                );
                $serviceOperations[] = new OperationMetadata(
                    $path,
                    $apiOperation,
                    $responses
                );
            }
        }

        return $serviceOperations;
    }

    public function supports(OpenApiContext $openApiObject): bool
    {
        return $openApiObject->getSubject() instanceof Path;
    }

    /**
     * @return ResponseMetadata[]
     */
    private function parseResponses(array $responses, OpenApiContext $openApiContext, Metadata $generatedApi): array
    {
        $responsesMetadata = [];

        /** @var Response $response */
        foreach ($responses as $response) {
            if (empty($response->schema) || empty($response->schema->ref)) {
                $responsesMetadata[] = new ResponseMetadata($response);

                continue;
            }

            $def = $openApiContext->findRef($response->schema->ref);
            $definition = $generatedApi->findDefinitionByGoPackageName($def->definition);
            if (!$definition) {
                throw new \RuntimeException('No model found: '. $def->definition);
            }
            $responsesMetadata[] = new ResponseMetadata($response, $definition);
        }

        return $responsesMetadata;
    }
}
