<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Note;
use App\Entity\User;
use App\Entity\Network;
use App\Entity\Category;
use App\Entity\Offer;
use App\Entity\Subscription;
use App\Entity\View;
use App\Entity\Notification;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $slug = null;
    private $hash = null;

    public function __construct(
        private SluggerInterface $slugger,
        private UserPasswordHasherInterface $hasher
    ) {
        $this->slug = $slugger;
        $this->hash = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Création de catégories
        $categories = [
            'HTML' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/html5/html5-plain.svg',
            'CSS' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/css3/css3-plain.svg',
            'JavaScript' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/javascript/javascript-plain.svg',
            'PHP' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-plain.svg',
            'SQL' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/postgresql/postgresql-plain.svg',
            'JSON' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/json/json-plain.svg',
            'Python' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/python/python-plain.svg',
            'Ruby' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/ruby/ruby-plain.svg',
            'C++' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/cplusplus/cplusplus-plain.svg',
            'Go' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/go/go-original-wordmark.svg',
            'bash' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/bash/bash-plain.svg',
            'Markdown' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/markdown/markdown-original.svg',
            'Java' => 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/java/java-original-wordmark.svg',
        ];

        $networks = ['github', 'twitter', 'linkedin', 'facebook', 'reddit', 'instagram', 'youtube'];

        $typesNotif = ["Note pushed in public", "Note successfully updated", "Note set to private status", "Note successfully deleted", "Note has reached a new level of views", "Note has reached a new level of likes", "Note successfully upgraded to premium", "Note successfully put to public"];

        $categoryArray = []; // Ce tableau nous servira pour conserver les objets Category

        foreach ($categories as $title => $icon) {
            $category = new Category(); // Nouvel objet Category
            $category
                ->setTitle($title) // Ajoute le titre
                ->setIcon($icon) // Ajoute l'icone
            ;

            array_push($categoryArray, $category); // Ajout de l'objet
            $manager->persist($category);
        }

        // ------------- OFFERS --------------------
        $offerArray = [];
        // Création de 3 offres
        $offer1 = new Offer();
        $offer1
            ->setName("Standard")
            ->setPrice(0)
            ->setFeatures($faker->paragraph())
            ;
        array_push($offerArray, $offer1);
        $manager->persist($offer1);

        $offer2 = new Offer();
        $offer2
            ->setName("Premium Bronze")
            ->setPrice(14,90)
            ->setFeatures($faker->paragraphs(2, true)) //source : https://fakerphp.org/formatters/text-and-paragraphs/#text
            ;
            array_push($offerArray, $offer2);
            $manager->persist($offer2);

        $offer3 = new Offer();
        $offer3
            ->setName("Premium Gold")
            ->setPrice(19.90)
            ->setFeatures($faker->paragraphs(3, true))
            ;
            array_push($offerArray, $offer3);
            $manager->persist($offer3);
        // ------------- END OFFERS --------------------

        // Admin
        $user =  new User();
        $user
            ->setEmail('hello@codexpress.fr')
            ->setUsername('Jensone')
            ->setPassword($this->hash->hashPassword($user, 'admin'))
            ->setRoles(['ROLE_ADMIN'])
            ->setImage($faker->imageUrl(360, 360, 'User', true, 'Picture')) // source : https://fakerphp.org/formatters/image/
            ;
        $manager->persist($user);

        $subscription = new Subscription();
        $subscription
            ->setOffer($faker->randomElement($offerArray))
            ->setCreator($user)
        ;
        $manager->persist($subscription);

        for ($d=0; $d < 3; $d++) {
            $network = new Network();
            $network
                ->setName($faker->randomElement($networks))
                ->setUrl('https://' . $network->getName() . '.com/Jensone')
                ->setCreator($user)
                ;
            $manager->persist($network);
        }

        for ($y=0; $y < 10; $y++) { 
            $note = new Note();
            $note
                ->setTitle($faker->words(5, true))
                ->setSlug($this->slug->slug($note->getTitle()))
                ->setContent($faker->randomHtml())
                ->setPublic($faker->boolean())
                ->setCreator($user)
                ->setCategory($faker->randomElement($categoryArray))
                ;
                $manager->persist($note);

            // Création d'un nb de view alétatoire
            $n = $faker->numberBetween(1, 100);
            for($v = 0; $v <= $n ; $v++)
            {
                $view = new View();
                $view
                    ->setNote($note)
                    ->setIpAddress($faker->localIpv4()) // source : https://fakerphp.org/formatters/internet/#ipv6
                ;
                $manager->persist($view);
            }
            

            // Création de notifications
            $nb = $faker->numberBetween(1, 5); // Création d'un nb alétatoire
            for($notif = 0; $notif <= $nb ; $notif++)
            {
                $notification = new Notification();
                $notification
                    ->setTitle($faker->words(5, true))
                    ->setContent($faker->randomHtml())
                    ->setType($faker->randomElement($typesNotif))
                    ->setArchived($faker->boolean(50))
                    ->setNote($note)
                ;
                $manager->persist($notification);
            }
        }

        // 10 utilisateurs
        for ($i = 0; $i < 10; $i++) {
            $username = $faker->userName; // Génére un username aléatoire
            $usernameFinal = $this->slug->slug($username); // Username en slug
            $user =  new User();
            $user
                ->setEmail($usernameFinal . '@' . $faker->freeEmailDomain)
                ->setUsername($username)
                ->setPassword($this->hash->hashPassword($user, 'admin'))
                ->setRoles(['ROLE_USER'])
                ->setImage($faker->imageUrl(360, 360, 'User', true, 'Picture')) // source : https://fakerphp.org/formatters/image/
                ;

            $subscription = new Subscription();
            $subscription
                ->setOffer($faker->randomElement($offerArray))
                ->setCreator($user)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setStartDate(new \DateTimeImmutable())
            ;
            $manager->persist($subscription);

            for ($z=0; $z < 3; $z++) {
                $network = new Network();
                $network
                    ->setName($faker->randomElement($networks))
                    ->setUrl('https://' . $network->getName() . '.com/' . $usernameFinal)
                    ->setCreator($user)
                    ;
                $manager->persist($network);
            }
            $manager->persist($user);

            for ($j=0; $j < 10; $j++) { 
                $note = new Note();
                $note
                    ->setTitle($faker->words(5, true))
                    ->setSlug($this->slug->slug($note->getTitle()))
                    ->setContent($faker->randomHtml())
                    ->setPublic($faker->boolean(50))
                    ->setPremium($faker->boolean(50))
                    ->setCreator($user)
                    ->setCategory($faker->randomElement($categoryArray))
                    ->setCreatedAt(new \DateTimeImmutable())
                    ;
                $manager->persist($note);

                 // Création d'un nb de view alétatoire
                $n = $faker->numberBetween(1, 100);
                for($v = 0; $v <= $n ; $v++)
                {
                    $view = new View();
                    $view
                        ->setNote($note)
                        ->setIpAddress($faker->localIpv4()) // source : https://fakerphp.org/formatters/internet/#ipv6
                        ->setCreatedAt(new \DateTimeImmutable())
                    ;
                    $manager->persist($view);
                };

                // Création de notifications
                $nb = $faker->numberBetween(1, 5); // Création d'un nb alétatoire
                for($notif = 0; $notif <= $nb ; $notif++)
                {
                    $notification = new Notification();
                    $notification
                        ->setTitle($faker->words(5, true))
                        ->setContent($faker->randomHtml())
                        ->setType($faker->randomElement($typesNotif))
                        ->setArchived($faker->boolean(50))
                        ->setNote($note)
                        ->setCreatedAt(new \DateTimeImmutable())
                    ;
                    $manager->persist($notification);
                }
            }
        }

        $manager->flush();
    }
}
