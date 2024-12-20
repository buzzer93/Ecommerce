<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'attr' => [
                    'placeholder' => 'Saisissez votre nouveau mot de passe',
                    'class' => 'form-control'
                ]
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirmer votre mot de passe',
                'attr' => [
                    'placeholder' => 'Confirmez votre mot de passe',
                    'class' => 'form-control'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'class' => 'btn btn-primary my-3'
                ]
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                if (!$this->passwordMatch($data['password'], $data['confirm_password'])) {
                    $form->get('confirm_password')->addError(new FormError('Les mots de passe ne sont pas identiques'));
                }
            })

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
    public function passwordMatch($password, $confirm_password): bool
    {
        if ($password === $confirm_password) {
            return true;
        }
        return false;
    }
}
