<?php

namespace App\Manager;

use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectType;
use App\Form\TextareoType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserManager extends ServiceEntityRepository
{
    public $tokenStorage;

    public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        parent::__construct($registry, Project::class);
    }

    public function register(User $user)
    {
        $this->_em->persist($user); // On confie notre entité à l'entity manager (on persist l'entité)
        $this->_em->flush(); // On execute la requete
    }
}
