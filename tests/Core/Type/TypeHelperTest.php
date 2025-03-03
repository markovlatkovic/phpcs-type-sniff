<?php

namespace Gskema\TypeSniff\Core\Type;

use Gskema\TypeSniff\Core\Type\Common\ArrayType;
use Gskema\TypeSniff\Core\Type\Common\IntType;
use Gskema\TypeSniff\Core\Type\DocBlock\TypedArrayType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TypeHelperTest extends TestCase
{
    /**
     * @return mixed[][]
     */
    public static function dataGetFakeTypedArrayType(): array
    {
        return [
            [null, null],
            [new ArrayType(), null],
            [new TypedArrayType(new IntType(), 1), null],
            [new TypedArrayType(new ArrayType(), 3), new TypedArrayType(new ArrayType(), 3)],
        ];
    }

    #[DataProvider('dataGetFakeTypedArrayType')]
    public function testGetFakeTypedArrayType(?TypeInterface $givenType, ?TypeInterface $expectedFakeType): void
    {
        $actualFakeType = TypeHelper::getFakeTypedArrayType($givenType);

        static::assertEquals($expectedFakeType, $actualFakeType);
    }
}
