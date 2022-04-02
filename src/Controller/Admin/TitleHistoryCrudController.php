<?php

namespace App\Controller\Admin;

use App\Entity\TitleHistory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TitleHistoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TitleHistory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextField::new('category'),
            AssociationField::new('creator'),
            DateField::new('createdAt'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('createdAt')
            ->add('creator');
    }
}
