<?php

namespace Tob\PhpUnitBot\Command;

use PHPUnit\Framework\TestCase;
use Tob\PhpUnitBot\Config\BotConfig;
use Tob\PhpUnitBot\Io\SourceFile;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\PropertyValueGenerator;
use Zend\Code\Reflection\ClassReflection;
use Zend\Console\Adapter\AdapterInterface;
use ZF\Console\Route;

/**
 * Class CreateFromSourceCommand
 *
 * PHP Version 7
 *
 * @category  PHP
 * @package   Tob\PhpUnitBot\Command
 * @author    Simplicity Trade GmbH <development@simplicity.ag>
 * @copyright 2014-2017 Simplicity Trade GmbH
 * @license   Proprietary http://www.simplicity.ag
 */
class CreateFromSourceCommand
{
    /** @var BotConfig */
    protected $config;

    /**
     * CreateFromSourceCommand constructor.
     *
     * @param BotConfig $config
     */
    public function __construct(BotConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param Route            $route
     * @param AdapterInterface $console
     *
     * @return void
     * @throws \Zend\Code\Generator\Exception\InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __invoke(Route $route, AdapterInterface $console)
    {
        $sourceFilePath = $route->getMatchedParam('sourceFile');
        $sourceFile = new SourceFile($sourceFilePath);

        $classReflection = new ClassReflection($sourceFile->getFullClassName());
        $classGenerator = ClassGenerator::fromReflection($classReflection);

        $testClassProperty = new PropertyGenerator(lcfirst($classGenerator->getName()));
        $testClassProperty->setDefaultValue(null, 'auto', PropertyValueGenerator::OUTPUT_SINGLE_LINE);
        $testClassProperty->setFlags(PropertyGenerator::FLAG_PROTECTED);
        $testClassPropertyDocBlock = DocBlockGenerator::fromArray(
            [
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => ucfirst($classGenerator->getName()),
                    ],
                ],
            ]
        );
        $testClassProperty->setDocBlock($testClassPropertyDocBlock);

        $testClassName = $classGenerator->getName() . 'Test';
        $testClassNamespace = $classGenerator->getNamespaceName() . 'Test';
        $testClassGenerator = new ClassGenerator($testClassName, $testClassNamespace);
        $testClassGenerator->addUse(TestCase::class);
        $testClassGenerator->addUse($classGenerator->getNamespaceName() . "\\" . $classGenerator->getName());
        $testClassGenerator->setExtendedClass(TestCase::class);
        $testClassGenerator->addPropertyFromGenerator($testClassProperty);
        $testClassDocBlock = DocBlockGenerator::fromArray(
            [
                'shortdescription' => 'Class ' . $testClassName,
                'longdescription'  => 'PHP Version 7',
                'tags'             => [
                    [
                        'name'        => 'category',
                        'description' => 'PHP',
                    ],
                    [
                        'name'        => 'package',
                        'description' => $testClassNamespace,
                    ],
                    [
                        'name'        => 'author',
                        'description' => $this->config->getAuthor(),
                    ],
                    [
                        'name'        => 'copyright',
                        'description' => $this->config->getCopyright(),
                    ],
                    [
                        'name'        => 'license',
                        'description' => $this->config->getLicence(),
                    ],
                ],
            ]
        );
        $testClassGenerator->setDocBlock($testClassDocBlock);



        $setUpMethodBody = [];

        $constructorMethod = $classReflection->getConstructor();

        $reflectionParameterProperties = [];
        if ($constructorMethod) {
            $reflectionParameters = $constructorMethod->getParameters();
            foreach ($reflectionParameters as $reflectionParameter) {
                $reflectionClass = $reflectionParameter->getClass();

                if ($reflectionClass) {
                    $testClassGenerator->addUse($reflectionClass->getName());

                    $reflectionParameterProperty = '$this->' . lcfirst($reflectionParameter->getName());
                    $setUpMethodBody[] = $reflectionParameterProperty . ' = $this->createMock(' . $reflectionClass->getShortName(
                        ) . '::class);';
                    $reflectionParameterProperties[] = $reflectionParameterProperty;

                    $testClassProperty = new PropertyGenerator(
                        lcfirst($reflectionParameter->getName()), null, PropertyGenerator::FLAG_PROTECTED
                    );
                    $testClassProperty->setDocBlock(
                        DocBlockGenerator::fromArray(
                            [
                                'tags' => [
                                    [
                                        'name'        => 'var',
                                        'description' => ucfirst(
                                                $reflectionClass->getShortName()
                                            ) . "|\PHPUnit_Framework_MockObject_MockObject",
                                    ],
                                ],
                            ]
                        )
                    );

                    $testClassGenerator->addPropertyFromGenerator($testClassProperty);

                }
            }
            $setUpMethodBody[] = '';
        }

        if (\count($reflectionParameterProperties) > 0) {
            $setUpMethodBody[] = '$this->' . lcfirst($classGenerator->getName()) . ' = new ' . $classGenerator->getName() . '(';
            $setUpMethodBody[] = '    ' . implode(',', $reflectionParameterProperties);
            $setUpMethodBody[] = ');';
        } else {
            $setUpMethodBody[] = '$this->' . lcfirst($classGenerator->getName()) . ' = new ' . $classGenerator->getName() . '();';
        }

        $docBlock = DocBlockGenerator::fromArray(
            [
                'tags' => [
                    [
                        'name'        => 'return',
                        'description' => 'void',
                    ],
                ],
            ]
        );
        $setUpMethodGenerator = new MethodGenerator(
            'setUp', [], 'public', implode("\n", $setUpMethodBody), $docBlock
        );
        $testClassGenerator->addMethodFromGenerator($setUpMethodGenerator);

        $testMethodGenerator = new MethodGenerator(
            'test', [], 'public', '', $docBlock
        );
        $testClassGenerator->addMethodFromGenerator($testMethodGenerator);

        $fileGenerator = new FileGenerator();
        $fileGenerator->setClass($testClassGenerator);

        $console->writeLine(@$fileGenerator->generate());
    }
}