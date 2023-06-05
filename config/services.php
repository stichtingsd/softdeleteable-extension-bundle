<?php

declare(strict_types=1);

use Doctrine\ORM\Events;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/* @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
$container
    ->setDefinition(
        'stichtingsd.soft_deletable_extension.metadata_factory',
        new Definition('StichtingSD\SoftDeleteableExtensionBundle\Mapping\MetadataFactory')
    )
    ->addArgument(new Reference('stichtingsd.softdeleteable_extension.cache'))
;

/* @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
$container
    ->setDefinition(
        'stichtingsd.soft_deletable_extension.subscriber',
        new Definition('StichtingSD\SoftDeleteableExtensionBundle\EventListener\OnSoftDeleteEventSubscriber')
    )
    ->addMethodCall('setContainer', [
        new Reference('service_container'),
    ])
    ->addArgument(new Reference('stichtingsd.soft_deletable_extension.metadata_factory'))
    ->addTag('doctrine.event_listener', ['event' => SoftDeleteableListener::PRE_SOFT_DELETE])
;

/* @var \Symfony\Component\DependencyInjection\ContainerBuilder $container */
$container
    ->setDefinition(
        'stichtingsd.soft_deletable_extension.validator',
        new Definition('StichtingSD\SoftDeleteableExtensionBundle\EventListener\OnSoftDeleteValidatorEventSubscriber')
    )
    ->addMethodCall('setContainer', [
        new Reference('service_container'),
    ])
    ->addArgument(new Reference('stichtingsd.soft_deletable_extension.metadata_factory'))
    ->addTag('doctrine.event_listener', ['event' => Events::loadClassMetadata])
;