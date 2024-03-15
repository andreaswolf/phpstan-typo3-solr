<?php

declare(strict_types=1);

namespace a9f\PhpstanTypo3Solr\Type;

use ApacheSolrForTypo3\Solr\System\Solr\Document\Document;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Changes the return type of {@see Document::getFields()} to an array shape with the relevant values.
 *
 * This uses type information that was added by {@see SolrDocumentTypeResolverExtension}.
 */
class SolrRecordDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Document::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getFields';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $varType = $scope->getType($methodCall->var);

        if ($varType instanceof UnionType) {
            $shouldRefineType = false;
            $unionTypes = [];
            foreach ($varType->getTypes() as $subType) {
                $result = $this->resolveGenericType($subType);

                if ($result === null) {
                    $unionTypes[] = $subType;
                } else {
                    $shouldRefineType = true;
                    $unionTypes[] = $result;
                }
            }

            if ($shouldRefineType) {
                return new UnionType($unionTypes);
            }
            return null;
        }
        return $this->resolveGenericType($varType);
    }

    private function resolveGenericType(Type $varType): ?Type
    {
        if (!$varType instanceof GenericObjectType) {
            return null;
        }

        // the type should be SolrDocument<'record_type', 'field_stringS', 'anotherField_boolS'>
        // => [1] gives us the list of field types
        $types = $varType->getTypes()[1] ?? null;

        if (!$types instanceof UnionType) {
            return null;
        }

        $newTypeBuilder = ConstantArrayTypeBuilder::createEmpty();
        foreach ($types->getTypes() as $field) {
            if (!$field instanceof ConstantStringType) {
                continue;
            }

            $valueType = $this->deriveTypeFromSolrFieldName($field->getValue());
            $newTypeBuilder->setOffsetValueType($field, $valueType);
        }

        return $newTypeBuilder->getArray();
    }

    private function deriveTypeFromSolrFieldName(string $solrFieldName): Type
    {
        if (strpos($solrFieldName, '_') === false) {
            return new MixedType();
        }

        $type = substr($solrFieldName, strrpos($solrFieldName, '_') + 1, -1);
        $isMultiValuedType = $solrFieldName[-1] === 'M';

        switch ($type) {
            case 'int':
                $valueType = new IntegerType();
                break;
            case 'string':
                $valueType = new StringType();
                break;
            case 'bool':
                $valueType = new UnionType([
                    new ConstantStringType('0'),
                    new ConstantStringType('1'),
                ]);
                break;
            default:
                $valueType = new MixedType();
        }

        if (!$isMultiValuedType) {
            return $valueType;
        }

        return new ArrayType(IntegerRangeType::fromInterval(0, null), $valueType);
    }
}
