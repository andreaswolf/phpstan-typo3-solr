<?php

namespace a9f\PhpstanTypo3Solr\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Adds type information on the fields of a Solr document based on the record type specified via PhpDoc.
 *
 * The type is read from a type specification like
 *
 * ```
 * /** @var SolrDocument<'some_solr_record_type'> $myVariable …
 * ```
 *
 * and the fields are resolved from the conf parameter "solr.recordTypes.[record-type]".
 *
 * The fields are appended to the generic type as a single union type, i.e. the type will look like
 * `SolrDocument<'some_solr_record_type', 'fieldA_stringS' | 'fieldB_boolM'>` afterwards
 */
class SolrDocumentTypeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{
    private TypeNodeResolver $typeNodeResolver;

    /**
     * @var array<string, list<string>>
     */
    private array $solrRecordTypes;

    /**
     * @param array<string, list<string>> $solrRecordTypes The record types defined for Solr
     */
    public function __construct(array $solrRecordTypes)
    {
        $this->solrRecordTypes = $solrRecordTypes;
    }

    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof UnionTypeNode) {
            $shouldRefineType = false;
            $unionTypes = [];
            foreach ($typeNode->types as $subTypeNode) {
                $result = $this->resolveGenericNodeType($subTypeNode, $nameScope);

                if ($result === null) {
                    $unionTypes[] = $this->typeNodeResolver->resolve($subTypeNode, $nameScope);
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
        return $this->resolveGenericNodeType($typeNode, $nameScope);
    }

    private function resolveGenericNodeType(TypeNode $typeNode, NameScope $nameScope): ?GenericObjectType
    {
        if (!$typeNode instanceof GenericTypeNode) {
            return null;
        }

        $typeName = $typeNode->type;
        $resolvedTypeName = $nameScope->resolveStringName($typeName->name);
        if ($resolvedTypeName !== SolrDocument::class) {
            return null;
        }

        $arguments = $typeNode->genericTypes;
        if (count($arguments) !== 1) {
            return null;
        }

        $recordType = $this->typeNodeResolver->resolve($arguments[0], $nameScope);

        if (!$recordType instanceof ConstantStringType) {
            return null;
        }

        $recordTypeName = $recordType->getValue();
        $recordTypeFields = $this->solrRecordTypes[$recordTypeName] ?? null;

        if ($recordTypeFields === null) {
            throw new \RuntimeException(sprintf('Solr record type "%s" is not configured', $recordTypeName), 1710486027);
        }

        $recordTypeFieldTypes = new UnionType(
            array_map(
                static fn (string $fieldName) => new ConstantStringType($fieldName),
                $recordTypeFields
            )
        );

        return new GenericObjectType(SolrDocument::class, [
            $recordType,
            $recordTypeFieldTypes
        ]);
    }
}
