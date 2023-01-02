<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\RemoveAssociationOnly\AttributeDefinedOnInversedSide;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseEntity;

#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: true)]
class MappedEntity
{
    use BaseEntity;

    #[ORM\ManyToMany(targetEntity: InversedEntity::class, inversedBy: 'parents')]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(InversedEntity $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->addParent($this);
        }
    }

    public function removeChild(InversedEntity $childEntity): void
    {
        if ($this->children->contains($childEntity)) {
            $this->children->removeElement($childEntity);
            $childEntity->removeParent($this);
        }
    }
}
