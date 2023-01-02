<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToOne;

use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteAssociationTypeNotSupportedException;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseTestCase;

/**
 * @internal
 */
final class SoftDeleteManyToOneTest extends BaseTestCase
{
    public function testWhenParentEntityHasOnSoftDeleteCascadeAllChildrenAreAlsoDeleted(): void
    {
        // We only import the SimpleMapping entities.
        $objectManager = $this->getObjectManager(
            [Cascade\SimpleMapping\ChildEntity::class, Cascade\SimpleMapping\ParentEntity::class],
            true,
            [__DIR__.'/Cascade/SimpleMapping/']
        );

        $child1 = new Cascade\SimpleMapping\ChildEntity();
        $objectManager->persist($child1);
        $child2 = new Cascade\SimpleMapping\ChildEntity();
        $objectManager->persist($child2);

        $parent1 = new Cascade\SimpleMapping\ParentEntity();
        $parent1->addChild($child1);
        $parent1->addChild($child2);
        $objectManager->persist($parent1);
        $objectManager->flush();

        $parentRepository = $objectManager->getRepository(Cascade\SimpleMapping\ParentEntity::class);
        $parent = $parentRepository->find($parent1->getId());
        static::assertInstanceOf(Cascade\SimpleMapping\ParentEntity::class, $parent);
        static::assertCount(2, $parent->getChildren());

        $objectManager->remove($parent);
        $objectManager->flush();
        $objectManager->clear();

        $objectManager->getFilters()->disable('softdeleteable');
        $parent = $parentRepository->find($parent->getId());
        static::assertNotNull($parent->getDeletedAt());
        foreach ($parent->getChildren() as $child) {
            static::assertNotNull($child->getDeletedAt());
        }
    }

