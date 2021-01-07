<?php

namespace App\DataFixtures;

use App\Entity\Season;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Service\StatusCalculator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class SubscriptionFixtures extends Fixture implements DependentFixtureInterface
{

    private StatusCalculator $statusCalculator;

    public function __construct(StatusCalculator $statusCalculator)
    {
        $this->statusCalculator = $statusCalculator;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $index = 0;
        for ($i = 0; $i < 100; $i++) {
            for ($j = 0; $j <= rand(1, 6); $j++) {
                if ($j != rand(0, 2)) {
                    $subscription = new Subscription();
                    $subscription->setSubscriptionDate($faker->dateTimeThisDecade('now'));
                    $subscription->setSubscriber($this->getReference('subscriber_' . $i));
                    $subscription->setSeason($this->getReference('season_' . $j));
                    $subscription->setLicence($this->getReference('licence_' . rand(0, 5)));
                    $subscription->setStatus('');
                    $subscription->setCategory($this->getReference('category_' . rand(0, 11)));
                    $manager->persist($subscription);
                    $this->addReference('subscription_' . $index, $subscription);
                    $index++;
                }
            }
        }
        $manager->flush();

        $seasons = $manager->getRepository(Season::class)->findAll();
        $subscribers = $manager->getRepository(Subscriber::class)->findAll();

        //Subscription avec statut transfert
        $subscription = new Subscription();
        $subscription->setSubscriptionDate($faker->dateTimeThisDecade('now'));
        $subscription->setSubscriber($this->getReference('subscriber_100'));
        $subscription->setSeason($this->getReference('season_1'));
        $subscription->setLicence($this->getReference('licence_' . rand(0, 5)));
        $subscription->setStatus($this->statusCalculator->calculate($seasons, $subscribers));
        $subscription->setCategory($this->getReference('category_' . rand(0, 11)));
        $manager->persist($subscription);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SubscriberFixtures::class, SeasonFixtures::class, LicenceFixtures::class, CategoryFixtures::class];
    }
}