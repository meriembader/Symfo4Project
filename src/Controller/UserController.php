<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\User1Type;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Knp\Component\Pager\PaginatorInterface;
use Mukadi\Chart\Builder;
use Mukadi\Chart\Utils\RandomColorFactory;
use Mukadi\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(Request $request,  PaginatorInterface $paginator): Response
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();

        // Paginate the results of the query
        $users = $paginator->paginate(
        // Doctrine Query, not results
            $users,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            5
        );
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/listUserPDF", name="user_listPdf", methods={"GET"})
     */
    public function listUserPDF(): Response
    {


        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();


        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('user/listUserPDF.html.twig', [
            'users' => $users,
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A3', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("mypdf.pdf", [
            "Attachment" => true
        ]);





    }

    /**
     * @Route("/listExcel", name="user_listExcel", methods={"GET"})
     */
    public function excel(): Response
    {
        $spreadsheet = new Spreadsheet();

        /* @var $sheet \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet */
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', 'email');
        $sheet->setCellValue('C1', 'password');
        $sheet->setCellValue('D1', 'password');
        $sheet->setCellValue('D1', 'status');
        $sheet->setTitle("My First Worksheet");

        // Instantiate Dompdf with our options
        $sheet = new Spreadsheet();
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAll();


        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('user/listUserExcel.html.twig', [
            'users' => $users,
        ]);


        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);

        // Create a Temporary file in the system
        $fileName = 'my_first_excel_symfony4.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);

        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);

    }








    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }



    /**
     * @Route("/statics", name="user_st", methods={"GET"})
     */
    public function stat()
    {
        $connection = new PDO('mysql:dbname=fymfo4;host=127.0.0.1','root','');
        $builder = new Builder($connection);

        $builder
//
            ->query("SELECT COUNT(*) total, is_active FROM user GROUP BY is_active")
            ->addDataset('total','Total',[
                "backgroundColor" => RandomColorFactory::getRandomRGBAColors(24)
            ])
            ->labels('is_active');

        $chart = $builder->buildChart('chart',Chart::PIE);
        return $this->render('user/chart.html.twig',[
            "chart" => $chart
        ]);
    }


}
