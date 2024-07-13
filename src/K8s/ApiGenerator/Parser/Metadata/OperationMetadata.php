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

use K8s\ApiGenerator\Parser\Formatter\GoPackageNameFormatter;
use Swagger\Annotations\Operation;
use Swagger\Annotations\Parameter;
use Swagger\Annotations\Path;

readonly class OperationMetadata
{
    private const DEPRECATION_MARKER = 'Deprecated: ';

    private const VOID_METHODS = [
        'connect',
        'watch',
        'watchlist',
    ];

    /**
     * @param ResponseMetadata[] $responses
     */
    public function __construct(private Path $path, private Operation $operation, private array $responses)
    {
    }

    public function getMethod(): string
    {
        return $this->operation->method;
    }

    public function getDescription(): string
    {
        return ucfirst($this->operation->description);
    }

    public function getUriPath(): string
    {
        return $this->path->path;
    }

    public function getKubernetesGroup(): ?string
    {
        if (!isset($this->operation->x['kubernetes-group-version-kind'])) {
            return null;
        }

        return $this->operation->x['kubernetes-group-version-kind']->group;
    }

    public function getKubernetesVersion(): ?string
    {
        if (!isset($this->operation->x['kubernetes-group-version-kind'])) {
            return null;
        }

        return $this->operation->x['kubernetes-group-version-kind']->version;
    }

    public function getKubernetesKind(): ?string
    {
        if (!isset($this->operation->x['kubernetes-group-version-kind'])) {
            return null;
        }

        return $this->operation->x['kubernetes-group-version-kind']->kind;
    }

    public function hasRequiredPathParameters(): bool
    {
        if (empty($this->path->parameters)) {
            return false;
        }

        foreach ($this->path->parameters as $parameter) {
            if ($parameter->required) {
                return true;
            }
        }

        return false;
    }

    public function getRequiredPathParameters(): array
    {
        if (empty($this->path->parameters)) {
            return [];
        }

        $parameters = [];
        foreach ($this->path->parameters as $parameter) {
            if ($parameter->required) {
                $parameters[] = $parameter->name;
            }
        }

        return $parameters;
    }

    public function getKubernetesAction(): ?string
    {
        return $this->operation->x['kubernetes-action'] ?? null;
    }

    public function isWebsocketOperation(): bool
    {
        return $this->getKubernetesAction() === 'connect'
            && !$this->isProxy();
    }

    public function requiresNamespace(): bool
    {
        if (empty($this->path->parameters)) {
            return false;
        }

        foreach ($this->path->parameters as $parameter) {
            if ($parameter->name === 'namespace') {
                return $parameter->required;
            }
        }

        return false;
    }

    public function requiresName(): bool
    {
        if (empty($this->path->parameters)) {
            return false;
        }

        foreach ($this->path->parameters as $parameter) {
            if ($parameter->name === 'name') {
                return $parameter->required;
            }
        }

        return false;
    }

    public function getPhpMethodName(): string
    {
        $operation = $this->operation->operationId;
        $operation = str_ireplace(
            $this->getKubernetesKind(),
            '',
            $operation
        );
        $group = $this->getKubernetesGroup() ?: 'Core';
        $operation = str_ireplace(
            $group . $this->getKubernetesVersion(),
            '',
            $operation
        );

        return str_ireplace(
            array_keys(GoPackageNameFormatter::NAME_REPLACEMENTS),
            array_values(GoPackageNameFormatter::NAME_REPLACEMENTS),
            $operation
        );
    }

    public function getReturnedType(): ?string
    {
        if (in_array($this->getKubernetesAction(), self::VOID_METHODS, true) && !$this->isProxy()) {
            return 'void';
        }
        $success = [];

        foreach ($this->responses as $response) {
            if ($response->isSuccess()) {
                $success[] = $response;
            }
        }

        foreach ($success as $response) {
            if ($response->getDefinition()) {
                return 'model';
            }

            if ($response->isStringResponse()) {
                return 'string';
            }
        }

        return null;
    }

    public function isNullable(): bool
    {
        if ($this->getReturnedType() === 'void') {
            return false;
        }
        $result = array_filter(
            $this->getParameters(),
            static fn (ParameterMetadata $param) => in_array($param->getName(), ['watch', 'follow'], true)
        );

        return count($result) > 0;
    }

    public function getReturnedDefinition(): ?DefinitionMetadata
    {
        $success = [];

        foreach ($this->responses as $response) {
            if ($response->isSuccess() && $response->getDefinition()) {
                $success[] = $response;
            }
        }

        foreach ($success as $response) {
            if ($response->getDefinition()) {
                return $response->getDefinition();
            }
        }

        return null;
    }

    /**
     * @return ParameterMetadata[]
     */
    public function getParameters(): array
    {
        return array_merge(
            array_map(
                static fn (Parameter $parameter) => new ParameterMetadata($parameter),
                $this->operation->parameters ?? []
            ),
            array_map(
                static fn (Parameter $parameter) => new ParameterMetadata($parameter),
                $this->path->parameters ?? []
            )
        );
    }

    public function hasQueryParameters(): bool
    {
        return !empty($this->getQueryParameters());
    }

    public function needsCallableParameter(): bool
    {
        if ($this->isWebsocketOperation()) {
            return true;
        }

        foreach ($this->getQueryParameters() as $parameter) {
            if (in_array($parameter->getName(), ['watch', 'follow'], true)) {
                return true;
            }
        }

        return false;
    }

    public function isWatchable(): bool
    {
        if ($this->getKubernetesAction() !== 'list') {
            return false;
        }

        foreach ($this->getQueryParameters() as $parameter) {
            if ($parameter->getName() === 'watch') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ParameterMetadata[]
     */
    public function getQueryParameters(): array
    {
        return array_filter(
            $this->getParameters(),
            static fn (ParameterMetadata $param) => $param->isQueryParam()
        );
    }

    public function isDeprecated(): bool
    {
        if ((bool)$this->operation->deprecated) {
            return true;
        }
        $definition = $this->getReturnedDefinition();

        if ($definition && $definition->isDeprecated()) {
            return true;
        }

        return stripos($this->getDescription(), self::DEPRECATION_MARKER) !== false;
    }

    public function getDeprecationDescription(): string
    {
        if (!$this->isDeprecated()) {
            return '';
        }

        $definition = $this->getReturnedDefinition();
        if ($definition && $definition->isDeprecated()) {
            $deprecatedPos = stripos($definition->getDescription(), self::DEPRECATION_MARKER);
            if ($deprecatedPos === false) {
                return '';
            }

            return ucfirst((string)substr(
                $definition->getDescription(),
                $deprecatedPos + strlen(self::DEPRECATION_MARKER)
            ));
        }

        $deprecatedPos = stripos($this->getDescription(), self::DEPRECATION_MARKER);
        if ($deprecatedPos === false) {
            return '';
        }

        return ucfirst((string)substr(
            $this->getDescription(),
            $deprecatedPos + strlen(self::DEPRECATION_MARKER)
        ));
    }

    public function isProxyWithPath(): bool
    {
        return str_ends_with($this->getPhpMethodName(), 'ProxyWithPath');
    }

    private function isProxy(): bool
    {
        return str_ends_with($this->getPhpMethodName(), 'Proxy')
            || $this->isProxyWithPath();
    }
}
