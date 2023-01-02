<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToMany\Cascade\MappedSide;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseEntity;

#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: true)]
class InversedEntity
{
    use BaseEntity;

    #[ORM\ManyToMany(targetEntity: MappedEntity::class, mappedBy: 'children')]
    #[onSoftDelete(Type::CASCADE)]
    private Collection $parents;

    public function __construct()
    {
        $this->parents = new ArrayCollection();
    }

    public function getParents()
    {
        return $this->parents;
    }

    public function addParent(MappedEntity $parent): void
    {
        if (!$this->parents->contains($parent)) {
            $this->parents[] = $parent;
        }
    }

    public function removeParent(MappedEntity $parent): void
    {
        if ($this->parents->contains($parent)) {
            $this->parents->removeElement($parent);
        }
    }
}
