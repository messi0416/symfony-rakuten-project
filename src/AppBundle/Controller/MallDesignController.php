<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\MallDesign;
use AppBundle\Form\Type\MallDesignType;

/**
 * モールデザイン管理画面
 *
 * @Route("/malldesign")
 */
class MallDesignController extends Controller
{

    /**
     * Lists all MallDesign entities.
     *
     * @Route("/", name="malldesign")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:MallDesign')->findBy([], ['name' => 'ASC']);

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new MallDesign entity.
     *
     * @Route("/", name="malldesign_create")
     * @Method("POST")
     * @Template("AppBundle:MallDesign:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new MallDesign();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            // リダイレクト先を malldesign に修正
            // return $this->redirect($this->generateUrl('malldesign_show', array('id' => $entity->getId())));
            return $this->redirectToRoute('malldesign');
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a MallDesign entity.
     *
     * @param MallDesign $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(MallDesign $entity)
    {
        $form = $this->createForm(new MallDesignType(), $entity, array(
            'action' => $this->generateUrl('malldesign_create'),
            'method' => 'POST',
        ));

        return $form;
    }

    /**
     * Displays a form to create a new MallDesign entity.
     *
     * @Route("/new", name="malldesign_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new MallDesign();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to edit an existing MallDesign entity.
     *
     * @Route("/{id}/edit", name="malldesign_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MallDesign')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MallDesign entity.');
        }

        $editForm = $this->createEditForm($entity);
        // $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            // 'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a MallDesign entity.
    *
    * @param MallDesign $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(MallDesign $entity)
    {
        $form = $this->createForm(new MallDesignType(), $entity, array(
            'action' => $this->generateUrl('malldesign_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));
        return $form;
    }
    /**
     * Edits an existing MallDesign entity.
     *
     * @Route("/{id}", name="malldesign_update")
     * @Method("PUT")
     * @Template("AppBundle:MallDesign:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:MallDesign')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MallDesign entity.');
        }

        // $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            // リダイレクト先を malldesign に修正
            // return $this->redirect($this->generateUrl('malldesign_edit', array('id' => $id)));
            return $this->redirectToRoute('malldesign');
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            // 'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a MallDesign entity.
     *
     * @Route("/{id}", name="malldesign_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Malldesign $malldesign)
    {
        // $form = $this->createDeleteForm($id);
        // $form->handleRequest($request);

        // if ($form->isValid()) {
        //     $em = $this->getDoctrine()->getManager();
        //     $entity = $em->getRepository('AppBundle:MallDesign')->find($id);

        //     if (!$entity) {
        //         throw $this->createNotFoundException('Unable to find MallDesign entity.');
        //     }

        //     $em->remove($entity);
        //     $em->flush();
        // }

        if ($this->isCsrfTokenValid('delete_malldesign', $request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($malldesign);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('malldesign'));
    }

    // /**
    //  * Creates a form to delete a MallDesign entity by id.
    //  *
    //  * @param mixed $id The entity id
    //  *
    //  * @return \Symfony\Component\Form\Form The form
    //  */
    // private function createDeleteForm($id)
    // {
    //     return $this->createFormBuilder()
    //         ->setAction($this->generateUrl('malldesign_delete', array('id' => $id)))
    //         ->setMethod('DELETE')
    //         ->add('submit', 'submit', array('label' => 'Delete'))
    //         ->getForm()
    //     ;
    // }
}
