<?php
namespace AE\History\Controller;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use AE\History\Domain\Repository\NodeEventRepository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\View\ViewInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\EventLog\Domain\Model\Event;
use TYPO3\Neos\EventLog\Domain\Model\EventsOnDate;
use TYPO3\TypoScript\View\TypoScriptView;

/**
 * Controller for the history module of Neos, displaying the timeline of changes.
 */
class HistoryController extends AbstractModuleController
{
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
    protected $defaultViewObjectName = TypoScriptView::class;

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
                ->uriFor('Index', ['offset' => $offset + $limit, 'site' => $site], 'History', 'TYPO3.Neos');
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

        $this->view->assignMultiple([
            'eventsByDate' => $eventsByDate,
            'nextPage' => $nextPage,
            'sites' => $sites,
            'site' => $site,
            'node' => $node,
            'firstEvent' => $events[0]
        ]);
    }

    /**
     * Simply sets the TypoScript path pattern on the view.
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->setTypoScriptPathPattern('resource://AE.History/Private/TypoScript');
    }
}
