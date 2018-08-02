<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RepositoryForms\Form\Type\FieldType;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\FieldType\Author\Author;
use eZ\Publish\Core\FieldType\Author\Value;
use EzSystems\RepositoryForms\Form\Type\FieldType\Author\AuthorCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Type representing ezauthor field type.
 */
class AuthorFieldType extends AbstractType
{
    /** @var \eZ\Publish\API\Repository\Repository */
    private $repository;

    /**
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'ezplatform_fieldtype_ezauthor';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('authors', AuthorCollectionType::class, [])
            ->addViewTransformer($this->getViewTransformer())
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'filterOutEmptyAuthors']);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => Value::class]);
    }

    /**
     * Returns a view transformer which handles empty row needed to display add/remove buttons.
     *
     * @return \Symfony\Component\Form\DataTransformerInterface
     */
    public function getViewTransformer(): DataTransformerInterface
    {
        return new CallbackTransformer(function (Value $value) {
            if (0 === $value->authors->count()) {
                $value->authors->append($this->fetchLoggedAuthor());
            }

            return $value;
        }, function (Value $value) {
            return $value;
        });
    }

    /**
     * @param \Symfony\Component\Form\FormEvent $event
     */
    public function filterOutEmptyAuthors(FormEvent $event)
    {
        $value = $event->getData();

        $value->authors->exchangeArray(
            array_filter(
                $value->authors->getArrayCopy(),
                function (Author $author) {
                    return !empty($author->email) || !empty($author->name);
                }
            )
        );
    }

    /**
     * Returns currently logged user data, or empty Author object if none was found.
     *
     * @return \eZ\Publish\Core\FieldType\Author\Author
     */
    private function fetchLoggedAuthor(): Author
    {
        $author = new Author();

        try {
            $permissionResolver = $this->repository->getPermissionResolver();
            $userService = $this->repository->getUserService();
            $loggedUserId = $permissionResolver->getCurrentUserReference()->getUserId();
            $loggedUserData = $userService->loadUser($loggedUserId);

            $author->name = $loggedUserData->getName();
            $author->email = $loggedUserData->email;
        } catch (NotFoundException $e) {
            //Do nothing
        }

        return $author;
    }
}
