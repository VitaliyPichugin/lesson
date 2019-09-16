<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Form;

use Doctrine\Common\Collections\Collection;
use LessonBundle\Entity\Lesson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of LessonResourcesFormType
 *
 */
class LessonResourcesFormType extends AbstractType
{   
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('resources', LessonResourceType::class, [
                    'choices' => $options['resources'],
                ])
                ->add('objectives', CollectionType::class, [
                    'entry_type' => TextType::class,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'allow_add' => true,
                    'prototype' => true,
                    'entry_options' => [
                        'label' => false,
                    ],
                ])
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('resources');
        $resolver->setRequired('resources');
        $resolver->setAllowedTypes('resources', ['array', Collection::class]);
        
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'attr' => []
        ]);
    }
    
    public function getBlockPrefix()
    {
        return 'lessonbundle_lesson_resources_form';
    }
}
