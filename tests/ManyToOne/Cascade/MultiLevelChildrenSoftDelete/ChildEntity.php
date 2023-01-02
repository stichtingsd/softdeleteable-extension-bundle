<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToOne\Cascade\MultiLevelChildrenSoftDelete;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[onSoftDelete(Type::CASCADE)]
    private ?ParentEntity $parent = null;

    #[ORM\OneToMany(mappedBy: 'child', targetEntity: ChildMetaEntity::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $childMetas;

    public function __construct()
    {
        $this->childMetas = new ArrayCollection();
    }

    public function getParent(): ?ParentEntity
    {
        return $this->parent;
    }

    public function setParent(?ParentEntity $parent): void
    {
        $this->parent = $parent;
    }

    public function getChildMetas()
    {
        return $this->childMetas;
    }

    public function addChildMeta(ChildMetaEntity $childMetaEntity): void
    {
        if (!$this->childMetas->contains($childMetaEntity)) {
            $this->childMetas[] = $childMetaEntity;
            $childMetaEntity->setChild($this);
        }
    }

    public function removeChildMeta(ChildMetaEntity $childMetaEntity): void
    {
        if ($this->childMetas->removeElement($childMetaEntity)) {
            // set the owning side to null (unless already changed)
            if ($childMetaEntity->getChild() === $this) {
                $childMetaEntity->setChild(null);
            }
        }
    }
}
