<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany;

use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteAssociationTypeNotSupportedException;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseTestCase;

/**
 * @internal
 */
final class SoftDeleteOneToManyTest extends BaseTestCase
{
    public function testOneToManyAssociationIsUnsupportedForCascadeType(): void
    {
        static::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        static::expectExceptionMessage('AssociationType unsupported in StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\Cascade\SimpleMapping\ParentEntity->children. got: OneToMany, expected one of: OneToOne, ManyToOne, ManyToMany.');
        $this->getObjectManager([
            Cascade\SimpleMapping\ChildEntity::class,
            Cascade\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/Cascade/SimpleMapping']);
    }

    public function testOneToManyAssociationIsUnsupportedForSetNullType(): void
    {
        static::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        static::expectExceptionMessage('AssociationType unsupported in StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\RemoveAssociationOnly\SimpleMapping\ParentEntity->children. got: OneToMany, expected one of: OneToOne, ManyToOne, ManyToMany.');
        $this->getObjectManager([
            RemoveAssociationOnly\SimpleMapping\ChildEntity::class,
            RemoveAssociationOnly\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/RemoveAssociationOnly/SimpleMapping']);
    }

    public function testOneToManyAssociationIsUnsupportedForRemoveAssociationType(): void
    {
        static::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        static::expectExceptionMessage('AssociationType unsupported in StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\SetNull\SimpleMapping\ParentEntity->children. got: OneToMany, expected one of: OneToOne, ManyToOne, ManyToMany.');
        $this->getObjectManager([
            SetNull\SimpleMapping\ChildEntity::class,
            SetNull\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/SetNull/SimpleMapping']);
    }
}
