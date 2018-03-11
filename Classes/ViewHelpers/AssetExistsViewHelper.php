<?php
namespace AE\History\ViewHelpers;

use Doctrine\ORM\EntityNotFoundException;
use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Media\Domain\Model\AssetInterface;

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
        } catch (EntityNotFoundException $e) {
            return '';
        }
        return $this->renderChildren();
    }
}
