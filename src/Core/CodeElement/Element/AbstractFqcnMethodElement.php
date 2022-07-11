<?php

namespace Gskema\TypeSniff\Core\CodeElement\Element;

use Gskema\TypeSniff\Core\CodeElement\Element\Metadata\ClassMethodMetadata;
use Gskema\TypeSniff\Core\CodeElement\Element\Metadata\InterfaceMethodMetadata;
use Gskema\TypeSniff\Core\CodeElement\Element\Metadata\TraitMethodMetadata;
use Gskema\TypeSniff\Core\DocBlock\DocBlock;
use Gskema\TypeSniff\Core\Func\FunctionSignature;

abstract class AbstractFqcnMethodElement extends AbstractFqcnElement
{
    /**
     * @param string[] $attributeNames
     */
    public function __construct(
        DocBlock $docBlock,
        string $fqcn,
        protected FunctionSignature $signature,
        array $attributeNames,
    ) {
        parent::__construct($signature->getLine(), $docBlock, $fqcn, $attributeNames);
    }

    public function getSignature(): FunctionSignature
    {
        return $this->signature;
    }

    public function getId(): string
    {
        return sprintf('%s::%s()', $this->fqcn, $this->signature->getName());
    }

    /**
     * @return ClassMethodMetadata|TraitMethodMetadata|InterfaceMethodMetadata
     */
    abstract public function getMetadata();
}
