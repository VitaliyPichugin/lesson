<?php

namespace LessonBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use LessonBundle\Form\FeedbackRatingType;

class FeedbackType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('languRating', FeedbackRatingType::class)
                ->add('lessonRating', FeedbackRatingType::class)
                ->add('teacherRating', FeedbackRatingType::class)
                ->add('teacherComment', TextAreaType::class)
//                ->add('lessonComment', TextAreaType::class)
//                ->add('language', EntityType::class, [
//                    'choices' => $options['languages'],
//                    'choice_value' => 'id',
//                    'choice_label' => 'name',
//                    'choice_translation_domain' => false,
//                    'class' => 'IntlBundle\Entity\Language',
//                    'expanded' => false,
//                ])        
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // required fields
        $options = ['languages'];

        $resolver->setDefined($options);
        $resolver->setRequired($options);

        foreach ($options as $option) {
            $resolver->setAllowedTypes($option, 'array');
        }
        
        $resolver->setDefaults(array(
            //'translation_domain' => 'Lesson',
            'attr' => array('novalidate' => 'novalidate'),
            'cascade_validation' => false,
            'data_class' => 'LessonBundle\Entity\Feedback'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'lessonbundle_feedback';
    }
}
