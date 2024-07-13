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

use K8s\ApiGenerator\Code\CodeGenerator\ModelCodeGenerator;
use K8s\ApiGenerator\Code\CodeGenerator\ServiceCodeGenerator;
use K8s\ApiGenerator\Code\CodeGenerator\ServiceFactoryCodeGenerator;
use K8s\ApiGenerator\Code\Writer\PhpFileWriter;
use K8s\ApiGenerator\Parser\Metadata\Metadata;

readonly class CodeGenerator
{
    public function __construct(
        private PhpFileWriter $phpFileWriter = new PhpFileWriter(),
        private ServiceCodeGenerator $serviceCodeGenerator = new ServiceCodeGenerator(),
        private ModelCodeGenerator $modelCodeGenerator = new ModelCodeGenerator(),
        private ServiceFactoryCodeGenerator $serviceFactoryCodeGenerator = new ServiceFactoryCodeGenerator(),
    ) {
    }

    public function generateCode(Metadata $metadata, CodeOptions $options): void
    {
        foreach ($metadata->getDefinitions() as $model) {
            if ($model->isValidModel()) {
                $codeFile = $this->modelCodeGenerator->generate($model, $metadata, $options);
                $this->phpFileWriter->write(
                    $codeFile,
                    $options
                );
            }
        }

        foreach ($metadata->getServiceGroups() as $serviceGroup) {
            $codeFile = $this->serviceCodeGenerator->generate(
                $serviceGroup,
                $metadata,
                $options
            );
            $this->phpFileWriter->write(
                $codeFile,
                $options
            );
        }

        $codeFile = $this->serviceFactoryCodeGenerator->generate(
            $metadata,
            $options
        );
        $this->phpFileWriter->write(
            $codeFile,
            $options
        );
    }
}
