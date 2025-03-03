<?php

namespace Gskema\TypeSniff\Core\DocBlock;

use Gskema\TypeSniff\Core\DocBlock\Tag\GenericTag;
use Gskema\TypeSniff\Core\DocBlock\Tag\ParamTag;
use Gskema\TypeSniff\Core\DocBlock\Tag\ReturnTag;
use Gskema\TypeSniff\Core\DocBlock\Tag\VarTag;
use Gskema\TypeSniff\Core\Type\Common\ArrayType;
use Gskema\TypeSniff\Core\Type\Common\IntType;
use Gskema\TypeSniff\Core\Type\Common\MixedType;
use Gskema\TypeSniff\Core\Type\Common\NullType;
use Gskema\TypeSniff\Core\Type\Common\StringType;
use Gskema\TypeSniff\Core\Type\Common\UnionType;
use Gskema\TypeSniff\Core\Type\DocBlock\TypedArrayType;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DocBlockParserTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataDetectFromTokens(): array
    {
        $dataSets = [];

        // #0
        $dataSets[] = [
            'givenPath'         => __DIR__ . '/fixtures/TestDocBlock.php',
            'givenPointers'     => [2, 55],
            'expectedDocBlock'  => new DocBlock(
                [
                    4 => 'FuncDesc',
                    5 => 'oops',
                    6 => '',
                    7 => ' MultiLine',
                ],
                [
                    new ParamTag(10, new IntType(), 'param1', 'ParamDesc SecondLine'),
                    new GenericTag(12, 'inheritdoc', null),
                    new ReturnTag(14, new ArrayType(), null),
                ],
            ),
            'expectedException' => null,
        ];

        // #1
        $dataSets[] = [
            'givenPath'         => __DIR__ . '/fixtures/TestDocBlock.php',
            'givenPointers'     => [58, 63],
            'expectedDocBlock'  => new DocBlock(
                [],
                [
                    new VarTag(17, new IntType(), null, 'SomeDesc'),
                ],
            ),
            'expectedException' => null,
        ];

        // #2
        $dataSets[] = [
            'givenPath'         => __DIR__ . '/fixtures/TestDocBlock.php',
            'givenPointers'     => [66, 71],
            'expectedDocBlock'  => new DocBlock(
                [],
                [
                    new VarTag(
                        19,
                        new TypedArrayType(new StringType(), 1),
                        'inlineVar',
                        'Desc1',
                    ),
                ],
            ),
            'expectedException' => null,
        ];

        // #3
        $dataSets[] = [
            'givenPath'         => __DIR__ . '/fixtures/TestDocBlock.php',
            'givenPointers'     => [75, 158],
            'expectedDocBlock'  => new DocBlock(
                [
                    23 => 'FuncDesc',
                    24 => 'oops wtf',
                    25 => '',
                    26 => 'array(',
                    27 => '  example',
                    28 => ')',
                    29 => ' MultiLine MultiLine',
                ],
                [
                    new ParamTag(32, new IntType(), 'param1', 'ParamDesc SecondLine'),
                    new ParamTag(
                        34,
                        new UnionType([new TypedArrayType(new StringType(), 1), new IntType()]),
                        'param2',
                        null,
                    ),
                    new GenericTag(35, 'deprecated', null),
                    new GenericTag(36, 'inheritdoc', null),
                    new ReturnTag(38, new TypedArrayType(new StringType(), 1), null),
                ]
            ),
            'expectedException' => null,
        ];

        return $dataSets;
    }

    /**
     * @param int[]         $givenPointers
     * @throws RuntimeException
     */
    #[DataProvider('dataDetectFromTokens')]
    public function testDetectFromTokens(
        string $givenPath,
        array $givenPointers,
        ?DocBlock $expectedDocBlock,
        ?string $expectedException,
    ): void {
        $givenFile = new LocalFile($givenPath, new Ruleset(new Config()), new Config());
        $givenFile->parse();

        if (null !== $expectedException) {
            self::expectException($expectedException);
        }

        $actual = DocBlockParser::fromTokens($givenFile, $givenPointers[0], $givenPointers[1]);

        self::assertEquals($expectedDocBlock, $actual);
    }

    /**
     * @return mixed[][]
     */
    public static function dataFromRaw(): array
    {
        $dataSets = [];

        // #0
        $dataSets[] = [
            'givenRawDocBlock'  => '/** @ test */',
            'givenStartLine'    => 1,
            'expectedDocBlock'  => null,
            'expectedException' => \RuntimeException::class,
        ];

        // #1
        $dataSets[] = [
            'givenRawDocBlock'  => '/** @SmartTemplate() */',
            'givenStartLine'    => 2,
            'expectedDocBlock'  => new DocBlock(
                [],
                [new GenericTag(2, 'smarttemplate', '()')],
            ),
            'expectedException' => null,
        ];

        // #2 Var tag with multiple preceding spaces
        $dataSets[] = [
            'givenRawDocBlock'  => '/**  @var int */',
            'givenStartLine'    => 2,
            'expectedDocBlock'  => new DocBlock(
                [],
                [new VarTag(2, new IntType(), null, null)],
            ),
            'expectedException' => null,
        ];

        // #3 Dynamic tags, some with dashes
        $dataSets[] = [
            'givenRawDocBlock'  => '/**
 * @Route("/{id}", name="blog_post", requirements = {"id" = "\d+"})
 * @Param-Converter("user", class="AcmeBlogBundle:User", options={
 *    "repository_method" = "findByFullName"
 * })
 */',
            'givenStartLine'    => 2,
            'expectedDocBlock'  => new DocBlock(
                [],
                [
                    new GenericTag(3, 'route', '("/{id}", name="blog_post", requirements = {"id" = "\d+"})'),
                    new GenericTag(4, 'param-converter', '("user", class="AcmeBlogBundle:User", options={ "repository_method" = "findByFullName" })'),
                ],
            ),
            'expectedException' => null,
        ];

        // #4 Return tags with custom array type
        $dataSets[] = [
            'givenRawDocBlock'  => '/**
* @return array<int, array<string|null, int>>|null Description text
* @return array|null Description option dsa asd
* @return int
*/',
            'givenStartLine'    => 2,
            'expectedDocBlock'  => new DocBlock(
                [],
                [
                    new ReturnTag(3, new UnionType([
                        new TypedArrayType(new MixedType(), 1),
                        new NullType(),
                    ]), 'Description text'),
                    new ReturnTag(4, new UnionType([
                        new ArrayType(),
                        new NullType(),
                    ]), 'Description option dsa asd'),
                    new ReturnTag(5, new IntType(), null),
                ],
            ),
            'expectedException' => null,
        ];

        // #5 Var tags with custom array type
        $dataSets[] = [
            'givenRawDocBlock'  => '/**
* @var array<int, array<string|null, int>>|null Description text
* @var array|null Description option dsa asd
* @var int
*/',
            'givenStartLine'    => 2,
            'expectedDocBlock'  => new DocBlock(
                [],
                [
                    new VarTag(3, new UnionType([
                        new TypedArrayType(new MixedType(), 1),
                        new NullType(),
                    ]), null, 'Description text'),
                    new VarTag(4, new UnionType([
                        new ArrayType(),
                        new NullType(),
                    ]), null, 'Description option dsa asd'),
                    new VarTag(5, new IntType(), null, null),
                ],
            ),
            'expectedException' => null,
        ];

        return $dataSets;
    }

    #[DataProvider('dataFromRaw')]
    public function testFromRaw(
        string $givenRawDocBlock,
        int $givenStartLine,
        ?DocBlock $expectedDocBlock,
        ?string $expectedException,
    ): void {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        $actualDocBlock = DocBlockParser::fromRaw($givenRawDocBlock, $givenStartLine);

        self::assertEquals($expectedDocBlock, $actualDocBlock);
    }
}
