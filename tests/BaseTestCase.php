<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ObjectManager;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use StichtingSD\SoftDeleteableExtensionBundle\EventListener\OnSoftDeleteEventSubscriber;
use StichtingSD\SoftDeleteableExtensionBundle\EventListener\OnSoftDeleteValidatorEventSubscriber;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\MetadataFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

abstract class BaseTestCase extends TestCase
{
    private ?ObjectManager $objectManager = null;

    /**
     * @var MockObject&LoggerInterface
     */
    protected $queryLogger;

    protected function setUp(): void
    {
        $this->queryLogger = $this->createMock(LoggerInterface::class);
    }

    protected function getObjectManager(array $entities, bool $forceRecreate = false, array $entityPaths = [__DIR__]): EntityManager
    {
        if (null !== $this->objectManager && !$forceRecreate) {
            return $this->objectManager;
        }

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $arrayAdapter = new ArrayAdapter();
        $metaDataFactory = new MetadataFactory($arrayAdapter);
        $evm = new EventManager();
        $evm->addEventListener(Events::loadClassMetadata, new OnSoftDeleteValidatorEventSubscriber($metaDataFactory));
        $evm->addEventListener(SoftDeleteableListener::PRE_SOFT_DELETE, new OnSoftDeleteEventSubscriber($metaDataFactory));

        // Enable Gedmo event listener and filter.
        $config = $this->getDefaultConfiguration(md5(json_encode($entityPaths)));
        $config->setMetadataDriverImpl(new AttributeDriver($entityPaths));
        $config->addFilter('softdeleteable', SoftDeleteableFilter::class);
        $evm->addEventSubscriber(new SoftDeleteableListener());

        $connection = DriverManager::getConnection($conn, $config);
        $entityManager = new EntityManager($connection, $config, $evm);
        $entityManager->getFilters()->enable('softdeleteable');

        $schema = array_map(static fn ($class) => $entityManager->getClassMetadata($class), $entities);

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->objectManager = $entityManager;
    }

    protected function getDefaultConfiguration(string $proxyDirHash): Configuration
    {
        $config = new Configuration();
        $config->setProxyDir(__DIR__.'/var/cache/doctrine/'.$proxyDirHash.'/');
        $config->setProxyNamespace('Proxy');
        $config->setMiddlewares([
            new Middleware($this->queryLogger),
        ]);

        return $config;
    }
}
