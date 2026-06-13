<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * CRUD-контроллер для управления пользователями.
 *
 * Позволяет создавать, редактировать, удалять пользователей,
 * управлять ролями, а также безопасно сбрасывать пароль.
 */
final class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public static function getEntityFqcn(): string
    {
        return User::class;
    }// end getEntityFqcn()

    /**
     * @inheritDoc
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50);
    }// end configureCrud()

    /**
     * @inheritDoc
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('username');

        // Пароль показываем только на форме создания; для редактирования – отдельное действие.
        $passwordField = TextField::new('plainPassword')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions(
                [
                    'type'           => PasswordType::class,
                    'first_options'  => ['label' => 'entities.' . $this->getEntityFqcn() . '.properties.first'],
                    'second_options' => ['label' => 'entities.' . $this->getEntityFqcn() . '.properties.second'],
                    'mapped'         => false,
                    'required'       => $pageName === Crud::PAGE_NEW,
                ]
            )
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms();

        yield $passwordField;

        yield ChoiceField::new('roles')
            ->setChoices(
                [
                    'entities.' . $this->getEntityFqcn() . '.role.user'  => 'ROLE_USER',
                    'entities.' . $this->getEntityFqcn() . '.role.admin' => 'ROLE_ADMIN',
                ]
            )
            ->allowMultipleChoices()
            ->renderExpanded(false);

        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('updatedAt')->hideOnForm();
    }// end configureFields()

    /**
     * @inheritDoc
     */
    public function configureActions(Actions $actions): Actions
    {
        // Кастомное действие сброса пароля.
        $resetPassword = Action::new('resetPassword')
            ->setIcon('fas fa-key')
            ->linkToCrudAction('resetPassword');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $resetPassword)
            ->add(Crud::PAGE_INDEX, $resetPassword);
    }// end configureActions()

    /**
     * @inheritDoc
     *
     * Добавляем обработчик для хеширования пароля перед сохранением.
     */
    public function createEditFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormBuilderInterface {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        $this->addPasswordEventListener($formBuilder);

        return $formBuilder;
    }// end createEditFormBuilder()

    /**
     * @inheritDoc
     */
    public function createNewFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormBuilderInterface {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        $this->addPasswordEventListener($formBuilder);

        return $formBuilder;
    }// end createNewFormBuilder()

    /**
     * Добавляет слушатель события формы для автоматического хеширования пароля.
     */
    private function addPasswordEventListener(FormBuilderInterface $formBuilder): void
    {
        $formBuilder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $user = $event->getData();
                $form = $event->getForm();

                $plainPassword = $form->get('plainPassword')->getData();
                if ($plainPassword) {
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }
            }
        );
    }// end addPasswordEventListener()

    /**
     * Кастомное действие для сброса пароля пользователя.
     *
     * GET: отображает форму с новым паролем.
     * POST: обновляет пароль и перенаправляет на список.
     */
    #[AdminRoute]
    public function resetPassword(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add(
                'newPassword',
                RepeatedType::class,
                [
                    'type'           => PasswordType::class,
                    'first_options'  => ['label' => 'Новый пароль'],
                    'second_options' => ['label' => 'Повторите пароль'],
                    'mapped'         => false,
                ]
            )
            ->getForm();

        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $this->entityManager->flush();

            $this->addFlash('success', 'Пароль успешно изменён.');

            return $this->redirect(
                $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl()
            );
        }

        return $this->render(
            'user/reset_password.html.twig',
            [
                'form' => $form->createView(),
                'user' => $user,
            ]
        );
    }// end resetPassword()

    /**
     * @inheritDoc
     */
    public function persistEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        $user = $entityInstance;
        // Если пароль не был задан (например, при создании), можно выбросить исключение.
        if (null === $user->getPassword()) {
            throw new \RuntimeException('Пароль обязателен для нового пользователя.');
        }

        parent::persistEntity($entityManager, $user);
    }// end persistEntity()
}// end class
