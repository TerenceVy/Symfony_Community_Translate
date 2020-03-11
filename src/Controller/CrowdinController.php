<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Form\ProjectType;
use App\Form\TextareoType;
use App\Manager\ProjectManager;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CrowdinController extends AbstractController
{
    /**
     * @Route("/", name="crowdin_project_index")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function indextodoAction(Request $request, PaginatorInterface $paginator)
    {
        $donnees =  $this->getDoctrine()->getRepository(Project::class)->findtodoAction();

        $projects = $paginator->paginate(
            $donnees,
            $request->query->getInt('page', 1),
            7 // Nombre de résultats par page
        );

        return $this->render('project/index.html.twig', ['projects' => $projects]);
    }

    /**
     * @Route("/project_done", name="crowdin_project_done")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function indexdoneAction(Request $request, PaginatorInterface $paginator)
    {
        $donnees =  $this->getDoctrine()->getRepository(Project::class)->finddoneAction();

        $projects = $paginator->paginate(
        $donnees,
        $request->query->getInt('page', 1),
            6 // Nombre de résultats par page
        );

        return $this->render('project/index.html.twig', ['projects' => $projects]);
    }

    /**
     * @Route("/my_project", name="crowdin_project_myproject")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function myprojectAction(Request $request, PaginatorInterface $paginator)
    {
        $user = 0;
        if ($this->getUser() != null)
            $user = $this->getUser()->getId();
        $donnees =  $this->getDoctrine()->getRepository(Project::class)->findmyprojectAction($user);

        $projects = $paginator->paginate(
            $donnees,
            $request->query->getInt('page', 1),
            7 // Nombre de résultats par page
        );

        return $this->render('project/index.html.twig', ['projects' => $projects]);
    }

    /**
     * @Route("/project_add", name="crowdin_project_add")
     *
     * @IsGranted("ROLE_ADMIN")
     */
    public function addAction(Request $request, ProjectManager $projectManager)
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($project->getIsPublished()) {
                $project->setLastUpdateDate(new \DateTime());
                if ($project->getCsv() !== null) {
                    $file = $form->get('csv')->getData();
                    $mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
                    if(in_array($file->getmimeType(),$mimes)){

                    $fileName = uniqid() . '.' . $file->getClientOriginalExtension();

                    try {
                        $file->move(
                            $this->getParameter('csv_directory'), // Le dossier dans le quel le fichier va etre charger
                            $fileName
                        );
                    } catch (FileException $e) {
                        return new Response($e->getMessage());
                    }

                    $project->setCsv($fileName);
                    } else {
                        return new Response("C'est pas un csv bg");
                    }
                }

                if ($project->getIsPublished()) {
                    $project->setPublicationDate(new \DateTime());
                }
                $projectManager->addAction($project);
                return $this->render('project/show.html.twig', [
                    'project' => $project
                ]);
            }
        }

        return $this->render('project/add.html.twig', [
            'form' => $form->createView(),
            'user_id' => $this->getUser()->getId()
        ]);
    }

    /**
     * @Route("/project_show/{project}", name="crowdin_project_show")
     * @ParamConverter("Project", options={"mapping": {"project_title" : "title"}})
     * @Template()
     * @param Project $project
     * @return Response
     */
    public function showAction(Project $project)
    {
        $userid = 0;
        $userlanguage = "0";
        $project_user_id = 0;
        if($this->getUser() != null) {
            $userid = $this->getUser()->getId();
            $userid = (int)$userid;
            $userlanguage = $this->getUser()->getLanguages();
            $project_user_id = (int)$project->getUser()->getId();
        }

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'project_userid' => $project_user_id,
            'user' => $userid,
            'user_language' => $userlanguage
        ]);
    }

    /**
     * @Route("/project_edit/{project}", name="crowdin_project_edit")
     * @ParamConverter("Project", options={"mapping": {"project_title" : "title"}})
     *
     * @Template()
     * @param Project $project
     * @param Request $request
     * @return Response
     * @throws \Exception
     * @IsGranted("ROLE_ADMIN")
     */
    public function editAction(Project $project, Request $request, ProjectManager $projectManager)
    {
        $oldCsv = $project->getCsv();

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setLastUpdateDate(new \DateTime());

            if ($project->getIsPublished()) {
                $project->setPublicationDate(new \DateTime());
            }

            if ($project->getCsv() !== null && $project->getCsv() !== $oldCsv) {
                $file = $form->get('csv')->getData();
                $fileName = uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('csv_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $project->setCsv($fileName);
            } else {
                $project->setCsv($oldCsv);
            }

            $projectManager->editAction($project);

            return new Response('Le project a bien été modifié.');
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/project_remove/{id}", name="crowdin_project_remove")
     */
    public function deleteAction($id)
    {

        $this->getDoctrine()->getRepository(Project::class)->deleteProject($id);

        return new Response('Le project a bien été supprimé.');
    }

    /**
     * @Route("/admin", name="crowdin_project_admin")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function adminAction(Request $request, PaginatorInterface $paginator)
    {
        // Méthode findBy qui permet de récupérer les données avec des critères de filtre et de tri
        $donneesP = $this->getDoctrine()->getRepository(Project::class)->findBy([],['id' => 'desc']);

        $projects = $paginator->paginate(
            $donneesP, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            10 // Nombre de résultats par page
        );

        $donneesU = $this->getDoctrine()->getRepository(User::class)->findBy([],['id' => 'desc']);
        $users = $paginator->paginate(
            $donneesU, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            5 // Nombre de résultats par page
        );
        return $this->render('admin/index.html.twig', [
            'projects' => $projects,
            'users' => $users
        ]);
    }

    /**
     * @Route("/translate/{project}", name="crowdin_project_translate")
     * @ParamConverter("Project", options={"mapping": {"project_title" : "title"}})
     * @Template()
     * @param Project $project
     * @return Response
     */
    public function translateAction(Request $request, Project $project, ProjectManager $projectManager)
    {
        $form = $this->createForm(TextareoType::class);
        $form->handleRequest($request);
        $translation = $form->get('translation')->getData();
        if($form->isSubmitted()) {
            $projectManager->translateAction($project, $translation);
        }

        return $this->render('project/translate.html.twig', [
            'project' => $project,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/my_stat", name="crowdin_project_stat")
     */
    public function showstatAction()
    {
        return $this->render('project/stat.html.twig');
    }

}
