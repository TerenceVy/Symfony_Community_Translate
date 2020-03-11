<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UpdateUserTypeType;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UpdateUserController extends AbstractController
{
    /**
     * @Route("/update_user/{id}", name="crowdin_user_update")
     * @IsGranted("ROLE_ADMIN")
     */
    public function profileEdit(Request $request, UserPasswordEncoderInterface $passwordEncoder, UserManager $userManager, User $user) : Response
    {
        $form = $this->createForm(UpdateUserTypeType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword($user,
                    $form->get('plainPassword')->getData()
                )
            );

            $userManager->register($user);


            return $this->redirectToRoute('crowdin_project_index');
        }

        return $this->render('registration/updateuser.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
