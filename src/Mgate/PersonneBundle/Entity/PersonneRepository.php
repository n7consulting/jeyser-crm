<?php

/*
 * This file is part of the Incipio package.
 *
 * (c) Florian Lefevre
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mgate\PersonneBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PersonneRepository extends EntityRepository
{
    public function getMembreOnly()
    {
        $qb = $this->_em->createQueryBuilder();
        $query = $qb->select('n')->from('MgatePersonneBundle:Personne', 'n')
          ->where('n.membre IS NOT NULL')
          ->orderBy('n.prenom', 'ASC')
          ->addOrderBy('n.nom', 'ASC');

        return $query;
    }

    /**
     * Renvoi tous les membres qui ont été au poste de $poste pendant un mandat.
     * Il est possible d'utiliser les metacaractères spécifiques à mySQL tel que % pour vos recherches.
     *
     * @param $poste
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getMembresByPoste($poste)
    {
        $qb = $this->_em->createQueryBuilder();
        $query = $qb
                ->select('p')
                ->from('MgatePersonneBundle:Personne', 'p')
                ->innerJoin('p.membre', 'me')
                ->innerJoin('me.mandats', 'ma')
                ->innerJoin('ma.poste', 'po')
                ->where('po.intitule LIKE :poste')
                ->setParameter('poste', $poste)
                ->orderBy('ma.finMandat', 'DESC');

        return $query;
    }

    public function getLastMembresByPoste($poste)
    {
        return $this->getMembresByPoste($poste)->setMaxResults(1);
    }

    /**
     * Requete récupérant uniquement les employés et retournant un query builder, utilisable dans un form.
     *
     * @param null $prospect
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEmployeOnly($prospect = null)
    {
        $qb = $this->_em->createQueryBuilder();

        if (null !== $prospect) {
            $query = $qb->select('p')
                        ->from('MgatePersonneBundle:Personne', 'p')
                        ->leftJoin('p.employe', 'e')
                        ->addSelect('e')
                        ->where('p.employe IS NOT NULL')
                        ->andWhere('e.prospect = :prospect')
                            ->setParameter('prospect', $prospect);
        } else {
            $query = $qb->select('n')->from('MgatePersonneBundle:Personne', 'n')
                        ->where('n.employe IS NOT NULL');
        }

        return $query;
    }

    public function getMembreNotUser($user = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $query = $qb->select('n')->from('MgatePersonneBundle:Personne', 'n')
          ->where('n.user IS NULL')
          ->andWhere('n.membre IS NOT NULL');

        if ($user) {
            $query->orWhere('n.user = :user')
            ->setParameter('user', $user);
        }

        return $query;
    }

    /**
     * Requete sur l'ensemble des personnes avec en jointure les différents OneToOne possibles.
     *
     * @param bool $orderByNom
     *
     * @return mixed
     */
    public function getAllPersonne($orderByNom = false)
    {
        $qb = $this->_em->createQueryBuilder();
        $query = $qb->select('p')->from('MgatePersonneBundle:Personne', 'p')
            ->leftJoin('p.employe', 'employe')
            ->addSelect('employe')
             ->leftJoin('p.membre', 'membre')
            ->addSelect('membre');
        if ($orderByNom) {
            $query->orderBy('p.nom', 'asc');
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Retourne un query builder de toutes les personnes ayant au moins un poste.
     * Utile dans le cas où l'on souhaite faire un formulaire d'uniquement les membres de la junior.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getByMandatNonNulQueryBuilder()
    {
        $qb = $this->_em->createQueryBuilder();

        $query = $qb
            ->select('p')
            ->from('MgatePersonneBundle:Personne', 'p')
            ->leftJoin('p.membre', 'm')->addSelect('m')
            ->leftJoin('m.mandats', 'mm')->addSelect('mm')
            ->where('mm.id IS NOT NULL')
            ;

        return $query;
    }

    /**
     * @param $search string a pattern we'd like to search in personnes' name or firstname
     * @param int $limit the number of personne that research should return
     *
     * @return array
     */
    public function searchByNom($search, $limit = 10)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('p')
            ->from('MgatePersonneBundle:Personne', 'p')
            ->where('p.nom LIKE :nom')
            ->orWhere('p.prenom LIKE :prenom')
            ->setParameter('nom', '%' . $search . '%')
            ->setParameter('prenom', '%' . $search . '%')
            ->setMaxResults($limit);
        $query = $qb->getQuery();

        return $query->getResult();
    }
}
