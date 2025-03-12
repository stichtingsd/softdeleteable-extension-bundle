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
        self::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        self::expectExceptionMessage('AssociationType unsupported. got: OneToMany, expected one of: OneToOne, ManyToOne, ManyToMany. In StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\Cascade\SimpleMapping\ParentEntity->children.');
        $this->getObjectManager([
            Cascade\SimpleMapping\ChildEntity::class,
            Cascade\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/Cascade/SimpleMapping']);
    }

    public function testOneToManyAssociationIsUnsupportedForSetNullType(): void
    {
        self::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        self::expectExceptionMessage('AssociationType unsupported. got: OneToMany, expected one of: OneToOne, ManyToOne, ManyToMany. In StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\RemoveAssociationOnly\SimpleMapping\ParentEntity->children.');
        $this->getObjectManager([
            RemoveAssociationOnly\SimpleMapping\ChildEntity::class,
            RemoveAssociationOnly\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/RemoveAssociationOnly/SimpleMapping']);
    }

    public function testOneToManyAssociationIsUnsupportedForRemoveAssociationType(): void
    {
        self::expectException(SoftDeleteAssociationTypeNotSupportedException::class);
        self::expectExceptionMessage('AssociationType unsupported. got: OneToMany, expected one of: OneToOne, ManyToOne, ManyToMany. In StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\SetNull\SimpleMapping\ParentEntity->children.');
        $this->getObjectManager([
            SetNull\SimpleMapping\ChildEntity::class,
            SetNull\SimpleMapping\ParentEntity::class,
        ], true, [__DIR__.'/SetNull/SimpleMapping']);
    }
}
