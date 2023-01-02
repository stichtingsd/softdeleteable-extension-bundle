<?php

declare(strict_types=1);

namespace StichtingSD\SoftDeleteableExtensionBundle\Tests\ManyToOne\SetNull\MultiLevelChildrenSoftDelete;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Attribute\onSoftDelete;
use StichtingSD\SoftDeleteableExtensionBundle\Mapping\Type;
use StichtingSD\SoftDeleteableExtensionBundle\Tests\BaseEntity;

#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: true)]
class ChildMetaEntity
{
    use BaseEntity;

    #[ORM\ManyToOne(targetEntity: ChildEntity::class, inversedBy: 'childMetas')]
    #[ORM\JoinColumn(nullable: true)]
    #[onSoftDelete(Type::SET_NULL)]
    private ?ChildEntity $child = null;

    public function getChild(): ?ChildEntity
    {
        return $this->child;
    }

    public function setChild(?ChildEntity $child): void
    {
        $this->child = $child;
    }
}
