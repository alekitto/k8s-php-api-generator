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

namespace K8s\ApiGenerator\Code\Writer;

use K8s\ApiGenerator\Code\CodeFile;
use K8s\ApiGenerator\Code\CodeOptions;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Symfony\Component\Filesystem\Filesystem;

readonly class PhpFileWriter
{
    private const LICENSE_BLOCK = <<<LICNESE
        This file was automatically generated by k8s/api-generator {generator-version} for API version {api-version}
        
        For the full copyright and license information, please view the LICENSE
        file that was distributed with this source code.
        LICNESE;

    public function __construct(private Filesystem $fileSystem = new Filesystem(), private PsrPrinter $psrPrinter = new PsrPrinter())
    {
    }

    public function write(CodeFile $codeFile, CodeOptions $options): string
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $file->addComment(str_replace(
            ['{api-version}', '{generator-version}'],
            [$options->getApiVersion(), $options->getGeneratorVersion()],
            self::LICENSE_BLOCK
        ));
        $file->addNamespace($codeFile->getPhpNamespace());

        $filename = $options->getSrcDir() . DIRECTORY_SEPARATOR . $codeFile->getFullFileName();
        $this->fileSystem->dumpFile(
            $filename,
            $this->psrPrinter->printFile($file)
        );

        return $filename;
    }
}
