<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\RemoveAssociationOnly\SimpleMapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseEntity;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\OneToMany\Cascade\SimpleMapping\ChildEntity;

#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: true)]
class ParentEntity
{
    use BaseEntity;

    #[onSoftDelete(Type::REMOVE_ASSOCIATION_ONLY)]
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: ChildEntity::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(ChildEntity $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }
    }

    public function removeChild(ChildEntity $childEntity): void
    {
        if ($this->children->removeElement($childEntity)) {
            // set the owning side to null (unless already changed)
            if ($childEntity->getParent() === $this) {
                $childEntity->setParent(null);
            }
        }
    }
}
