<?php

namespace Tests\Solr;

use ApacheSolrForTypo3\Solr\System\Solr\Document\Document;
use a9f\PhpstanTypo3Solr\Type\SolrDocument;
use function PHPStan\Testing\assertType;

// normally, this $document would be returned from a query
/** @var SolrDocument<'fe_users'> $document */
$document = new Document();
$record = $document->getFields();

assertType(
    'a9f\PhpstanTypo3Solr\Type\SolrDocument<\'fe_users\', \'bar_intM\'|\'baz_boolS\'|\'foo_stringS\'>',
    $document
);
assertType('array{bar_intM: array<int<0, max>, int>, baz_boolS: \'0\'|\'1\', foo_stringS: string}', $record);
assertType('string', $record['foo_stringS']);
assertType('array<int<0, max>, int>', $record['bar_intM']);

// normally, this $document would be returned from a query
/** @var SolrDocument<'fe_users'>|null $documentOrNull */
$record = $documentOrNull->getFields();

assertType(
    'a9f\PhpstanTypo3Solr\Type\SolrDocument<\'fe_users\', \'bar_intM\'|\'baz_boolS\'|\'foo_stringS\'>|null',
    $documentOrNull
);
assertType('array{bar_intM: array<int<0, max>, int>, baz_boolS: \'0\'|\'1\', foo_stringS: string}|null', $record);
assertType('string', $record['foo_stringS']);
assertType('array<int<0, max>, int>', $record['bar_intM']);
