<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OtpCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', HiddenType::class, [ ]);
        $builder
            ->add('emailAddress', HiddenType::class, [
                'data' => $options['email']
             ]);
        $builder
            ->add('password', HiddenType::class, [
                   'data' => $options['password']
             ]);
        $builder
            ->add('reference', HiddenType::class, [
                   'data' => $options['reference']
             ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => true,
            'email' => null,
            'password' => null,
            'reference' => null,
            'csrf_token_id' => 'two_factor',
        ]);
    }
}
