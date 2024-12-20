<?php

namespace App\Controller;

use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/connection', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/déconnection', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/mot-de-passe-oublié', name: 'forgoten_password')]
    public function forgotenPassword(Request $request, UserRepository $ur, EntityManagerInterface $em, TokenGeneratorInterface $tokenGenerator, SendMailService $mail): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $ur->findOneBy(['email' => $data['email']]);
            if (!$user) {
                $this->addFlash('danger', 'Un problème est survenu');
                return $this->redirectToRoute('login');
            }

            $user->setResetToken($tokenGenerator->generateToken());
            $em->persist($user);
            $em->flush();

            $url = $this->generateUrl('reset_password', ['token' => $user->getResetToken()], UrlGeneratorInterface::ABSOLUTE_URL);

            $context = [
                'user' => $user,
                'url' => $url
            ];

            $mail->send(
                from: 'no-reply@ecommerce.fr',
                to: $user->getEmail(),
                subjet: 'Réinitialisation de votre mot de passe',
                template: 'reset_password',
                context: $context
            );
            $this->addFlash('success', 'Un email vous a été envoyé pour réinitialiser votre mot de passe');
            return $this->redirectToRoute('login');
        }
        return $this->render('security/forgoten_password.html.twig', [

            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/reset_password/{token}', name: 'reset_password')]
    public function resetPassword(string $token, Request $request, UserRepository $ur, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $ur->findOneBy(['resetToken' => $token]);
        if (!$user) {
            $this->addFlash('danger', 'Token invalid');
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user->setResetToken(null);
            $user->setPassword($hasher->hashPassword($user, $data['password']));
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès');
            return $this->redirectToRoute('login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
