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
        static::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        static::expectExceptionMessage('Expected Type::REMOVE_ASSOCIATION_ONLY for ManyToMany association in StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\Cascade\MappedSide\InversedEntity->parents. Given CASCADE.');
        $this->getObjectManager([
            Cascade\MappedSide\InversedEntity::class,
            Cascade\MappedSide\MappedEntity::class,
        ], true, [__DIR__.'/Cascade/MappedSide/']);
    }

    public function testManyToManyWithSetNullThrowsExceptionUnsupportedType(): void
    {
        static::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        static::expectExceptionMessage('Expected Type::REMOVE_ASSOCIATION_ONLY for ManyToMany association in StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\SetNull\MappedSide\InversedEntity->parents. Given SET_NULL.');
        $this->getObjectManager([
            SetNull\MappedSide\InversedEntity::class,
            SetNull\MappedSide\MappedEntity::class,
        ], true, [__DIR__.'/SetNull/MappedSide/']);
    }

    public function testAttributeOnInversedSideAndInversedSideEntityIsSoftDeletedOnlyTheAssociationIsRemoved(): void
    {
        static::expectException(SoftDeleteManyToManyNotOnMappedSideException::class);
        static::expectExceptionMessage('StichtingSD\onSoftDelete() should be defined on the mapped/owning side of the ManyToMany relation. In: StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\RemoveAssociationOnly\AttributeDefinedOnInversedSide\InversedEntity->parents.');
        // Construct
        $mappedEntityClass = RemoveAssociationOnly\AttributeDefinedOnInversedSide\MappedEntity::class;
        $inversedEntityClass = RemoveAssociationOnly\AttributeDefinedOnInversedSide\InversedEntity::class;
        $objectManager = $this->getObjectManager([
            $mappedEntityClass,
            $inversedEntityClass,
        ], true, [__DIR__.'/RemoveAssociationOnly/AttributeDefinedOnInversedSide/']);
//
//        // Tests keep failing. Too many hacky code and since there is an owning side the user should set it on the owning side.
//        $mappedEntityRepository = $objectManager->getRepository($mappedEntityClass);
//        $inversedEntityRepository = $objectManager->getRepository($inversedEntityClass);
//
//        // Fixture
//        $inversedEntity1 = new $inversedEntityClass();
//        $inversedEntity2 = new $inversedEntityClass();
//        $mappedEntity = new $mappedEntityClass();
//        $mappedEntity->addChild($inversedEntity1);
//        $mappedEntity->addChild($inversedEntity2);
//        $objectManager->persist($inversedEntity1);
//        $objectManager->persist($inversedEntity2);
//        $objectManager->persist($mappedEntity);
//        $objectManager->flush();
//        $objectManager->clear();
//
//        // Re-fetch
//        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
//        static::assertInstanceOf($mappedEntityClass, $mappedEntity);
//        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
//        static::assertInstanceOf($inversedEntityClass, $inversedEntity1);
//
//        // Remove
//        static::assertCount(2, $mappedEntity->getChildren());
//        $objectManager->remove($inversedEntity1);
//        $objectManager->flush();
//        $objectManager->clear();
//
//        // Re-fetch
//        $objectManager->getFilters()->disable('softdeleteable');
//        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
//        static::assertInstanceOf($mappedEntityClass, $mappedEntity);
//        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
//        static::assertInstanceOf($inversedEntityClass, $inversedEntity1);
//
//        // Check
//        static::assertNotNull($inversedEntity1->getDeletedAt());
//        static::assertNull($mappedEntity->getDeletedAt());
//        static::assertNull($inversedEntity2->getDeletedAt());
//        static::assertCount(0, $inversedEntity1->getParents());
//        static::assertCount(1, $mappedEntity->getChildren());
//        static::assertNotContains($inversedEntity1, $mappedEntity->getChildren());
    }

    public function testAttributeOnInversedSideAndMappedSideEntityIsSoftDeletedOnlyTheAssociationIsRemoved(): void
    {
        static::expectException(SoftDeleteManyToManyNotOnMappedSideException::class);
        static::expectExceptionMessage('StichtingSD\onSoftDelete() should be defined on the mapped/owning side of the ManyToMany relation. In: StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\RemoveAssociationOnly\AttributeDefinedOnInversedSide\InversedEntity->parents.');
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
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Remove the parent
        static::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Check
        static::assertNotNull($mappedEntity->getDeletedAt());
        static::assertCount(0, $mappedEntity->getChildren());
        static::assertNull($inversedEntity1->getDeletedAt());
        static::assertNotContains($mappedEntity, $inversedEntity1->getParents());
        static::assertNull($inversedEntity2->getDeletedAt());
        static::assertNotContains($mappedEntity, $inversedEntity2->getParents());
    }

    public function testAttributeOnMappedSideAndMappedSideEntityIsSoftDeletedOnlyTheAssociationIsRemoved(): void
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
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Remove
        static::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($mappedEntity);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);

        // Check
        static::assertNotNull($mappedEntity->getDeletedAt());
        static::assertCount(0, $mappedEntity->getChildren());
        static::assertNull($inversedEntity1->getDeletedAt());
        static::assertNotContains($mappedEntity, $inversedEntity1->getParents());
        static::assertNull($inversedEntity2->getDeletedAt());
        static::assertNotContains($mappedEntity, $inversedEntity2->getParents());
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
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        static::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Remove
        static::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($inversedEntity1);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        static::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Check
        static::assertNotNull($inversedEntity1->getDeletedAt());
        static::assertCount(0, $inversedEntity1->getParents());
        static::assertNotContains($mappedEntity, $inversedEntity1->getParents());
        static::assertNull($inversedEntity2->getDeletedAt());
        static::assertNull($mappedEntity->getDeletedAt());
        static::assertNotContains($inversedEntity1, $mappedEntity->getChildren());
        static::assertCount(1, $mappedEntity->getChildren());
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
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        static::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Remove
        static::assertCount(2, $mappedEntity->getChildren());
        $objectManager->remove($inversedEntity1);
        $objectManager->flush();
        $objectManager->clear();

        // Re-fetch
        $objectManager->getFilters()->disable('softdeleteable');
        $mappedEntity = $mappedEntityRepository->find($mappedEntity->getId());
        static::assertInstanceOf($mappedEntityClass, $mappedEntity);
        $inversedEntity1 = $inversedEntityRepository->find($inversedEntity1->getId());
        static::assertInstanceOf($inversedEntityClass, $inversedEntity1);

        // Check
        static::assertNotNull($inversedEntity1->getDeletedAt());
        static::assertNull($mappedEntity->getDeletedAt());
        static::assertNull($inversedEntity2->getDeletedAt());
        static::assertCount(1, $mappedEntity->getChildren());
        static::assertNotContains($inversedEntity1, $mappedEntity->getChildren());
    }
}
