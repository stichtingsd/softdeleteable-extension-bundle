# SoftDeleteableExtensionBundle

## Warning: This bundle only works with Symfony ^6.4 || ^7.2 and PHP ^8.3
<hr />

This bundle is created to handle soft deletes for associated entities/documents in addition to the functionality of Gedmo SoftDelete.
Gedmo does not handle CASCADING relations for example. Doctrine doesn't also since it's not actually deleted.

The bundle also handles deletion of large entities very well thanks to Doctrine DQL and Proxy objects.
Entities that have 10.000+ relations are updated in a split second.

## Prerequisites

**This bundle requires Symfony ^6.4 || ^7.2 or higher and PHP ^8.3 or higher**
- Symfony ^6.4 || ^7.2
- PHP ^8.4
- gedmo/doctrine-extensions ^3.16

## Installation

Add stichtingsd/soft-deleteable-extension-bundle to your `composer.json` file:

```
composer require "stichtingsd/soft-deleteable-extension-bundle"
```

### Bundle registration (Skip when using Symfony Flex)

Register bundle into config/bundles.php (Flex does it automatically):

``` php
# config/bundles.php

return [
    ...
    new StichtingSD\SoftDeleteableExtensionBundle\StichtingSDSoftDeleteableExtensionBundle(),
];
```

## Getting started

**Cascade soft deletedelete the entity**

To (soft-)delete an entity when its parent record is soft-deleted:

```
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute as StichtingSD;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;

...

#[StichtingSD\onSoftDelete(type: Type::CASCADE)]
```

Set reference to null (instead of deleting the entity)

```
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute as StichtingSD;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;

...

#[StichtingSD\onSoftDelete(type: Type::SET_NULL)]
```

### Supported association types:
- One-To-One
  - Type::CASCADE
  - Type::SET_NULL
- Many-To-One
    - Type::CASCADE
    - Type::SET_NULL
- Many-To-Many
    - Type::REMOVE_ASSOCIATION_ONLY

#### Type::CASCADE
This behaves like the SQL CASCADE. All relations that have a soft deleteable field from Gedmo will be deleted also.

#### Type::SET_NULL
This behaves like the SQL SET_NULL. The relation will be set to NULL.
<br />
_Caution: Your field must be `nullable: true` to allow null values._

#### Type::REMOVE_ASSOCIATION_ONLY
This will remove the association only. Associated entities them self are not deleted since they can have other associations.

### Full example Many-To-One

``` php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute as StichtingSD;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use App\Entity\Tenant;

#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class User
{
    ...

    #[StichtingSD\onSoftDelete(type: Type::CASCADE)]
    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tenant $tenant;

    ...
}
```

### Full example Many-To-Many (mapped side)

``` php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Tenant;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute as StichtingSD;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;

#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class User
{
    ...

    #[StichtingSD\onSoftDelete(type: Type::REMOVE_ASSOCIATION_ONLY)]
    #[ORM\ManyToMany(targetEntity: Tenant::class, inversedBy: 'users')]
    private Collection $tenants;

    ...
}
```

### Full example Many-To-Many (inversed side)

``` php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute as StichtingSD;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;

#[ORM\Entity(repositoryClass: 'App\Repository\TenantRepository')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class Tenant
{
    ...

    #[StichtingSD\onSoftDelete(type: Type::REMOVE_ASSOCIATION_ONLY)]
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'tenants')]
    private Collection $users;

    ...
}
```

## Limitations

### Many-To-Many
- Currently, it's only possible to use the `Type::REMOVE_ASSOCIATION_ONLY`.
### One-To-Many
- Not supported, and won't be. Define it on the Many-To-One side.
