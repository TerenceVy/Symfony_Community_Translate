<?php

namespace App\Repository;

use App\Entity\Project;
use App\Manager\ProjectManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function deleteProject($id)
    {
        $query = $this
            ->createQueryBuilder('du')
            ->delete(Project::class,'du')
            ->where('du.id = :id')
            ->setParameter("id", $id)
            ->getQuery()
            ->execute();
        return $query;
    }

    public function finddoneAction()
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('r')
            ->from($this->_entityName, 'r') // Dans un repository, $this->_entityName est le namespace de l'entité gérée
            ->Where('r.translation IS NOT NULL')
            ->orderBy('r.id', 'DESC');
        return $queryBuilder->getQuery()->getResult();
    }

    public function findtodoAction()
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('r')
            ->from($this->_entityName, 'r') // Dans un repository, $this->_entityName est le namespace de l'entité gérée
            ->Where('r.translation IS NULL')
            ->orderBy('r.id', 'DESC');
        return $queryBuilder->getQuery()->getResult();
    }

    public function findmyprojectAction($user)
    {
        $queryBuilder = $this->_em->createQueryBuilder()
            ->select('r')
            ->from($this->_entityName, 'r') // Dans un repository, $this->_entityName est le namespace de l'entité gérée
            ->Where('r.user =' . $user)
            ->orderBy('r.id', 'DESC');
        return $queryBuilder->getQuery()->getResult();
    }
}
