<?php
namespace AE\History\ViewHelpers;

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;

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
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('nodeType', 'string', 'NodeType', true);
    }

    /**
     * @return string
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function render() : string
    {
        $nodeType = $this->arguments['nodeType'];

        return $this->nodeTypeManager->getNodeType($nodeType)->getConfiguration('ui.icon');
    }
}
