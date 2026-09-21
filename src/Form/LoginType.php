<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emailAddress', EmailType::class, [
                'label' => ' ',
                'constraints' => [
                    new Assert\NotBlank(message: 'Saisissez votre addresse e-mail.'),
                    new Assert\Email(message:'Votre adresse e-mail n\'est pas une adresse validel.'),
                    new Assert\Regex(
                        pattern: '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
                        message: 'Veuillez saisir une adresse e-mail valide.'
                    ),
                ],
                'data' => $options['email'],
                'attr' => [
                    'class' => 'h-[50px] w-full rounded-full border border-slate-300 bg-white px-5 text-[16px] text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-nexo-600 focus:ring-4 focus:ring-nexo-600/10',
                    'autocomplete' => 'email',
                    'placeholder' => '',
                ],
            ])
             ->add('password', PasswordType::class, [
                'label' => ' ',
                'constraints' => [
                    new Assert\NotBlank(message: 'Saisissez votre mot de passe.'), 
                ],
                'data' => $options['password'],
                'attr' => [
                    'class' => 'h-[54px] w-full rounded-full border border-slate-300 bg-white px-5 pr-14 text-[16px] text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-nexo-600 focus:ring-4 focus:ring-nexo-600/10',
                    'autocomplete' => 'current-password',
                    'placeholder' => '',
                    'required' => true
                ],
            ])
            ->add('step', HiddenType::class, [
                    'data' => $options['step']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'email' => null,
            'password' => null,
            'step' => null,
            'csrf_protection' => true,
            'csrf_token_id' => 'authenticate',
        ]);
    }
}
