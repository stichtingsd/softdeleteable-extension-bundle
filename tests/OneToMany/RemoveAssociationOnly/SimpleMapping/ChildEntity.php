<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\RemoveAssociationOnly\SimpleMapping;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseEntity;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\Cascade\SimpleMapping\ParentEntity;

#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: true)]
class ChildEntity
{
    use BaseEntity;

    #[ORM\ManyToOne(targetEntity: ParentEntity::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
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
