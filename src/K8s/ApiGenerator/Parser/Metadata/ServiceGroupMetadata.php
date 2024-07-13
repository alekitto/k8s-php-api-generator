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

use K8s\ApiGenerator\Parser\Formatter\ServiceGroupName;

readonly class ServiceGroupMetadata
{
    public function __construct(
        private ServiceGroupName $group,
        private array $operations,
    ) {
    }

    public function getFqcn(): string
    {
        return $this->makeFinalNamespace($this->group->getFqcn());
    }

    public function getFinalNamespace(): string
    {
        return $this->makeFinalNamespace($this->group->getFullNamespace());
    }

    public function getNamespace(): string
    {
        return $this->group->getFullNamespace();
    }

    public function getClassName(): string
    {
        return $this->group->getClassName();
    }

    public function getKind(): string
    {
        return $this->group->getKind();
    }

    public function getVersion(): string
    {
        return $this->group->getVersion();
    }

    public function getGroup(): ?string
    {
        return $this->group->getGroupName();
    }

    /**
     * @return OperationMetadata[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function getDescription(): string
    {
        foreach ($this->operations as $operation) {
            if ($operation->getPhpMethodName() === 'read' && $operation->getReturnedDefinition()) {
                return $operation->getReturnedDefinition()->getDescription();
            }
        }

        return '';
    }

    public function getModelDefinition(): ?DefinitionMetadata
    {
        $operation = $this->getCreateOperation();
        if (!$operation) {
            return null;
        }

        return $operation->getReturnedDefinition();
    }

    public function getCreateOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'post') {
                continue;
            }
            if (str_starts_with($operation->getPhpMethodName(), 'create')) {
                return $operation;
            }
        }

        return null;
    }

    public function getDeleteOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'delete') {
                continue;
            }
            if (str_starts_with($operation->getPhpMethodName(), 'delete')) {
                return $operation;
            }
        }

        return null;
    }

    public function getDeleteCollectionOperation(bool $namespaced = true): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'deletecollection') {
                continue;
            }
            if ($namespaced && $operation->requiresNamespace()) {
                return $operation;
            }

            if (!$namespaced && !$operation->requiresNamespace()) {
                return $operation;
            }
        }

        return null;
    }

    public function getWatchOperation(bool $namespaced = true): ?OperationMetadata
    {
        $operation = $this->getListOperation($namespaced);

        return ($operation && $operation->isWatchable()) ? $operation : null;
    }

    public function getListOperation(bool $namespaced = true): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if ($operation->getKubernetesAction() !== 'list') {
                continue;
            }
            $operationIsNamespaced = str_contains($operation->getUriPath(), "/{namespace}/");
            if ($operationIsNamespaced && $namespaced) {
                return $operation;
            }

            if (!$operationIsNamespaced && !$namespaced) {
                return $operation;
            }
        }

        return null;
    }

    public function getReadOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if (str_ends_with($operation->getUriPath(), '/status')) {
                continue;
            }
            if ($operation->getKubernetesAction() === 'get') {
                return $operation;
            }
        }

        return null;
    }

    public function getReadStatusOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if (!str_ends_with($operation->getUriPath(), '/status')) {
                continue;
            }
            if ($operation->getKubernetesAction() === 'get') {
                return $operation;
            }
        }

        return null;
    }

    public function getPatchOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if (str_ends_with($operation->getUriPath(), '/status')) {
                continue;
            }
            if ($operation->getKubernetesAction() === 'patch') {
                return $operation;
            }
        }

        return null;
    }

    public function getPatchStatusOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if (!str_ends_with($operation->getUriPath(), '/status')) {
                continue;
            }
            if ($operation->getKubernetesAction() === 'patch') {
                return $operation;
            }
        }

        return null;
    }

    public function getPutOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if (str_ends_with($operation->getUriPath(), '/status')) {
                continue;
            }
            if ($operation->getKubernetesAction() === 'put') {
                return $operation;
            }
        }

        return null;
    }

    public function getPutStatusOperation(): ?OperationMetadata
    {
        foreach ($this->operations as $operation) {
            if (!str_ends_with($operation->getUriPath(), '/status')) {
                continue;
            }
            if ($operation->getKubernetesAction() === 'put') {
                return $operation;
            }
        }

        return null;
    }

    private function makeFinalNamespace(string $namespace): string
    {
        return sprintf(
            'Service\\%s',
            $namespace
        );
    }
}
