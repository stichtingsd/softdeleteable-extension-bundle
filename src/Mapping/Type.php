<?php

declare(strict_types=1);

/*
 * This file is a part of the StichtingSD/SoftDeleteableExtensionBundle
 * (c) StichtingSD <info@stichtingsd.nl> https://stichtingsd.nl
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StichtingSD\SoftDeleteableExtensionBundle\Mapping;

enum Type: string
{
    case SET_NULL = 'SET_NULL';
    case CASCADE = 'CASCADE';
    case REMOVE_ASSOCIATION_ONLY = 'REMOVE_ASSOCIATION_ONLY';
}
