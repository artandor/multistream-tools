<?php

namespace App\Form;

use App\Entity\TitleHistory;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class StreamInfoType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $moderableAccounts[] = $this->security->getUser();
        $moderableAccounts = array_merge($moderableAccounts, $this->security->getUser()->getModeratorOf()->toArray());

        $builder
            ->add('title', TextType::class, ['label' => 'stream.title'])
            ->add('category', TextType::class, ['label' => 'stream.category'])
            ->add('submit', SubmitType::class, ['label' => 'form.submit']);

        if (count($moderableAccounts) > 1) {
            $builder
                ->add('account', EntityType::class, [
                    'label' => 'stream.account',
                    'class' => User::class,
                    'choices' => $moderableAccounts,
                    'mapped' => false
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TitleHistory::class,
        ]);
    }
}
