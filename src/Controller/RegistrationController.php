<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        SendMailService $mailer,
        JWTService $jwt
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $entityManager->persist($user);
            $entityManager->flush();

            // on créé le header
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];
            $payload = [
                'user_id' => $user->getId()
            ];

            // on genere le JWT
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
            // on envoie un mail
            $mailer->send(
                'no-reply@monsite.com',
                $user->getEmail(),
                'Activation de votre compte pour Ecommerce',
                'register',
                [
                    'user' => $user,
                    'token' => $token
                ]
            );

            return $security->login($user, UserAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
    #[Route('/token/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UserRepository $ur, EntityManagerInterface $em): Response
    {
        if (
            $jwt->isValid($token)
            && !$jwt->isExpired($token)
            && $jwt->check($token, $this->getParameter('app.jwtsecret'))
        ) {
            $user = $ur->find($jwt->getPayload($token)['user_id']);
            if ($user && !$user->getIsVerified()) {
                $user->setIsVerified(true);
                $em->flush();
                $this->addFlash('success', 'Votre compte a bien été activé');
                return $this->redirectToRoute('profile_index');
            }
            $this->addFlash('success', 'Votre compte a déja été activé');
            return $this->redirectToRoute('profile_index');
        }
        $this->addFlash('danger', 'le Token est invalide ou a éxpiré');
        return $this->redirectToRoute('login');
    }

    #[Route('/resend_verif', name: 'resend_verif')]
    public function resend(
        SendMailService $mailer,
        UserRepository $ur,
        JWTService $jwt
    ): Response {
        $user = $ur->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if (!$user) {
            $this->addFlash('danger', 'Vous devez etre connecté pour accéder a cette page');
            return $this->redirectToRoute('login');
        }
        if ($user->getIsVerified()) {
            $this->addFlash('success', 'Votre compte a déja été activé');
            return $this->redirectToRoute('profile_index');
        }
        // on créé le header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        $payload = [
            'user_id' => $user->getId()
        ];

        // on genere le JWT
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
        // on envoie un mail
        $mailer->send(
            'no-reply@monsite.com',
            $user->getEmail(),
            'Activation de votre compte pour Ecommerce',
            'register',
            [
                'user' => $user,
                'token' => $token
            ]
        );
        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('profile_index');
    }
}
