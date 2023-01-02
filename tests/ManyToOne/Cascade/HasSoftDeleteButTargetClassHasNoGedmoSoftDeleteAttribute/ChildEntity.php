<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToOne\Cascade\HasSoftDeleteButTargetClassHasNoGedmoSoftDeleteAttribute;

use Doctrine\ORM\Mapping as ORM;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseEntity;

#[ORM\Entity]
class ChildEntity
{
    use BaseEntity;

    #[ORM\ManyToOne(targetEntity: ParentEntity::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
    #[onSoftDelete(Type::CASCADE)]
    private ?ParentEntity $parent = null;

    public function getParent(): ?ParentEntity
    {
        return $this->parent;
    }

    public function setParent(?ParentEntity $parent): void
    {
        $this->parent = $parent;
    }
}
