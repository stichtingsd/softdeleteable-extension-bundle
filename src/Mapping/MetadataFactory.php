<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Annotation\SoftDeleteable;
use Psr\Cache\CacheItemPoolInterface;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteAssociationTypeNotSupportedException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteBundleExceptionInterface;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteClassHasInvalidGedmoAttributeException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteClassHasNoGedmoAttributeException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteManyToManyNotOnMappedSideException;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeleteUnknownTypeException;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;

final class MetadataFactory
{
    use MetadataCacheAwareTrait;

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

    public function __construct(CacheItemPoolInterface $cacheItemPool = null)
    {
        null !== $cacheItemPool && $this->cacheItemPool = $cacheItemPool;
    }

    public function computeSingleClassMetadata(ClassMetadata $classMetaData): void
    {
        $className = $classMetaData->getName();
        $reflClass = $classMetaData->getReflectionClass();

        // Also set cache for classes that are empty, so we don't check them again.
        if (!$this->hasCachedMetadataForClass($className)) {
            $this->setMetadataCacheForClass($className, []);
        }

        if ($reflClass->isAbstract()) {
            return;
        }

        foreach ($classMetaData->getAssociationNames() as $associationName) {
            try {
                $reflProperty = $reflClass->getProperty($associationName);
                $onSoftDeleteAttribute = $this->extractSoftDeleteAttribute($reflProperty);
                $associationMapping = $classMetaData->getAssociationMapping($associationName);
                $associationTargetClass = $classMetaData->getAssociationTargetClass($associationMapping['fieldName']);

                if (null === $onSoftDeleteAttribute) {
                    continue;
                }

                $associationMappingType = $this->extractAssociationType($associationMapping);
                $type = $this->extractSoftDeleteType($onSoftDeleteAttribute);
                $associationTargetReflClass = new \ReflectionClass($associationTargetClass);
                $associationTargetClassGedmoAttr = $this->extractGedmoSoftDeleteAttributeFrom($associationTargetReflClass);
                $targetEntitySoftDeleteFieldName = $this->extractFieldName($associationTargetClassGedmoAttr);
                $targetEntityProperty = $associationMapping['mappedBy'] ?: $associationMapping['inversedBy'];
                $isUnidirectional = empty($associationMapping['mappedBy']) && empty($associationMapping['inversedBy']);

                $this->throwIfAssociationTypeIsManyToManyButSoftDeleteTypeIsNotRemoveAssociationOnly($associationMapping, $type);
                $this->throwIfSoftDeleteTypeIsRemoveAssociationOnlyButAssociationTypeIsNotManyToMany($associationMapping, $type);
                $this->throwIfSoftDeleteTypeForManyToManyIsDefinedOnInversedSide($associationMapping);
            } catch (SoftDeleteBundleExceptionInterface $e) {
                throw $e->addMessage(sprintf(' In %s->%s.', $reflClass->getName(), $associationName));
            }

            // Fallback to association name for unidirectional relations.
            $property = $targetEntityProperty ?: $associationName;
            $this->addPropertyToMetadataCache($associationTargetClass, $property, [
                'associatedTo' => $classMetaData->getName(),
                'associatedToProperty' => $associationName,
                'targetEntityProperty' => $targetEntityProperty,
                'targetEntitySoftDeleteFieldName' => $targetEntitySoftDeleteFieldName,
                'associationMappingType' => $associationMappingType,
                'isUnidirectional' => $isUnidirectional,
                'type' => $type->value,
            ]);
        }
    }

    public function computeMetadata(ObjectManager $objectManager): void
    {
        $allClassNames = $objectManager->getConfiguration()
            ->getMetadataDriverImpl()
            ->getAllClassNames()
        ;

        foreach ($allClassNames as $className) {
            $this->computeSingleClassMetadata($objectManager->getClassMetadata($className));
        }
    }

