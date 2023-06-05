<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\EventListener;

use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\MetadataFactory;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OnSoftDeleteValidatorEventSubscriber
{
    use ContainerAwareTrait;

    public function __construct(
        private MetadataFactory $metadataFactory
    ) {
    }

    public function loadClassMetaData(LoadClassMetadataEventArgs $args): void
    {
        $this->metadataFactory->computeSingleClassMetadata($args->getClassMetadata());
    }
}
