<?php

namespace Gskema\TypeSniff\Core\Func;

use Gskema\TypeSniff\Core\DocBlock\DocBlock;
use Gskema\TypeSniff\Core\DocBlock\Tag\VarTag;
use Gskema\TypeSniff\Core\DocBlock\UndefinedDocBlock;
use Gskema\TypeSniff\Core\Type\Common\ArrayType;
use Gskema\TypeSniff\Core\Type\Common\BoolType;
use Gskema\TypeSniff\Core\Type\Common\FalseType;
use Gskema\TypeSniff\Core\Type\Common\FqcnType;
use Gskema\TypeSniff\Core\Type\Common\IntType;
use Gskema\TypeSniff\Core\Type\Common\NullType;
use Gskema\TypeSniff\Core\Type\Common\SelfType;
use Gskema\TypeSniff\Core\Type\Common\StringType;
use Gskema\TypeSniff\Core\Type\Common\UndefinedType;
use Gskema\TypeSniff\Core\Type\Common\UnionType;
use Gskema\TypeSniff\Core\Type\Declaration\NullableType;
use Gskema\TypeSniff\Core\Type\DocBlock\TypedArrayType;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FunctionSignatureParserTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataFromTokens(): array
    {
        $dataSets = [];

        // #0
        $dataSets[] = [
            __DIR__ . '/fixtures/test_func0.php',
            2,
            new FunctionSignature(
                3,
                'func1',
                [],
                new UndefinedType(),
                3,
            ),
            null,
        ];

        // #1
        $dataSets[] = [
            __DIR__ . '/fixtures/test_func0.php',
            13,
            new FunctionSignature(
                5,
                'func2',
                [
                    new FunctionParam(5, 'a', new UndefinedType(), new UndefinedType(), []),
                    new FunctionParam(5, 'b', new IntType(), new UndefinedType(), []),
                    new FunctionParam(5, 'c', new NullableType(new StringType()), new UndefinedType(), []),
                    new FunctionParam(5, 'd', new SelfType(), new UndefinedType(), []),
                ],
                new ArrayType(),
                5,
            ),
            null,
        ];

        // #2
        $dataSets[] = [
            __DIR__ . '/fixtures/test_func0.php',
            48,
            new FunctionSignature(
                9,
                'func3',
                [
                    new FunctionParam(10, 'arg1', new NullableType(new FqcnType('\Space\Class1')), new UndefinedType(), []),
                    new FunctionParam(11, 'arg2', new BoolType(), new UndefinedType(), []),
                    new FunctionParam(12, 'arg3', new UndefinedType(), new BoolType(), []),
                    new FunctionParam(13, 'arg4', new IntType(), new NullType(), []),
                    new FunctionParam(14, 'arg5', new IntType(), null, []),
                ],
                new FqcnType('\Space1\Class2'),
                15,
            ),
            null,
        ];

        // #3
        $dataSets[] = [
            __DIR__ . '/fixtures/test_func0.php',
            118,
            null,
            \RuntimeException::class,
        ];

        // #4
        $dataSets[] = [
            __DIR__ . '/fixtures/test_func1.php',
            2,
            new FunctionSignature(
                3,
                'func1',
                [
                    new FunctionParam(4, 'arg1', new IntType(), null, []),
                    new FunctionParam(5, 'arg2', new StringType(), null, []),
                ],
                new UnionType([
                    new IntType(),
                    new FalseType(),
                ]),
                6,
            ),
            null,
        ];

        // #5
        $dataSets[] = [
            __DIR__ . '/fixtures/test_func1.php',
            46,
            new FunctionSignature(
                11,
                '__construct',
                [
                    new FunctionParam(12, 'arg1', new IntType(), new UndefinedType(), [], new UndefinedDocBlock(), true),
                    new FunctionParam(13, 'arg2', new UndefinedType(), new UndefinedType(), [], new UndefinedDocBlock(), false),
                    new FunctionParam(
                        15,
                        'arg3',
                        new UnionType([new ArrayType(), new IntType(), new FalseType()]),
                        new BoolType(),
                        [],
                        new DocBlock(
                            [],
                            [
                                new VarTag(14, new UnionType([new TypedArrayType(new IntType(), 1), new IntType(), new FalseType()]), null, null)
                            ]
                        ),
                        true
                    ),
                    new FunctionParam(16, 'arg4', new NullableType(new BoolType()), new BoolType(), [], new UndefinedDocBlock(), true),
                    new FunctionParam(17, 'arg5', new UnionType([new NullType(), new IntType(), new StringType()]), new NullType(), [], new UndefinedDocBlock(), true),
                ],
                new UndefinedType(),
                18,
            ),
            null,
        ];

        return $dataSets;
    }

    /**
     * @throws RuntimeException
     */
    #[DataProvider('dataFromTokens')]
    public function testFromTokens(
        string $givenPath,
        int $givenFnPtr,
        ?FunctionSignature $expectedFun,
        ?string $expectedException,
    ): void {
        $givenFile = new LocalFile($givenPath, new Ruleset(new Config()), new Config());
        $givenFile->parse();

        if (null !== $expectedException) {
            self::expectException($expectedException);
        }

        $actualFunc = FunctionSignatureParser::fromTokens(
            $givenFile,
            $givenFnPtr
        );

        self::assertEquals($expectedFun, $actualFunc);
    }
}
