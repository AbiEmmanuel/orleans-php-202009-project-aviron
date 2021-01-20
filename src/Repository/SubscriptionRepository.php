<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }
    public function grandTotalPerSeason(): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, season.name')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->groupBy('season.name')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }
    public function getQueryForGrandTotalPerSeason(): string
    {
        return 'SELECT COUNT(subscription.subscriber) as total, season.name as seasonName
        FROM App\Entity\Subscription subscription 
        JOIN App\Entity\Season season 
        WITH season.id=subscription.season 
        GROUP BY seasonName
        ORDER BY seasonName';
    }
    public function totalPerSeason(): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, season.name, subscriber.gender')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->leftJoin('App\Entity\Subscriber', 'subscriber', 'WITH', 'subscription.subscriber = subscriber.id')
            ->groupBy('season.name, subscriber.gender')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }
    public function getQueryForTotalPerSeason(): string
    {
        return 'SELECT COUNT(subscription.subscriber) as total, season.name as seasonName, subscriber.gender as gender
        FROM App\Entity\Subscription subscription 
        JOIN App\Entity\Season season 
        WITH season.id=subscription.season 
        JOIN App\Entity\Subscriber subscriber 
        WITH subscriber.id=subscription.subscriber 
        GROUP BY seasonName, gender
        ORDER BY season.name';
    }

    /**
     * @param string|null $categoryLabel
     * @param string|null $licenceAcronym
     * @return Subscription
     */
    public function findSubscriptionsBySeason(
        ?string $categoryLabel,
        ?string $licenceAcronym
    ) {
        return $this->createQueryBuilder('subscription')
            ->select('COUNT(subscription.subscriber) AS total, season.name, subscriber.gender')
            ->leftJoin('App\Entity\Season', 'season', 'WITH', 'subscription.season = season.id')
            ->leftJoin('App\Entity\Category', 'category', 'WITH', 'subscription.category = category.id')
            ->leftJoin('App\Entity\Licence', 'licence', 'WITH', 'subscription.licence = licence.id')
            ->leftJoin('App\Entity\Subscriber', 'subscriber', 'WITH', 'subscription.subscriber = subscriber.id')
            ->where('licence.acronym = :licenceAcronym')
            ->setParameter('licenceAcronym', $licenceAcronym)
            ->andWhere('category.label = :categoryLabel')
            ->setParameter('categoryLabel', $categoryLabel)
            ->groupBy('season.name, subscriber.gender')
            ->orderBy('season.name')
            ->getQuery()
            ->getResult();
    }
    /**
     * @param string|null $status
     * @param string|null $seasonName
     * @return Subscription
     * @throws NonUniqueResultException
     */
    public function findSubscribersForActualSeasonPerStatus(?string $status, ?string $seasonName)
    {
        return $this->createQueryBuilder('sub')
            ->select('COUNT(sub.subscriber)')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->innerJoin('App\Entity\Status', 'st', 'WITH', 'st.id = sub.status')
            ->andWhere('s.name = :seasonName')
            ->andWhere('st.label = :status')
            ->setParameter('seasonName', $seasonName)
            ->setParameter('status', $status)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function subscribersByYearByLicences(?string $season): array
    {
        return $this->createQueryBuilder('subscription')
            ->select('licence.acronym as label, COUNT(subscription.subscriber) as subscribersCount')
            ->leftJoin('App\Entity\Licence', 'licence', 'WITH', 'licence.id=subscription.licence')
            ->innerJoin('App\Entity\Season', 'season', 'WITH', 'season.id=subscription.season')
            ->where('season.name = :season')
            ->setParameter('season', $season)
            ->groupBy('licence.acronym')
            ->getQuery()
            ->getResult()
            ;
    }

    public function getQueryForSubscribersByYearByLicences(?string $season): string
    {
        return 'SELECT licence.acronym as label, COUNT(subscription.subscriber) as subscribersCount 
        FROM App\Entity\Subscription subscription 
        JOIN App\Entity\Licence licence 
        WITH licence.id=subscription.licence 
        JOIN App\Entity\Season season 
        WITH season.id=subscription.season 
        WHERE season.name = \'' . $season . '\'
        GROUP BY licence.acronym';
    }

    public function subscribersByYearByCategories(?string $season): array
    {
        return $this->createQueryBuilder('sub')
            ->select('c.label as label, COUNT(sub.category) as subscribersCount')
            ->leftJoin('App\Entity\Category', 'c', 'WITH', 'c.id = sub.category')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->where('s.name = :season')
            ->setParameter('season', $season)
            ->groupBy('sub.category')
            ->orderBy('c.id')
            ->getQuery()
            ->getResult();
    }

    public function getQueryForSubscribersByYearByCategories(?string $season): string
    {
        return 'SELECT COUNT(sub.category) as subscribersCount, c.label as label 
                    FROM \App\Entity\Subscription sub
                    JOIN \App\Entity\Category c
                    WITH c.id = sub.category
                    JOIN \App\Entity\Season s
                    WITH s.id = sub.season
                    WHERE s.name = \'' . $season . '\'
                    GROUP BY sub.category
                    ORDER BY c.id ASC';
    }

    /**
     * @param string|null $licenceAcronym
     * @param string|null $seasonName
     * @return Subscription
     * @throws NonUniqueResultException
     */
    public function findAllSubscribersForActualSeason(?string $licenceAcronym, ?string $seasonName)
    {
        return $this->createQueryBuilder('sub')
            ->select('COUNT(sub.subscriber)')
            ->innerJoin('App\Entity\Licence', 'l', 'WITH', 'l.id = sub.licence')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->where('l.acronym = :licenceAcronym')
            ->setParameter('licenceAcronym', $licenceAcronym)
            ->andWhere('s.name = :seasonName')
            ->setParameter('seasonName', $seasonName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|null $licenceAcronym
     * @param string|null $seasonName
     * @param string|null $categoryName
     * @return int|mixed|string|null
     * @throws NonUniqueResultException
     */
    public function findAllYoungSubscribersForActualSeason(
        ?string $licenceAcronym,
        ?string $seasonName,
        ?string $categoryName
    ) {
        return $this->createQueryBuilder('sub')
            ->select('COUNT(sub.subscriber)')
            ->innerJoin('App\Entity\Licence', 'l', 'WITH', 'l.id = sub.licence')
            ->innerJoin('App\Entity\Season', 's', 'WITH', 's.id = sub.season')
            ->innerJoin('App\Entity\Category', 'c', 'WITH', 'c.id = sub.category')
            ->where('l.acronym = :licenceAcronym')
            ->setParameter('licenceAcronym', $licenceAcronym)
            ->andWhere('s.name = :seasonName')
            ->setParameter('seasonName', $seasonName)
            ->andWhere('c.label LIKE :categoryName')
            ->setParameter('categoryName', $categoryName . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string|null $seasonName
     * @return array
     */
    public function getLastSubscriber(?string $seasonName)
    {
        return $this->createQueryBuilder('sub')
            ->select('sr.licenceNumber')
            ->innerJoin('App\Entity\Season', 'sn', 'WITH', 'sn.id = sub.season')
            ->innerJoin('App\Entity\Subscriber', 'sr', 'WITH', 'sr.id = sub.subscriber')
            ->where('sn.name = :seasonName')
            ->setParameter('seasonName', $seasonName)
            ->orderBy('sr.licenceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

// /**
    //  * @return Subscription[] Returns an array of Subscription objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
