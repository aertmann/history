<?php
namespace AE\History\ViewHelpers;

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

/**
 *
 */
class NodeTypeIconViewHelper extends AbstractViewHelper
{

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @param string $nodeType
     * @return string
     */
    public function render($nodeType)
    {
        return $this->nodeTypeManager->getNodeType($nodeType)->getConfiguration('ui.icon');
    }
}
