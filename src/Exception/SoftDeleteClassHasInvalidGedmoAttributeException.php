<?php

declare(strict_types=1);

/*
 * This file is a part of the StichtingSD/SoftDeleteableExtensionBundle
 * (c) StichtingSD <info@stichtingsd.nl> https://stichtingsd.nl
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StichtingSD\SoftDeleteableExtensionBundle\Exception;

class SoftDeleteClassHasInvalidGedmoAttributeException extends \Exception implements SoftDeleteBundleExceptionInterface
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public function addMessage(string $message): self
    {
        $this->message .= $message;

        return $this;
    }
}
