<?php

namespace Bigfoot\Bundle\SeoBundle\Controller;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Bigfoot\Bundle\SeoBundle\Entity\MetadataParameter;
use Bigfoot\Bundle\SeoBundle\Form\MetadataParameterType;
use Bigfoot\Bundle\SeoBundle\Entity\Parameter;
use Bigfoot\Bundle\CoreBundle\Theme\Menu\Item;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * MetadataParameter controller.
 *
 * @Cache(maxage="0", smaxage="0", public="false")
 * @Route("/admin/parameter/metadataparameter")
 */
class MetadataParameterController implements ContainerAwareInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Lists all MetadataParameter entities.
     *
     * @Route("/", name="admin_parameter_metadataparameter")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->container->get('doctrine')->getManager();

        $entities = $em->getRepository('BigfootSeoBundle:MetadataParameter')->findAll();

        $theme = $this->container->get('bigfoot.theme');
        $theme['page_content']['globalActions']->addItem(new Item('crud_add', 'Add a metadata parameter', 'admin_parameter_metadataparameter_new'));
  /*
        Id' | trans }}</th>
                <th>{{ 'Route' | trans }}</th>
                <th>{{ 'Parameters
*/
        return array(
            'list_title'    => 'MetadataParameter list',
            'list_items'    => $entities,
            'list_fields'   => array(
                'id' => 'ID',
                'route' => 'Route',
                'parameters' => 'Parameters',
            ),
        );
    }

    /**
     * Creates a new MetadataParameter entity.
     *
     * @Route("/", name="admin_parameter_metadataparameter_create")
     * @Method("POST")
     * @Template("BigfootSeoBundle:MetadataParameter:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new MetadataParameter();
        $form = $this->container->get('form.factory')->create('metadataparameter', $entity);
        $form->submit($request);

        if ($form->isValid()) {

            $post = $request->request->get('metadataparameter');

            $route = $post['route'];

            $em = $this->container->get('doctrine')->getManager();

            $uniqueRoute = $em->getRepository('BigfootSeoBundle:MetadataParameter')->findOneBy(array('route' => $route));

            if ($uniqueRoute) {

                $form->get('route')->addError(new FormError('Des paramètres ont déjà été créés pour cette route'));

                return array(
                    'entity' => $entity,
                    'form'   => $form->createView(),
                );
            }

            $em = $this->container->get('doctrine')->getManager();
            $em->persist($entity);
            $em->flush();

            return new RedirectResponse($this->container->get('router')->generate('admin_parameter_metadataparameter'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new MetadataParameter entity.
     *
     * @Route("/new", name="admin_parameter_metadataparameter_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new MetadataParameter();

        $form   = $this->container->get('form.factory')->create('metadataparameter', $entity);

        return array(
            'form'              => $form->createView(),
            'form_action'       => $this->container->get('router')->generate('admin_parameter_metadataparameter_create', array('id' => $entity->getId())),
            'form_method'       => 'POST',
            'form_title'        => 'MetadataParameter creation',
            'form_cancel_route' => 'admin_parameter_metadataparameter',
        );
    }

    /**
     * Displays a form to edit an existing MetadataParameter entity.
     *
     * @Route("/{id}/edit", name="admin_parameter_metadataparameter_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id, Request $request)
    {
        $em = $this->container->get('doctrine')->getManager();

        $entity = $em->getRepository('BigfootSeoBundle:MetadataParameter')->find($id);

        if (!$entity) {
            throw new NotFoundHttpException('Unable to find MetadataParameter entity.');
        }

        $originalParameters = array();

        // Crée un tableau contenant les objets Tag courants de la
        // base de données
        foreach ($entity->getParameters() as $parameter) $originalParameters[] = $parameter;

        $editForm = $this->container->get('form.factory')->create('metadataparameter', $entity);

        if ($request->isMethod('POST')) {
            $editForm->submit($this->getRequest());

            if ($editForm->isValid()) {

                // filtre $originalTags pour ne contenir que les tags
                // n'étant plus présents
                foreach ($entity->getParameters() as $parameter) {
                    foreach ($originalParameters as $key => $toDel) {
                        if ($toDel->getId() === $parameter->getId()) {
                            unset($originalParameters[$key]);
                        }
                    }
                }

                // supprime la relation entre le tag et la « Task »
                foreach ($originalParameters as $parameter) {
                    // supprime la « Task » du Tag
                    $parameter->getMetadataParameters()->removeElement($entity);

                    // si c'était une relation ManyToOne, vous pourriez supprimer la
                    // relation comme ceci
                    // $tag->setTask(null);

                    $em->persist($parameter);

                    // si vous souhaitiez supprimer totalement le Tag, vous pourriez
                    // aussi faire comme cela
                    // $em->remove($tag);
                }

                $em->persist($entity);
                $em->flush();

                // redirige vers quelconque page d'édition
                return new RedirectResponse($this->container->get('router')->generate('metadataparameter_edit', array('id' => $id)));
            }
        }

        $editForm = $this->container->get('form.factory')->create('metadataparameter', $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing MetadataParameter entity.
     *
     * @Route("/{id}", name="admin_parameter_metadataparameter_update")
     * @Method("PUT")
     * @Template("BigfootSeoBundle:MetadataParameter:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->container->get('doctrine')->getManager();

        $entity = $em->getRepository('BigfootSeoBundle:MetadataParameter')->find($id);

        if (!$entity) {
            throw new NotFoundHttpException('Unable to find MetadataParameter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->container->get('form.factory')->create('metadataparameter', $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return new RedirectResponse($this->container->get('router')->generate('admin_parameter_metadataparameter_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a MetadataParameter entity.
     *
     * @Route("/delete/{id}", name="admin_parameter_metadataparameter_delete")
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $form = $this->createDeleteForm($id);
        $form->submit($request);

        $em = $this->container->get('doctrine')->getManager();
        $entity = $em->getRepository('BigfootSeoBundle:MetadataParameter')->find($id);

        if (!$entity) {
            throw new NotFoundHttpException('Unable to find MetadataParameter entity.');
        }

        $em->remove($entity);
        $em->flush();

        return new RedirectResponse($this->container->get('router')->generate('admin_parameter_metadataparameter'));
    }

    /**
     * Creates a form to delete a MetadataParameter entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->container->get('form.factory')->createBuilder('form', array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
