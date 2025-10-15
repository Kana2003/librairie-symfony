<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le titre du livre',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer la description du livre',
                    ]),
                ],
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer l\'auteur du livre',
                    ]),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du livre',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG ou GIF)',
                    ]),
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix (CAD)',
                'currency' => 'CAD',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le prix du livre',
                    ]),
                    new Positive([
                        'message' => 'Le prix doit être positif',
                    ]),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock disponible',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer le stock disponible',
                    ]),
                    new Positive([
                        'message' => 'Le stock doit être positif',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
