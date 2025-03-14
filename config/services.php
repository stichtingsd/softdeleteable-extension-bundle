<?php

declare(strict_types=1);

use Doctrine\ORM\Events;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container
        ->services()
        ->set(
            'stichtingsd.soft_deletable_extension.metadata_factory',
            StichtingSD\SoftDeleteableExtensionBundle\Mapping\MetadataFactory::class
        )
        ->arg(0, service('stichtingsd.softdeleteable_extension.cache'))
    ;

    $container
        ->services()
        ->set(
            'stichtingsd.soft_deletable_extension.subscriber',
            StichtingSD\SoftDeleteableExtensionBundle\EventListener\OnSoftDeleteEventSubscriber::class,
        )
        ->arg(0, service('stichtingsd.soft_deletable_extension.metadata_factory'))
        ->tag('doctrine.event_listener', ['event' => SoftDeleteableListener::PRE_SOFT_DELETE])
    ;

    $container
        ->services()
        ->set(
            'stichtingsd.soft_deletable_extension.validator',
            StichtingSD\SoftDeleteableExtensionBundle\EventListener\OnSoftDeleteValidatorEventSubscriber::class,
        )
        ->arg(0, service('stichtingsd.soft_deletable_extension.metadata_factory'))
        ->tag('doctrine.event_listener', ['event' => Events::loadClassMetadata])
    ;
};