    public function testOnlyAssociatedChildrenAreRemovedAndOthersAreNotSoftDeleted(): void
    {
        // We only import the SimpleMapping entities.
        $objectManager = $this->getObjectManager([
            Cascade\SimpleMapping\ChildEntity::class,
            Cascade\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/Cascade/SimpleMapping/']);

        $child1 = new Cascade\SimpleMapping\ChildEntity();
        $objectManager->persist($child1);
        $child2 = new Cascade\SimpleMapping\ChildEntity();
        $objectManager->persist($child2);
        $notRelated = new Cascade\SimpleMapping\ChildEntity();
        $objectManager->persist($notRelated);

        $parent1 = new Cascade\SimpleMapping\ParentEntity();
        $parent1->addChild($child1);
        $parent1->addChild($child2);
        $objectManager->persist($parent1);
        $objectManager->flush();

        $parentRepository = $objectManager->getRepository(Cascade\SimpleMapping\ParentEntity::class);
        $parent = $parentRepository->find($parent1->getId());
        static::assertInstanceOf(Cascade\SimpleMapping\ParentEntity::class, $parent);
        static::assertCount(2, $parent->getChildren());

        $objectManager->remove($parent);
        $objectManager->flush();
        $objectManager->clear();

        $objectManager->getFilters()->disable('softdeleteable');
        $parent = $parentRepository->find($parent->getId());
        static::assertNull($notRelated->getDeletedAt());
        static::assertNotNull($parent->getDeletedAt());
        foreach ($parent->getChildren() as $child) {
            static::assertNotNull($child->getDeletedAt());
        }
    }

    public function testWhenParentEntityHasOnSoftDeleteCascadeAndAChildHasARelationThatAlsoHasOnSoftDeleteTheyAreAlsoDeletedRercursively(): void
    {
        // We only import the SimpleMapping entities.
        $objectManager = $this->getObjectManager([
            Cascade\MultiLevelChildrenSoftDelete\ChildMetaEntity::class,
            Cascade\MultiLevelChildrenSoftDelete\ChildEntity::class,
            Cascade\MultiLevelChildrenSoftDelete\ParentEntity::class,
        ], true, [__DIR__.'/Cascade/MultiLevelChildrenSoftDelete/']);

        $child1 = new Cascade\MultiLevelChildrenSoftDelete\ChildEntity();
        $objectManager->persist($child1);
        $child2 = new Cascade\MultiLevelChildrenSoftDelete\ChildEntity();
        $objectManager->persist($child2);

        $i = 1000;
        while ($i--) {
            $child1Meta = new Cascade\MultiLevelChildrenSoftDelete\ChildMetaEntity();
            $child1->addChildMeta($child1Meta);
            $objectManager->persist($child1Meta);

            $child2Meta = new Cascade\MultiLevelChildrenSoftDelete\ChildMetaEntity();
            $child2->addChildMeta($child2Meta);
            $objectManager->persist($child2Meta);
        }

        $notRelated = new Cascade\MultiLevelChildrenSoftDelete\ChildEntity();
        $objectManager->persist($notRelated);

        $parent1 = new Cascade\MultiLevelChildrenSoftDelete\ParentEntity();
        $parent1->addChild($child1);
        $parent1->addChild($child2);
        $objectManager->persist($parent1);
        $objectManager->flush();

        $parentRepository = $objectManager->getRepository(Cascade\MultiLevelChildrenSoftDelete\ParentEntity::class);
        $parent = $parentRepository->find($parent1->getId());
        static::assertInstanceOf(Cascade\MultiLevelChildrenSoftDelete\ParentEntity::class, $parent);
        static::assertCount(2, $parent->getChildren());
        foreach ($parent->getChildren() as $child) {
            static::assertCount(1000, $child->getChildMetas());
            static::assertNull($child->getDeletedAt());
            foreach ($child->getChildMetas() as $meta) {
                static::assertNull($meta->getDeletedAt());
            }
        }

        $objectManager->remove($parent);
        $objectManager->flush();
        $objectManager->clear();

        $objectManager->getFilters()->disable('softdeleteable');
        $parent = $parentRepository->find($parent->getId());
        static::assertNull($notRelated->getDeletedAt());
        static::assertNotNull($parent->getDeletedAt());
        foreach ($parent->getChildren() as $child) {
            static::assertNotNull($child->getDeletedAt());
            foreach ($child->getChildMetas() as $meta) {
                static::assertNotNull($meta->getDeletedAt());
            }
        }
    }

    public function testWhenParentEntityHasOnSoftDeleteSetNullAllChildrenAreAlsoDeleted(): void
    {
        // We only import the SimpleMapping entities.
        $objectManager = $this->getObjectManager([
            SetNull\SimpleMapping\ChildEntity::class,
            SetNull\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/SetNull/SimpleMapping/']);

        $child1 = new SetNull\SimpleMapping\ChildEntity();
        $objectManager->persist($child1);
        $child2 = new SetNull\SimpleMapping\ChildEntity();
        $objectManager->persist($child2);

        $parent1 = new SetNull\SimpleMapping\ParentEntity();
        $parent1->addChild($child1);
        $parent1->addChild($child2);
        $objectManager->persist($parent1);
        $objectManager->flush();

        $parentRepository = $objectManager->getRepository(SetNull\SimpleMapping\ParentEntity::class);
        $parent = $parentRepository->find($parent1->getId());
        static::assertInstanceOf(SetNull\SimpleMapping\ParentEntity::class, $parent);
        static::assertCount(2, $parent->getChildren());

        $objectManager->remove($parent);
        $objectManager->flush();
        $objectManager->clear();

        $objectManager->getFilters()->disable('softdeleteable');
        $parent = $parentRepository->find($parent->getId());
        static::assertNotNull($parent->getDeletedAt());
        static::assertCount(0, $parent->getChildren());
    }

    public function testOnlyAssociatedChildrenAreNulledAndOthersAreNotNulled(): void
    {
        // We only import the SimpleMapping entities.
        $objectManager = $this->getObjectManager([
            SetNull\SimpleMapping\ChildEntity::class,
            SetNull\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/SetNull/SimpleMapping/']);

        $child1 = new SetNull\SimpleMapping\ChildEntity();
        $objectManager->persist($child1);
        $child2 = new SetNull\SimpleMapping\ChildEntity();
        $objectManager->persist($child2);
        $notRelated = new SetNull\SimpleMapping\ChildEntity();
        $objectManager->persist($notRelated);

        $parent1 = new SetNull\SimpleMapping\ParentEntity();
        $parent1->addChild($child1);
        $parent1->addChild($child2);
        $objectManager->persist($parent1);
        $objectManager->flush();

        $parentRepository = $objectManager->getRepository(SetNull\SimpleMapping\ParentEntity::class);
        $parent = $parentRepository->find($parent1->getId());
        static::assertInstanceOf(SetNull\SimpleMapping\ParentEntity::class, $parent);
        static::assertCount(2, $parent->getChildren());

        $objectManager->remove($parent);
        $objectManager->flush();
        $objectManager->clear();

        $objectManager->getFilters()->disable('softdeleteable');
        $parent = $parentRepository->find($parent->getId());
        static::assertNull($notRelated->getDeletedAt());
        static::assertNotNull($parent->getDeletedAt());
        static::assertCount(0, $parent->getChildren());
    }

    public function testWhenParentEntityHasOnSoftDeleteSetNullAndAChildHasARelationThatAlsoHasOnSoftDeleteTheyAreAlsoDeletedRercursively(): void
    {
        // We only import the SimpleMapping entities.
        $objectManager = $this->getObjectManager([
            SetNull\MultiLevelChildrenSoftDelete\ChildMetaEntity::class,
            SetNull\MultiLevelChildrenSoftDelete\ChildEntity::class,
            SetNull\MultiLevelChildrenSoftDelete\ParentEntity::class,
        ], true, [__DIR__.'/SetNull/MultiLevelChildrenSoftDelete/']);

        $child1 = new SetNull\MultiLevelChildrenSoftDelete\ChildEntity();
        $objectManager->persist($child1);
        $child2 = new SetNull\MultiLevelChildrenSoftDelete\ChildEntity();
        $objectManager->persist($child2);

        $i = 1000;
        while ($i--) {
            $child1Meta = new SetNull\MultiLevelChildrenSoftDelete\ChildMetaEntity();
            $child1->addChildMeta($child1Meta);
            $objectManager->persist($child1Meta);

            $child2Meta = new SetNull\MultiLevelChildrenSoftDelete\ChildMetaEntity();
            $child2->addChildMeta($child2Meta);
            $objectManager->persist($child2Meta);
        }

        $notRelated = new SetNull\MultiLevelChildrenSoftDelete\ChildEntity();
        $objectManager->persist($notRelated);

        $parent1 = new SetNull\MultiLevelChildrenSoftDelete\ParentEntity();
        $parent1->addChild($child1);
        $parent1->addChild($child2);
        $objectManager->persist($parent1);
        $objectManager->flush();

        $parentRepository = $objectManager->getRepository(SetNull\MultiLevelChildrenSoftDelete\ParentEntity::class);
        $parent = $parentRepository->find($parent1->getId());
        static::assertInstanceOf(SetNull\MultiLevelChildrenSoftDelete\ParentEntity::class, $parent);
        static::assertCount(2, $parent->getChildren());
        foreach ($parent->getChildren() as $child) {
            static::assertCount(1000, $child->getChildMetas());
            static::assertNull($child->getDeletedAt());
            foreach ($child->getChildMetas() as $meta) {
                static::assertNull($meta->getDeletedAt());
            }
        }

        $objectManager->remove($parent);
        $objectManager->flush();
        $objectManager->clear();

        $objectManager->getFilters()->disable('softdeleteable');
        $parent = $parentRepository->find($parent->getId());
        static::assertNull($notRelated->getDeletedAt());
        static::assertNotNull($parent->getDeletedAt());
        foreach ($parent->getChildren() as $child) {
            static::assertNotNull($child->getDeletedAt());
            foreach ($child->getChildMetas() as $meta) {
                static::assertNotNull($meta->getDeletedAt());
            }
        }
    }

    public function testOnSoftDeleteAttributeOnOneToManyThrowsException(): void
    {
        static::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        static::expectExceptionMessage('Type::REMOVE_ASSOCIATION_ONLY applies only to ManyToMany associations in StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToOne\RemoveAssociationOnly\SimpleMapping\ChildEntity->parent. Given REMOVE_ASSOCIATION_ONLY.');
        $this->getObjectManager([
            RemoveAssociationOnly\SimpleMapping\ChildEntity::class,
            RemoveAssociationOnly\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/RemoveAssociationOnly/Entities/ValidMapping']);
    }
}
