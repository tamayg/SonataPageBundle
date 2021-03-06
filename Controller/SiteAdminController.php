<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SiteAdminController extends Controller
{
    public function snapshotsAction()
    {
        if (false === $this->admin->isGranted('EDIT')) {
            throw new AccessDeniedException();
        }

        $id = $this->get('request')->get($this->admin->getIdParameter());

        $object = $this->admin->getObject($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
        }

        $this->admin->setSubject($object);

        if ($this->get('request')->getMethod() == "POST") {
            $snapshotManager = $this->get('sonata.page.manager.snapshot');
            $pageManager = $this->get('sonata.page.manager.page');

            $pages = $pageManager->findBy(array(
                'site' => $object->getId(),
                'edited' => true
            ));

            $snapshots = array();
            foreach ($pages as $page) {
                $snapshot = $snapshotManager->create($page);
                $page->setEdited(false);

                $snapshotManager->save($snapshot);
                $pageManager->save($page);

                $snapshots[] = $snapshot;
            }

            $snapshotManager->enableSnapshots($snapshots);

            $this->get('session')->setFlash('sonata_flash_success', $this->admin->trans('flash_snapshots_created_success'));

            return new RedirectResponse($this->admin->generateUrl('edit', array('id' => $object->getId())));
        }

        return $this->render('SonataPageBundle:SiteAdmin:create_snapshots.html.twig', array(
            'action'  => 'snapshots',
            'object'  => $object
        ));
    }
}