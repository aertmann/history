<?php
namespace AE\History\Controller;

use AE\History\Domain\Repository\NodeEventRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\AccountRepository;
use Neos\Flow\Security\Context;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\EventLog\Domain\Model\EventsOnDate;
use Neos\Neos\EventLog\Domain\Model\NodeEvent;

/**
 * Controller for the history module of Neos, displaying the timeline of changes.
 */
class HistoryController extends AbstractModuleController
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var NodeEventRepository
     */
    protected $nodeEventRepository;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * Show event overview.
     *
     * @param int $offset
     * @param int $limit
     * @param string $site
     * @param string $node
     * @param string $account
     *
     * @return void
     */
    public function indexAction(
        int $offset = 0,
        int $limit = 25,
        string $site = null,
        string $node = null,
        string $account = null
    ) {
        $numberOfSites = 0;
        // In case a user can only access a single site, but more sites exists
        $this->securityContext->withoutAuthorizationChecks(function () use (&$numberOfSites) {
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

        /** @var Account[] $accounts */
        $accounts = $this->accountRepository->findByAuthenticationProviderName('Neos.Neos:Backend')->toArray();

        /** @var NodeEvent[] $events */
        $events = $this->nodeEventRepository
            ->findRelevantEventsByWorkspace($offset, $limit + 1, 'live', $site, $node, $account)
            ->toArray()
        ;

        $nextPage = null;
        if (count($events) > $limit) {
            $events = array_slice($events, 0, $limit);

            $nextPage = $this->controllerContext
                ->getUriBuilder()
                ->setCreateAbsoluteUri(true)
                ->uriFor(
                    'Index',
                    ['offset' => $offset + $limit, 'site' => $site],
                    'History',
                    'Neos.Neos'
                )
            ;
        }

        /** @var EventsOnDate[] $eventsByDate */
        $eventsByDate = [];
        foreach ($events as $event) {
            if ($event->getChildEvents()->count() === 0) {
                continue;
            }
            $timestamp = $event->getTimestamp();
            $day = $timestamp->format('Y-m-d');
            if (!isset($eventsByDate[$day])) {
                $eventsByDate[$day] = new EventsOnDate($timestamp);
            }

            $eventsOnThisDay = $eventsByDate[$day];
            $eventsOnThisDay->add($event);
        }

        $firstEvent = current($events);
        if ($firstEvent === false) {
            $actualNode = $this->createContentContext('live')->getNodeByIdentifier($node);
            if ($actualNode !== null) {
                $firstEvent = [
                    'data' => [
                        'documentNodeLabel' => $actualNode->getLabel(),
                        'documentNodeType' => $actualNode->getNodeType()->getName(),
                    ],
                    'node' => $actualNode,
                    'nodeIdentifier' => $node,
                ];
            }
        }

        $this->view->assignMultiple([
            'account' => $account,
            'accounts' => $accounts,
            'eventsByDate' => $eventsByDate,
            'firstEvent' => $firstEvent,
            'nextPage' => $nextPage,
            'node' => $node,
            'site' => $site,
            'sites' => $sites,
        ]);
    }

    /**
     * Simply sets the Fusion path pattern on the view.
     *
     * @param ViewInterface $view
     *
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->setFusionPathPattern('resource://AE.History/Private/Fusion');
    }
}