    private function extractSoftDeleteAttribute(\ReflectionProperty $reflectionProperty): ?\ReflectionAttribute
    {
        $attribute = $reflectionProperty->getAttributes(onSoftDelete::class)[0] ?? null;

        if (!$attribute instanceof \ReflectionAttribute) {
            return null;
        }

        return $attribute;
    }

    private function extractAssociationType(array $associationMapping): int
    {
        $type = $associationMapping['type'];

        if (!\in_array($type, self::SUPPORTED_ASSOCIATION_TYPES, true)) {
            $associationHumanReadable = self::ASSOCIATION_TO_STRING_MAP[$type] ?? sprintf('Unknown type: %d', $type);
            $associationMapHumanReadable = implode(', ', array_keys(self::SUPPORTED_ASSOCIATION_TYPES));
            throw new SoftDeleteAssociationTypeNotSupportedException(sprintf('AssociationType unsupported. got: %s, expected one of: %s.', $associationHumanReadable, $associationMapHumanReadable));
        }

        return $type;
    }

    private function extractSoftDeleteType(\ReflectionAttribute $onSoftDeleteAttribute): Type
    {
        $arguments = $onSoftDeleteAttribute->getArguments();
        $type = $arguments[0] ?? $arguments['type'] ?? null;
        if (!$type instanceof Type) {
            throw new SoftDeleteUnknownTypeException(sprintf('Invalid type given to onSoftDelete attribute, expected one of %s.', Type::class));
        }

        return $type;
    }

    private function extractGedmoSoftDeleteAttributeFrom(\ReflectionClass $targetEntityReflClass): \ReflectionAttribute
    {
        $attribute = $targetEntityReflClass->getAttributes(SoftDeleteable::class)[0] ?? null;

        if (null === $attribute) {
            throw new SoftDeleteClassHasNoGedmoAttributeException(sprintf('Defined onSoftDelete(Type::CASCADE) but the associated class %s has no Gedmo softdelete attribute.', $targetEntityReflClass->getName()));
        }

        return $attribute;
    }

    private function extractFieldName(\ReflectionAttribute $attribute): string
    {
        $fieldName = $attribute->getArguments()['fieldName'] ?? null;

        if (null === $fieldName) {
            throw new SoftDeleteClassHasInvalidGedmoAttributeException('Class has Gedmo\SoftDeleteable attribute but no fieldName which is required for onSoftDelete(Type::CASCADE).');
        }

        return $fieldName;
    }

    private function throwIfSoftDeleteTypeForManyToManyIsDefinedOnInversedSide(array $associationMapping): void
    {
        if (ClassMetadataInfo::MANY_TO_MANY !== $associationMapping['type']) {
            return;
        }

        if (true === $associationMapping['isOwningSide']) {
            return;
        }

        throw new SoftDeleteManyToManyNotOnMappedSideException('StichtingSD\onSoftDelete() should be defined on the mapped/owning side of the ManyToMany relation.');
    }

    private function throwIfAssociationTypeIsManyToManyButSoftDeleteTypeIsNotRemoveAssociationOnly(array $associationMapping, Type $type): void
    {
        if (ClassMetadataInfo::MANY_TO_MANY !== $associationMapping['type']) {
            return;
        }

        if (Type::REMOVE_ASSOCIATION_ONLY === $type) {
            return;
        }

        throw new SoftDeleteAssociationTypeNotSupportedException(sprintf('Expected Type::REMOVE_ASSOCIATION_ONLY for ManyToMany association given %s.', $type->value));
    }

    private function throwIfSoftDeleteTypeIsRemoveAssociationOnlyButAssociationTypeIsNotManyToMany(array $associationMapping, Type $type): void
    {
        if (ClassMetadataInfo::MANY_TO_MANY !== $associationMapping['type'] && Type::REMOVE_ASSOCIATION_ONLY === $type) {
            throw new SoftDeleteAssociationTypeNotSupportedException(sprintf('Type::REMOVE_ASSOCIATION_ONLY applies only to ManyToMany associations given %s.', $type->value));
        }
    }
}
