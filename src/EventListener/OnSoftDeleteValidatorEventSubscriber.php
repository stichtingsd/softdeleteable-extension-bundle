<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Annotation\SoftDeleteable;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteAssociationTargetNotFoundException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteAssociationTypeNotSupportedException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteClassHasInvalidGedmoAttributeException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteClassHasNoGedmoAttributeException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteManyToManyNotOnMappedSideException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteUnknownTypeException;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OnSoftDeleteValidatorEventSubscriber implements EventSubscriber
{
    use ContainerAwareTrait;

    private const SUPPORTED_ASSOCIATION_TYPES = [
        'OneToOne' => ClassMetadataInfo::ONE_TO_ONE,
        'ManyToOne' => ClassMetadataInfo::MANY_TO_ONE,
        'ManyToMany' => ClassMetadataInfo::MANY_TO_MANY,
    ];
    private const ASSOCIATION_TO_STRING_MAP = [
        ClassMetadataInfo::ONE_TO_ONE => 'OneToOne',
        ClassMetadataInfo::MANY_TO_ONE => 'ManyToOne',
        ClassMetadataInfo::TO_ONE => 'ToOne',
        ClassMetadataInfo::ONE_TO_MANY => 'OneToMany',
        ClassMetadataInfo::TO_MANY => 'ToMany',
        ClassMetadataInfo::MANY_TO_MANY => 'ManyToMany',
    ];

    private ObjectManager $objectManager;

    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetaData(LoadClassMetadataEventArgs $args): void
    {
        $this->objectManager = $args->getObjectManager();
        $classMetaData = $args->getClassMetadata();
        $reflClass = $classMetaData->getReflectionClass();

        foreach ($reflClass->getProperties() as $reflProperty) {
            $onSoftDeleteAttributes = $reflProperty->getAttributes(onSoftDelete::class);
            if (empty($onSoftDeleteAttributes)) {
                continue;
            }
            $onSoftDeleteAttribute = $onSoftDeleteAttributes[0];
            $associationMapping = $classMetaData->getAssociationMapping($reflProperty->getName());
            $this->throwIfAssociationTypeIsUnsupported($associationMapping, $reflClass, $reflProperty);
            $this->throwIfAssociatedObjectIsNotFound($associationMapping, $reflClass, $reflProperty);
            $this->throwIfSoftDeleteTypeIsInvalid($onSoftDeleteAttribute, $reflClass, $reflProperty);
            $this->throwIfAssociationHasOnSoftDeleteTypeCascadeButTheAssociatedClassHasNoGedmoSoftDeleteAttribute($associationMapping, $onSoftDeleteAttribute, $reflClass, $reflProperty);
            $this->throwIfAssociationTypeIsManyToManyButSoftDeleteTypeIsNotRemoveAssociationOnly($associationMapping, $onSoftDeleteAttribute, $reflClass, $reflProperty);
            $this->throwIfSoftDeleteTypeIsRemoveAssociationOnlyButAssociationTypeIsNotManyToMany($associationMapping, $onSoftDeleteAttribute, $reflClass, $reflProperty);
            $this->throwIsSoftDeleteTypeForManyToManyIsDefinedOnInversedSide($associationMapping, $reflClass, $reflProperty);
        }
    }

    private function throwIfAssociationTypeIsUnsupported(array $associationMapping, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty): void
    {
        $type = $associationMapping['type'];
        if (!\in_array($type, static::SUPPORTED_ASSOCIATION_TYPES, true)) {
            $associationHumanReadable = static::ASSOCIATION_TO_STRING_MAP[$type] ?? \sprintf('Unknown type: %d', $type);
            $associationMapHumanReadable = \implode(', ', \array_keys(static::SUPPORTED_ASSOCIATION_TYPES));
            throw new SoftDeleteAssociationTypeNotSupportedException(\sprintf('AssociationType unsupported in %s->%s. got: %s, expected one of: %s.', $reflClass->getName(), $reflProperty->getName(), $associationHumanReadable, $associationMapHumanReadable));
        }
    }

    private function throwIfAssociatedObjectIsNotFound(array $associationMapping, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty): void
    {
        $targetEntity = $associationMapping['targetEntity'] ?? '';
        if (!\class_exists($targetEntity)) {
            throw new SoftDeleteAssociationTargetNotFoundException(\sprintf('Set associated targetEntity %s could not be found. In %s->%s.', $targetEntity, $reflClass->getName(), $reflProperty->getName()));
        }
    }

    private function throwIfSoftDeleteTypeIsInvalid(\ReflectionAttribute $onSoftDeleteAttribute, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty): void
    {
        $arguments = $onSoftDeleteAttribute->getArguments();
        $type = $arguments[0] ?? $arguments['type'] ?? null;
        if (!$type instanceof Type) {
            throw new SoftDeleteUnknownTypeException(\sprintf('Invalid type given to onSoftDelete attribute, expected one of %s. In %s->%s.', Type::class, $reflClass->getName(), $reflProperty->getName()));
        }
    }

    private function throwIfAssociationHasOnSoftDeleteTypeCascadeButTheAssociatedClassHasNoGedmoSoftDeleteAttribute(array $associationMapping, \ReflectionAttribute $onSoftDeleteAttribute, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty): void
    {
        $arguments = $onSoftDeleteAttribute->getArguments();
        $type = $arguments[0] ?? $arguments['type'];
        if (Type::CASCADE !== $type) {
            return;
        }
        $targetEntity = $associationMapping['targetEntity'];
        $targetEntityReflClass = new \ReflectionClass($targetEntity);
        $targetEntityGedmoAttrs = $targetEntityReflClass->getAttributes(SoftDeleteable::class);
        if (empty($targetEntityGedmoAttrs)) {
            throw new SoftDeleteClassHasNoGedmoAttributeException(\sprintf('Class %s has no Gedmo\SoftDeleteable attribute. Required for onSoftDelete(Type::CASCADE) in %s->%s.', $targetEntityReflClass->getName(), $reflClass->getName(), $reflProperty->getName()));
        }

        $fieldName = $targetEntityGedmoAttrs[0]->getArguments()['fieldName'] ?? null;
        if (empty($fieldName)) {
            throw new SoftDeleteClassHasInvalidGedmoAttributeException(\sprintf('Class %s has Gedmo\SoftDeleteable attribute but no fieldName. Required for onSoftDelete(Type::CASCADE) in %s->%s.', $targetEntityReflClass->getName(), $reflClass->getName(), $reflProperty->getName()));
        }
    }

    private function throwIfAssociationTypeIsManyToManyButSoftDeleteTypeIsNotRemoveAssociationOnly(array $associationMapping, \ReflectionAttribute $onSoftDeleteAttribute, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty): void
    {
        if (ClassMetadataInfo::MANY_TO_MANY !== $associationMapping['type']) {
            return;
        }

        $arguments = $onSoftDeleteAttribute->getArguments();
        $type = $arguments[0] ?? $arguments['type'];
        if (Type::REMOVE_ASSOCIATION_ONLY === $type) {
            return;
        }

        throw new SoftDeleteAssociationTypeNotSupportedException(\sprintf('Expected Type::REMOVE_ASSOCIATION_ONLY for ManyToMany association in %s->%s. Given %s.', $reflClass->getName(), $reflProperty->getName(), $type->value));
    }

    private function throwIfSoftDeleteTypeIsRemoveAssociationOnlyButAssociationTypeIsNotManyToMany(array $associationMapping, \ReflectionAttribute $onSoftDeleteAttribute, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty): void
    {
        $arguments = $onSoftDeleteAttribute->getArguments();
        $type = $arguments[0] ?? $arguments['type'];
        if (ClassMetadataInfo::MANY_TO_MANY !== $associationMapping['type'] && Type::REMOVE_ASSOCIATION_ONLY === $type) {
            throw new SoftDeleteAssociationTypeNotSupportedException(\sprintf('Type::REMOVE_ASSOCIATION_ONLY applies only to ManyToMany associations in %s->%s. Given %s.', $reflClass->getName(), $reflProperty->getName(), $type->value));
        }
    }

    private function throwIsSoftDeleteTypeForManyToManyIsDefinedOnInversedSide(array $associationMapping, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty): void
    {
        if (ClassMetadataInfo::MANY_TO_MANY !== $associationMapping['type']) {
            return;
        }
        if (true === $associationMapping['isOwningSide']) {
            return;
        }
        throw new SoftDeleteManyToManyNotOnMappedSideException(\sprintf('StichtingSD\onSoftDelete() should be defined on the mapped/owning side of the ManyToMany relation. In: %s->%s.', $reflClass->getName(), $reflProperty->getName()));
    }
}
