<?php

declare(strict_types=1);

namespace a9f\PhpstanTypo3Solr\Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(\a9f\PhpstanTypo3Solr\Type\SolrDocumentTypeResolverExtension::class)]
#[CoversClass(\a9f\PhpstanTypo3Solr\Type\SolrRecordDynamicReturnTypeExtension::class)]
class SolrRecordTypeTest extends TypeInferenceTestCase
{
    /**
     * @return array<string, mixed[]>
     */
    public static function dataAsserts(): array
    {
        return self::gatherAssertTypes(__DIR__ . '/data/solr-record-type.php');
    }

    /**
     * @param mixed ...$args
     */
    #[Test]
    #[DataProvider(methodName: 'dataAsserts')]
    public function testAsserts(string $assertType, string $file, ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    /**
     * @return list<non-empty-string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/solr-record-type-extension.neon',
        ];
    }
}
