<?php

namespace Tob\PhpUnitBot\Command;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use SplFileInfo;
use Tob\PhpUnitBot\Config\BotConfig;
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
 * Class CreateFromSource
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
        $sourceFile = $route->getMatchedParam('sourceFile');
        $testDirectory = $route->getMatchedParam('testDirectory');

        $fileInfo = new SplFileInfo($sourceFile);

        $fp = fopen($sourceFile, 'rb');
        $sourceClassName = $sourceNamespace = $buffer = '';
        while (!$sourceClassName) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 512);
            if (preg_match('/class\s+(\w+)?/', $buffer, $matches)) {
                $sourceClassName = $matches[1];
                break;
            }
        }
        while (!$sourceNamespace) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 512);
            if (preg_match('/namespace\s+(.*)?\;/', $buffer, $matches)) {
                $sourceNamespace = $matches[1];
                break;
            }
        }

        $testBasename = $fileInfo->getBasename('.php') . 'Test';
        $testFilePath = $testDirectory . '/' . $testBasename . '.php';
        $testFileDirectory = dirname($testFilePath);

        if (!is_dir($testFileDirectory)) {
            mkdir($testFileDirectory, 0755, true);
        }

        try {
            $classReflection = new ClassReflection($sourceNamespace . "\\" . $sourceClassName);

        } catch (ReflectionException $exception) {
            return;
        }

        if ($classReflection->isInterface()) {
            return;
        }

        if ($classReflection->isAbstract()) {
            return;
        }

        $class = ClassGenerator::fromReflection($classReflection);

        if ($class->getExtendedClass() === \Exception::class) {
            return;
        }

        $testClassProperty = new PropertyGenerator(
            lcfirst($class->getName())
        );
        $testClassProperty->setDefaultValue(null, 'auto', PropertyValueGenerator::OUTPUT_SINGLE_LINE);
        $testClassProperty->setFlags(PropertyGenerator::FLAG_PROTECTED);
        $testClassProperty->setDocBlock(
            DocBlockGenerator::fromArray(
                [
                    'tags' => [
                        [
                            'name'        => 'var',
                            'description' => ucfirst($class->getName()),
                        ],
                    ],
                ]
            )
        );

        $className = $class->getName() . 'Test';
        $classNamespace = $class->getNamespaceName() . 'Test';
        $classGenerator = new ClassGenerator($className, $classNamespace);
        $classGenerator->addUse(TestCase::class);
        $classGenerator->addUse($class->getNamespaceName() . "\\" . $class->getName());
        $classGenerator->setExtendedClass(TestCase::class);
        $classGenerator->addPropertyFromGenerator($testClassProperty);
        $classDocBlock = DocBlockGenerator::fromArray(
            [
                'shortdescription' => 'Class ' . $className,
                'longdescription'  => 'PHP Version 7',
                'tags'             => [
                    [
                        'name'        => 'category',
                        'description' => 'PHP',
                    ],
                    [
                        'name'        => 'package',
                        'description' => $classNamespace,
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
                        'description' => $this->config->getLicence()
                    ],
                ],
            ]
        );

        $classGenerator->setDocBlock($classDocBlock);

        $setUpMethodBody = [];

        $constructorMethod = $classReflection->getConstructor();

        $reflectionParameterProperties = [];
        if ($constructorMethod) {
            $reflectionParameters = $constructorMethod->getParameters();
            foreach ($reflectionParameters as $reflectionParameter) {
                $reflectionClass = $reflectionParameter->getClass();

                if ($reflectionClass) {
                    $classGenerator->addUse($reflectionClass->getName());

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

                    $classGenerator->addPropertyFromGenerator($testClassProperty);

                }
            }
            $setUpMethodBody[] = '';
        }

        if (count($reflectionParameterProperties) > 0) {
            $setUpMethodBody[] = '$this->' . lcfirst($class->getName()) . ' = new ' . $class->getName() . '(';
            $setUpMethodBody[] = '    ' . implode(',', $reflectionParameterProperties);
            $setUpMethodBody[] = ');';
        } else {
            $setUpMethodBody[] = '$this->' . lcfirst($class->getName()) . ' = new ' . $class->getName() . '();';
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
        $classGenerator->addMethodFromGenerator($setUpMethodGenerator);

        $testMethodGenerator = new MethodGenerator(
            'test', [], 'public', '', $docBlock
        );
        $classGenerator->addMethodFromGenerator($testMethodGenerator);

        $fileGenerator = new FileGenerator();
        $fileGenerator->setClass($classGenerator);

        $console->writeLine(@$fileGenerator->generate());
    }
}