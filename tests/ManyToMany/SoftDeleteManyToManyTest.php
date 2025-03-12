<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany;

use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteAssociationTypeNotSupportedException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteManyToManyNotOnMappedSideException;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseTestCase;

/**
 * @internal
 */
final class SoftDeleteManyToManyTest extends BaseTestCase
{
    public function testManyToManyWithCascadeThrowsExceptionUnsupportedType(): void
    {
        self::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        self::expectExceptionMessage('Expected Type::REMOVE_ASSOCIATION_ONLY for ManyToMany association given CASCADE. In StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\Cascade\MappedSide\InversedEntity->parents.');
        $this->getObjectManager([
            Cascade\MappedSide\InversedEntity::class,
            Cascade\MappedSide\MappedEntity::class,
        ], true, [__DIR__.'/Cascade/MappedSide/']);
    }

    public function testManyToManyWithSetNullThrowsExceptionUnsupportedType(): void
    {
        self::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        self::expectExceptionMessage('Expected Type::REMOVE_ASSOCIATION_ONLY for ManyToMany association given SET_NULL. In StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\SetNull\MappedSide\InversedEntity->parents.');
        $this->getObjectManager([
            SetNull\MappedSide\InversedEntity::class,
            SetNull\MappedSide\MappedEntity::class,
        ], true, [__DIR__.'/SetNull/MappedSide/']);
    }

