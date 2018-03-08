<?php
namespace AE\History\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\EntityNotFoundException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 *
 */
class AssetExistsViewHelper extends AbstractViewHelper
{

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Checks if an asset exists.
     *
     * @param AssetInterface $asset
     * @return string
     */
    public function render(AssetInterface $asset)
    {
        try {
            $asset->getResource();
        } catch(EntityNotFoundException $e) {
            return '';
        }
        return $this->renderChildren();
    }
}
