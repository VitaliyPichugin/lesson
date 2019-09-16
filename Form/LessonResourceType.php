<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LessonBundle\Form;

use MediaBundle\Entity\Resource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Description of LessonResourceType
 *
 */
class LessonResourceType extends AbstractType
{
    public function getParent()
    {
        return EntityType::class;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Resource::class,
            'choice_value' => 'id',
            'choice_label' => [$this, 'generateLabel'],
            'choice_translation_domain' => false,
            'expanded' => true,
            'label' => false,
            'multiple' => true,
        ]);
    }
    
    public function generateLabel(Resource $resource, $key, $index)
    {
        return $resource->getName();
    }
    
    public function getBlockPrefix()
    {
        return 'lessonbundle_lesson_resource';
    }
}