    public function testAttributeOnInversedSideAndInversedSideEntityIsSoftDeletedOnlyTheAssociationIsRemoved(): void
    {
        self::expectException(SoftDeleteManyToManyNotOnMappedSideException::class);
        self::expectExceptionMessage('StichtingSD\onSoftDelete() should be defined on the mapped/owning side of the ManyToMany relation. In StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\RemoveAssociationOnly\AttributeDefinedOnInversedSide\InversedEntity->parents.');
        // Construct
        $mappedEntityClass = RemoveAssociationOnly\AttributeDefinedOnInversedSide\MappedEntity::class;
        $inversedEntityClass = RemoveAssociationOnly\AttributeDefinedOnInversedSide\InversedEntity::class;
        $objectManager = $this->getObjectManager([
            $mappedEntityClass,
            $inversedEntityClass,
        ], true, [__DIR__.'/RemoveAssociationOnly/AttributeDefinedOnInversedSide/']);

        // Tests keep failing. Too many hacky code and since there is an owning side the user should set it on the owning side.
        $mappedEntityRepository = $objectManager->getRepository($mappedEntityClass);
        $inversedEntityRepository = $objectManager->getRepository($inversedEntityClass);

        // Fixture
        $inversedEntity1 = new $inversedEntityClass();
        $inversedEntity2 = new $inversedEntityClass();
        $mappedEntity = new $mappedEntityClass();
        $mappedEntity->addChild($inversedEntity1);
        $mappedEntity->addChild($inversedEntity2);
        $objectManager->persist($inversedEntity1);
        $objectManager->persist($inversedEntity2);
        $objectManager->persist($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        self::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Remove
        self::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($inversedEntity1);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        self::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Check
        self::assertNotNull($inversedEntity1->getDeletedAt());
        self::assertNull($mappedEntity->getDeletedAt());
        self::assertNull($inversedEntity2->getDeletedAt());
        self::assertCount(0, $inversedEntity1->getParents());
        self::assertCount(1, $mappedEntity->getChildren());
        self::assertNotContains($inversedEntity1, $mappedEntity->getChildren());
    }

    public function testAttributeOnInversedSideAndMappedSideEntityIsSoftDeletedOnlyTheAssociationIsRemoved(): void
    {
        self::expectException(SoftDeleteManyToManyNotOnMappedSideException::class);
        self::expectExceptionMessage('StichtingSD\onSoftDelete() should be defined on the mapped/owning side of the ManyToMany relation. In StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\RemoveAssociationOnly\AttributeDefinedOnInversedSide\InversedEntity->parents.');
        // Construct
        $mappedEntityClass = RemoveAssociationOnly\AttributeDefinedOnInversedSide\MappedEntity::class;
        $inversedEntityClass = RemoveAssociationOnly\AttributeDefinedOnInversedSide\InversedEntity::class;
        $objectManager = $this->getObjectManager([
            $mappedEntityClass,
            $inversedEntityClass,
        ], true, [__DIR__.'/RemoveAssociationOnly/AttributeDefinedOnInversedSide/']);

        $mappedEntityRepository = $objectManager->getRepository($mappedEntityClass);

        // Fixture
        $inversedEntity1 = new $inversedEntityClass();
        $inversedEntity2 = new $inversedEntityClass();
        $mappedEntity = new $mappedEntityClass();
        $mappedEntity->addChild($inversedEntity1);
        $mappedEntity->addChild($inversedEntity2);
        $objectManager->persist($inversedEntity1);
        $objectManager->persist($inversedEntity2);
        $objectManager->persist($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Remove the parent
        self::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Check
        self::assertNotNull($mappedEntity->getDeletedAt());
        self::assertCount(0, $mappedEntity->getChildren());
        self::assertNull($inversedEntity1->getDeletedAt());
        self::assertNotContains($mappedEntity, $inversedEntity1->getParents());
        self::assertNull($inversedEntity2->getDeletedAt());
        self::assertNotContains($mappedEntity, $inversedEntity2->getParents());
    }

    public function testAttributeOnMappedSideAndMappedSideEntityIsSoftDeletedTheAssociationIsNotRemoved(): void
    {
        // Construct
        $mappedEntityClass = RemoveAssociationOnly\AttributeDefinedOnMappedSide\MappedEntity::class;
        $inversedEntityClass = RemoveAssociationOnly\AttributeDefinedOnMappedSide\InversedEntity::class;
        $objectManager = $this->getObjectManager([
            $mappedEntityClass,
            $inversedEntityClass,
        ], true, [__DIR__.'/RemoveAssociationOnly/AttributeDefinedOnMappedSide/']);

        $mappedEntityRepository = $objectManager->getRepository($mappedEntityClass);

        // Fixture
        $inversedEntity1 = new $inversedEntityClass();
        $inversedEntity2 = new $inversedEntityClass();
        $mappedEntity = new $mappedEntityClass();
        $mappedEntity->addChild($inversedEntity1);
        $mappedEntity->addChild($inversedEntity2);
        $objectManager->persist($inversedEntity1);
        $objectManager->persist($inversedEntity2);
        $objectManager->persist($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Remove
        self::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Check
        self::assertNotNull($mappedEntity->getDeletedAt());
        self::assertCount(2, $mappedEntity->getChildren());
        self::assertNull($inversedEntity1->getDeletedAt());
        $matched = $inversedEntity1->getParents()->findFirst(
            static fn ($key, $parent) => $parent->getId() === $mappedEntity->getId()
        );
        self::assertNotNull($matched);
        self::assertNull($inversedEntity2->getDeletedAt());
        $matched = $inversedEntity2->getParents()->findFirst(
            static fn ($key, $parent) => $parent->getId() === $mappedEntity->getId()
        );
        self::assertNotNull($matched);
    }

    public function testAttributeOnMappedSideAndInversedSideEntityIsSoftDeletedOnlyTheAssociationIsRemoved(): void
    {
        // Construct
        $mappedEntityClass = RemoveAssociationOnly\AttributeDefinedOnMappedSide\MappedEntity::class;
        $inversedEntityClass = RemoveAssociationOnly\AttributeDefinedOnMappedSide\InversedEntity::class;
        $objectManager = $this->getObjectManager([
            $mappedEntityClass,
            $inversedEntityClass,
        ], true, [__DIR__.'/RemoveAssociationOnly/AttributeDefinedOnMappedSide/']);

        $mappedEntityRepository = $objectManager->getRepository($mappedEntityClass);
        $inversedEntityRepository = $objectManager->getRepository($inversedEntityClass);

        // Fixture
        $inversedEntity1 = new $inversedEntityClass();
        $inversedEntity2 = new $inversedEntityClass();
        $mappedEntity = new $mappedEntityClass();
        $mappedEntity->addChild($inversedEntity1);
        $mappedEntity->addChild($inversedEntity2);
        $objectManager->persist($inversedEntity1);
        $objectManager->persist($inversedEntity2);
        $objectManager->persist($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        self::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Remove
        self::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($inversedEntity1);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        self::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Check
        self::assertNotNull($inversedEntity1->getDeletedAt());
        self::assertCount(0, $inversedEntity1->getParents());
        self::assertNotContains($mappedEntity, $inversedEntity1->getParents());
        self::assertNull($inversedEntity2->getDeletedAt());
        self::assertNull($mappedEntity->getDeletedAt());
        self::assertNotContains($inversedEntity1, $mappedEntity->getChildren());
        self::assertCount(1, $mappedEntity->getChildren());
    }

    public function testWhenAssociationIsUnidirectionalButTheMappedSideHasAnAttributeAndTheTargetEntityIsSoftDeletedTheTargetEntityIsRemovedFromTheCollection(): void
    {
        // Construct
        $mappedEntityClass = RemoveAssociationOnly\AttributeDefinedButUnidirectional\MappedEntity::class;
        $inversedEntityClass = RemoveAssociationOnly\AttributeDefinedButUnidirectional\InversedEntity::class;
        $objectManager = $this->getObjectManager([
            $mappedEntityClass,
            $inversedEntityClass,
        ], true, [__DIR__.'/RemoveAssociationOnly/AttributeDefinedButUnidirectional/']);

        $mappedEntityRepository = $objectManager->getRepository($mappedEntityClass);
        $inversedEntityRepository = $objectManager->getRepository($inversedEntityClass);

        // Fixture
        $inversedEntity1 = new $inversedEntityClass();
        $inversedEntity2 = new $inversedEntityClass();
        $mappedEntity = new $mappedEntityClass();
        $mappedEntity->addChild($inversedEntity1);
        $mappedEntity->addChild($inversedEntity2);
        $objectManager->persist($inversedEntity1);
        $objectManager->persist($inversedEntity2);
        $objectManager->persist($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        self::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Remove
        self::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($inversedEntity1);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        self::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        self::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Check
        self::assertNotNull($inversedEntity1->getDeletedAt());
        self::assertNull($mappedEntity->getDeletedAt());
        self::assertNull($inversedEntity2->getDeletedAt());
        self::assertCount(1, $mappedEntity->getChildren());
        self::assertNotContains($inversedEntity1, $mappedEntity->getChildren());
    }
}
