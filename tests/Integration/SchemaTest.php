<?php
declare(strict_types = 1);
/**
 * /tests/Integration/SchemaTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration;

use App\Doctrine\DBAL\Types\EnumLanguageType;
use App\Doctrine\DBAL\Types\EnumLocaleType;
use App\Doctrine\DBAL\Types\EnumLogLoginType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function array_filter;
use function array_values;
use function array_walk;
use function implode;
use function preg_replace;
use function sprintf;
use function str_contains;
use function strtolower;
use function trim;

/**
 * @package App\Tests\Integration
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class SchemaTest extends KernelTestCase
{
    #[TestDox('Test that entity mappings are valid')]
    public function testThatMappingsAreValid(): void
    {
        $errors = $this->getValidator()->validateMapping();

        $messages = [];

        $formatter = static function (array $errors, string $className) use (&$messages): void {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $messages[] = $className . ': ' . implode(', ', $errors);
        };

        array_walk($errors, $formatter);

        self::assertEmpty($errors, implode("\n", $messages));
    }

    #[TestDox('Test that database schema is sync with entity metadata')]
    public function testThatSchemaInSyncWithMetadata(): void
    {
        $validator = $this->getValidator();
        $schemaUpdateSql = $validator->getUpdateSchemaList();

        $schemaUpdateSql = array_values(array_filter(
            $schemaUpdateSql,
            static function (string $sql): bool {
                $normalized = strtolower(trim((string)preg_replace('/\s+/', ' ', $sql), " ;\t\n\r\0\x0B"));

                return !str_contains($normalized, 'drop table migration_versions')
                    && !str_contains($normalized, 'drop table `migration_versions`');
            },
        ));

        self::assertEmpty(
            $schemaUpdateSql,
            sprintf(
                "The database schema is not in sync with the current mapping file.\n%s",
                implode("\n", $schemaUpdateSql),
            ),
        );
    }

    private function getValidator(): SchemaValidator
    {
        self::bootKernel();

        $kernel = self::$kernel;

        if ($kernel === null) {
            throw new RuntimeException('Kernel is not booting.');
        }

        if (!Type::hasType('EnumLanguage')) {
            Type::addType('EnumLanguage', EnumLanguageType::class);
        }

        if (!Type::hasType('EnumLocale')) {
            Type::addType('EnumLocale', EnumLocaleType::class);
        }

        if (!Type::hasType('EnumLogLogin')) {
            Type::addType('EnumLogLogin', EnumLogLoginType::class);
        }

        $managerRegistry = $kernel->getContainer()->get('doctrine');

        /** @psalm-suppress MixedMethodCall */
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $managerRegistry->getManager();

        return new SchemaValidator($entityManager);
    }
}
