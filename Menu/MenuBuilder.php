<?php

namespace Sonata\PageBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Model\PageInterface;

/**
 * The MenuBuilder builds up a (knp) menu based on page/snapshot entities where the 'showInMenu' field is enabled.
 * The menu is provied by a service, see /Resources/config/menu.xml
 *
 * @author     Tamay Gündüz <coding@tamaygunduz.de>
 */
class MenuBuilder
{
    private $factory;
    protected $pageManager;
    protected $container;
    protected $cms;

    /**
     * @param \Knp\Menu\FactoryInterface $factory
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct($factory, $container)
    {
        $this->factory = $factory;
        $this->container = $container;
        $this->cms = $this->getCmsManager();
        $this->pageManager = $this->getPageManager();
    }

    /**
     * @return \Sonata\PageBundle\CmsManager\CmsManagerInterface
     */
    protected function getCmsManager()
    {
        return $this->container->get('sonata.page.cms_manager_selector')->retrieve();
    }

    /**
     * @return \Sonata\PageBundle\CmsManager\CmsManagerInterface
     */
    public function getPageManager()
    {
       // return $this->container->get('sonata.page.manager.snapshot');

        $securityContext = $this->container->get('security.context');

        if ($securityContext->getToken() !== null && $securityContext->isGranted('ROLE_SONATA_PAGE_ADMIN_PAGE_EDIT')) {
            $manager = $this->container->get('sonata.page.manager.page');
        } else {
            $manager = $this->container->get('sonata.page.manager.snapshot');
        }

        return $manager;
    }


    /**
     * Build up the SiteMainMenu based on pages where the "showInMenu" field is enabled
     * @return \Knp\Menu\ItemInterface
     */
    public function createSiteMainMenu()
    {
        $menu = $this->factory->createItem('root');
        $menu->setCurrentUri($this->cms->getCurrentPage()->getUrl());
        $site = $this->cms->getCurrentPage()->getSite();

        $result = $this->pageManager->findBy(
            array(
                'site' => $site->getId(),
                'enabled' => true,
                'showInMenu' => true,
                // this is part of a workaround which should be fixed when the issue with the snapshotmanager gets fixed
                (get_class($this->pageManager) == 'Sonata\PageBundle\Entity\PageManager') ? 'parent' : 'parentId' => null,
            )
        );


        foreach ($result as $_page) {
            // this is part of a workaround which should be fixed when the issue with the snapshotmanager gets fixed
            if (get_class($this->pageManager) == 'Sonata\PageBundle\Entity\SnapshotManager') {
                /** @var $_page \Sonata\PageBundle\Model\SnapshotPageProxy */
                $_page = $_page->getPage();
            }
            /** @var $_page \Sonata\PageBundle\Model\PageInterface */
                if ($_page->getEnabled() == 'true') {

                   //$page = $this->cms->getPageByUrl($site, $_page->getUrl());

                    $menu->addChild(
                        $this->parsePageToMenuItem($_page)
                    );
                }
        }
        return $menu;
    }

    /**
     * Parse a page and it childs to menu items
     * @param \Sonata\PageBundle\Model\PageInterface $page
     * @return \Knp\Menu\ItemInterface
     */
    private function parsePageToMenuItem($page) {

            $menu = $this->factory->createItem(
                $page->getName(),
                array(
                    'uri' => $page->getUrl(),
                )
            );

            foreach($page->getChildren() as $_childpage) {
                if ($_childpage->getShowInMenu() == true) {
                    $menu->addChild(
                        $this->parsePageToMenuItem($_childpage)
                    );
                }
            }

            return $menu;
    }
}