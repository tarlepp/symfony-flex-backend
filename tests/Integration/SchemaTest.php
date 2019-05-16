<?php
declare(strict_types = 1);
/**
 * /tests/Integration/SchemaTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class SchemaTest
 *
 * @package App\Tests\Integration
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SchemaTest extends KernelTestCase
{
    /**
     * @var SchemaValidator
     */
    private $validator;

    public function testThatMappingsAreValid(): void
    {
        $errors = $this->validator->validateMapping();

        $messages = [];

        $formatter = function ($errors, $className) use (&$messages) {
            $messages[] = $className . ': ' . \implode(', ', $errors);
        };

        \array_walk($errors, $formatter);

        static::assertEmpty($errors, \implode("\n", $messages));

        unset($errors, $messages);
    }

    public function testThatSchemaInSyncWithMetadata(): void
    {
        static::assertTrue(
            $this->validator->schemaInSyncWithMetadata(),
            'The database schema is not in sync with the current mapping file.'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        gc_enable();

        static::bootKernel();

        if (!Type::hasType('EnumLogLogin')) {
            Type::addType('EnumLogLogin', EnumLogLoginType::class);
        }

        /** @var EntityManagerInterface $em */
        $em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->validator = new SchemaValidator($em);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->validator);

        gc_collect_cycles();
    }
}
