<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToOne\SetNull\SimpleMapping;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseEntity;

#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: true)]
class ChildEntity
{
    use BaseEntity;

    #[ORM\ManyToOne(targetEntity: ParentEntity::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
    #[onSoftDelete(Type::SET_NULL)]
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
