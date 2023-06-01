<?php

declare(strict_types=1);

/*
 * This file is a part of the StichtingSD/SoftDeleteableExtensionBundle
 * (c) StichtingSD <info@stichtingsd.nl> https://stichtingsd.nl
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StichtingSD\SoftDeleteableExtensionBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Gedmo\Mapping\Annotation\SoftDeleteable;
use Gedmo\SoftDeleteable\SoftDeleteableListener as GedmoSoftDeleteableListener;
use StichtingSD\SoftDeleteableExtensionBundle\Exception\SoftDeletePropertyAccessorNotFoundException;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;

class OnSoftDeleteEventSubscriber
{
    use ContainerAwareTrait;

    public function preSoftDelete(LifecycleEventArgs $args)
    {
        $objectManager = $args->getObjectManager();
        $eventObject = $args->getObject();

        $allClassNames = $objectManager->getConfiguration()
            ->getMetadataDriverImpl()
            ->getAllClassNames()
        ;
        foreach ($allClassNames as $className) {
            $reflClass = new \ReflectionClass($className);
            if ($reflClass->isAbstract()) {
                return false;
            }

            $meta = $objectManager->getClassMetadata($className);
            $objectRepository = $objectManager->getRepository($className);
            foreach ($reflClass->getProperties() as $reflProperty) {
                // If the property is not an association, skip it.
                if (!\array_key_exists($reflProperty->getName(), $meta->getAssociationMappings())) {
                    continue;
                }

                // If no onSoftDelete attribute is defined, skip it.
                $softDeleteAttributes = $reflProperty->getAttributes(onSoftDelete::class);
                if (empty($softDeleteAttributes)) {
                    continue;
                }

                $arguments = $softDeleteAttributes[0]->getArguments();
                $type = $arguments[0] ?? $arguments['type'] ?? null;

                $associationMapping = $meta->getAssociationMapping($reflProperty->getName());
                // ManyToMany is always CASCADE with one but. We only want to remove the association itself instead of the other entity.
                // This is because the other entity can still be associated to other objects and by removing the associated object could cause unintended removals.
                if (ClassMetadataInfo::MANY_TO_MANY === $associationMapping['type'] && Type::REMOVE_ASSOCIATION_ONLY === $type) {
                    $this->removeAssociationsFromManyToMany($eventObject, $associationMapping, $reflClass, $reflProperty, $objectRepository, $objectManager);

                    // We must continue because we don't want to soft-delete the target many-to-many object, only its association.
                    continue;
                }

                // If it's not a ManyToMany (because it can have the attribute on the mapped and inversed side)
                // and the targetEntity is not an instance of object that was being soft deleted skip it.
                // For example, eventObject is a ParentEntity and being deleted
                // When we loop all the entities to look for associations to this entity.
                // If the current entity in the loop has an association, its targetEntityClassName is the ParentEntity.
                // So we must cascade softDelete the $className (sourceEntity) because its has a onSoftDelete() defined.
                if (!$eventObject instanceof (new $associationMapping['targetEntity']())) {
                    continue;
                }

                match ($type) {
                    Type::SET_NULL => $this->setNullAssociatedObjects($eventObject, $reflClass, $reflProperty, $objectRepository, $objectManager),
                    Type::CASCADE => $this->cascadeAssociatedObjects($eventObject, $reflClass, $reflProperty, $objectRepository, $objectManager)
                };
            }
        }
    }

    private function removeAssociationsFromManyToMany(object $eventObject, array $associationMapping, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty, ObjectRepository $objectRepository, ObjectManager $objectManager): void
    {
        // Unidirectional defined the ManyToMany on one side only, so there is no inversedBy or mappedBy
        // Because unidirectional is always defined on the owning side.
        $isUnidirectional = empty($associationMapping['mappedBy']) && empty($associationMapping['inversedBy']);
        $inversedAssociationRemoved = $reflClass->getName() !== $eventObject::class;

        if ($isUnidirectional || $inversedAssociationRemoved) {
            // IMPORTANT! TODO! BUG!
            // Currently a bug and I don't understand why the code bellow doesn't work.
            // For some reason, the query executed bellow does return an entity but the associated entity
            // is removed from the collection, thus I can't remove it using removeElement(). Weird.
            // I understand why, Doctrine probably removes the ManyToMany relation automaticly, and Gedmo reverts this deletion.
            // Well, I've tried using the POST_SOFT_DELETE event but that does not matter.
            // See SoftDeleteManyToManyTest (skipped one's).
            // Call $objectManager->clear(); and add a count and you see it works.
            $associatedObjects = $objectRepository->createQueryBuilder('e')
                ->innerJoin(sprintf('e.%s', $reflProperty->getName()), 'association')
                ->addSelect('association')
                ->andWhere(sprintf(':entity MEMBER OF e.%s', $reflProperty->getName()))
                ->setParameter('entity', $eventObject)
                ->getQuery()
                ->getResult()
            ;

            // For BULK deleting this is the best option we've got.
            // But it's too risky since we're grabbing the first joinColumn.
            // Executing plain SQL queries is highly discouraged by Doctrine.
            // $connection = $objectManager->getConnection();
            // $joinTableName = $associationMapping['joinTable']['name'] ?? null;
            // $inverseColumnName = $associationMapping['joinTable']['joinColumns'][0]['name'] ?? null;
            // $statement = $connection->prepare(sprintf('DELETE FROM %s WHERE %s IN (%s)', $joinTableName, $inverseColumnName, implode(',', $objectsAssociated)));
            // $statement->execute();

            $uow = $objectManager->getUnitOfWork();
            // For now, just loop all the related entities and remove it from the collection.
            foreach ($associatedObjects as $object) {
                // Gedmo handles re-computation for the removed item but not for the related items.
                // Since doctrine by default removed the many-to-many association on removal and Gedmo only re-computes the deleted entity.
                // It doesn't revert the changes made in the parent entity.
                $meta = $objectManager->getClassMetadata($object::class);
                $uow->computeChangeSet($meta, $object);

                $association = $reflProperty->getValue($object);
                $association->removeElement($eventObject);
            }

            return;
        }

        try {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $collection = $propertyAccessor->getValue($eventObject, $reflProperty->getName());
            $collection->clear();
        } catch (\Exception $e) {
            throw new SoftDeletePropertyAccessorNotFoundException(sprintf('No accessor found for %s in %s', $reflProperty->name, $eventObject::class), previous: $e);
        }
    }

    private function setNullAssociatedObjects(object $eventObject, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty, ObjectRepository $objectRepository, ObjectManager $objectManager): void
    {
        // Grab all the id's that are going to be updated, so we can schedule them for update.
        $objectsAssociatedToEventObject = $objectRepository->createQueryBuilder('e')
            ->select('e.id')
            ->andWhere("e.{$reflProperty->getName()} = :eventObject")
            ->setParameter('eventObject', $eventObject)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        // Actually update the entities, doing it this way won't cause memory problems.
        $objectRepository->createQueryBuilder('e')
            ->update()
            ->set("e.{$reflProperty->getName()}", ':relation')
            ->andWhere("e.{$reflProperty->getName()} = :eventObject")
            ->setParameter('eventObject', $eventObject)
            ->setParameter('relation', null)
            ->getQuery()
            ->execute()
        ;

        /**
         * @var UnitOfWork $uow
         */
        $uow = $objectManager->getUnitOfWork();
        // Use the getReference() method to fetch a partial object for each entity
        foreach ($objectsAssociatedToEventObject as $id) {
            $objectProxy = $objectManager->getReference($reflClass->getName(), $id);
            $uow->scheduleExtraUpdate($objectProxy, [
                $reflProperty->getName() => [$eventObject, null],
            ]);
        }
    }

    private function cascadeAssociatedObjects(object $eventObject, \ReflectionClass $reflClass, \ReflectionProperty $reflProperty, ObjectRepository $objectRepository, ObjectManager $objectManager): void
    {
        // Field name is set in the targetEntity class, when Entity1 as #[onSoftDelete()] on a property.
        // We should grab the SoftDelete fieldName from Gedmo.
        $gedmoAttributes = $reflClass->getAttributes(SoftDeleteable::class);
        $gedmoArguments = $gedmoAttributes[0]->getArguments();
        $fieldName = $gedmoArguments['fieldName'];

        // Actually update the entities, doing it this way won't cause memory problems.
        $deletedAt = new \DateTimeImmutable();
        $objectRepository->createQueryBuilder('e')
            ->update(alias: 'e')
            ->set("e.{$fieldName}", ':deletedAt')
            ->andWhere("e.{$reflProperty->getName()} = :eventObject")
            ->andWhere("e.{$fieldName} IS NULL")
            ->setParameter('eventObject', $eventObject)
            ->setParameter('deletedAt', $deletedAt->format('Y-m-d H:i:s'))
            ->getQuery()
            ->execute()
        ;

        // Grab all the id's that are going to be updated, so we can schedule them for update.
        $objectsAssociatedToEventObject = $objectRepository->createQueryBuilder('e')
            ->select('e.id')
            ->andWhere("e.{$reflProperty->getName()} = :eventObject")
            ->setParameter('eventObject', $eventObject)
            ->getQuery()
            ->iterate()
        ;

        /**
         * @var UnitOfWork $uow
         */
        $uow = $objectManager->getUnitOfWork();
        // Use the getReference() method to fetch a partial object for each entity
        foreach ($objectsAssociatedToEventObject as $row) {
            $id = $row[0];
            $objectProxy = $objectManager->getReference($reflClass->getName(), $id);
            $objectManager->getEventManager()->dispatchEvent(
                GedmoSoftDeleteableListener::PRE_SOFT_DELETE,
                new LifecycleEventArgs($objectProxy, $objectManager)
            );

            $uow->scheduleExtraUpdate($objectProxy, [
                $fieldName => [null, $deletedAt],
            ]);

            $objectManager->getEventManager()->dispatchEvent(
                GedmoSoftDeleteableListener::POST_SOFT_DELETE,
                new LifecycleEventArgs($objectProxy, $objectManager)
            );
        }
    }
}
