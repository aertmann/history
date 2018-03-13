<?php
namespace AE\History\Controller;

use AE\History\Domain\Repository\NodeEventRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Security\Context;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\EventLog\Domain\Model\Event;
use Neos\Neos\EventLog\Domain\Model\EventsOnDate;
use Neos\Fusion\View\FusionView;

/**
 * Controller for the history module of Neos, displaying the timeline of changes.
 */
class HistoryController extends AbstractModuleController
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var NodeEventRepository
     */
    protected $nodeEventRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @var string
     */
    protected $defaultViewObjectName = FusionView::class;

    /**
     * Show event overview.
     *
     * @param integer $offset
     * @param integer $limit
     * @param string $site
     * @param string $node
     * @return void
     */
    public function indexAction($offset = 0, $limit = 25, $site = null, $node = null)
    {
        $numberOfSites = 0;
        // In case a user can only access a single site, but more sites exists
        $this->securityContext->withoutAuthorizationChecks(function () use(&$numberOfSites) {
            $numberOfSites = $this->siteRepository->countAll();
        });
        $sites = $this->siteRepository->findOnline();
        if ($numberOfSites > 1 && $site === null) {
            $domain = $this->domainRepository->findOneByActiveRequest();
            // Set active asset collection to the current site's asset collection, if it has one, on the first view if a matching domain is found
            if ($domain !== null && $domain->getSite()) {
                $site = $this->persistenceManager->getIdentifierByObject($domain->getSite());
            }
        }
        $events = $this->nodeEventRepository->findRelevantEventsByWorkspace($offset, $limit + 1, 'live', $site, $node)->toArray();

        $nextPage = null;
        if (count($events) > $limit) {
            $events = array_slice($events, 0, $limit);

            $nextPage = $this
                ->controllerContext
                ->getUriBuilder()
                ->setCreateAbsoluteUri(true)
                ->uriFor('Index', ['offset' => $offset + $limit, 'site' => $site], 'History', 'Neos.Neos');
        }

        $eventsByDate = array();
        foreach ($events as $event) {
            if ($event->getChildEvents()->count() === 0) {
                continue;
            }
            /* @var $event Event */
            $day = $event->getTimestamp()->format('Y-m-d');
            if (!isset($eventsByDate[$day])) {
                $eventsByDate[$day] = new EventsOnDate($event->getTimestamp());
            }

            /* @var $eventsOnThisDay EventsOnDate */
            $eventsOnThisDay = $eventsByDate[$day];
            $eventsOnThisDay->add($event);
        }

        $firstEvent = current($events);
        if (!$firstEvent) {
            $contentContext = $this->createContentContext('live');
            $actualNode = $contentContext->getNodeByIdentifier($node);
            if ($actualNode) {
              $firstEvent = [
                'nodeIdentifier' => $node,
                'node' => $actualNode,
                'data' => ['documentNodeType' => $actualNode->getNodeType()->getName(), 'documentNodeLabel' => $actualNode->getLabel()]
              ];
            }
        }

        $this->view->assignMultiple([
            'eventsByDate' => $eventsByDate,
            'nextPage' => $nextPage,
            'sites' => $sites,
            'site' => $site,
            'node' => $node,
            'firstEvent' => $firstEvent
        ]);
    }

    /**
     * Simply sets the Fusion path pattern on the view.
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->setFusionPathPattern('resource://AE.History/Private/Fusion');
    }
}
