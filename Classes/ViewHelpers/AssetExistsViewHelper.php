<?php
namespace AE\History\ViewHelpers;

use Doctrine\ORM\EntityNotFoundException;
use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Media\Domain\Model\AssetInterface;

class AssetExistsViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('asset', AssetInterface::class, 'AssetInterface', true);
    }

    /**
     * Checks if an asset exists.
     *
     * @return string
     */
    public function render() : string
    {
        $asset = $this->arguments['asset'];

        try {
            $asset->getResource();
        } catch (EntityNotFoundException $e) {
            return '';
        }

        return $this->renderChildren();
    }
}
