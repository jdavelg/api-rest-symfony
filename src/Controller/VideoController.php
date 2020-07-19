<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;
use Container191YXfB\getValidator_EmailService;
use DateTime;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Cache\DoctrineProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

class VideoController extends AbstractController
{
    
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }

    public function resjson($data){

        //serializar datos con servicio serializer
        $json= $this->get('serializer')->serialize($data, 'json');
    
    //response con httpfoundation
    $response =new Response();
    
    //asignar contenido a la respuesta
    $response->setContent($json);
    
    
    //inidicar formato de respuesta
    
    $response->headers->set('Content-Type', 'application/json');
    
    //Devolver respuesta
    
    return $response;
    
    }

    public function create(Request $request, JwtAuth $jwt_auth, $id=null){

//datos por defecto
$data=[
    'code'=>200,
    'status'=>'error',
    'message'=>'new video working'
    
    ];


//recoger el token

$token = $request->headers->get('Authorization',null);
//comprobar si es correcto

$authCheck= $jwt_auth->checkToken($token);
if ($authCheck) {
    //recoger datos por POST

$json= $request->get('json',null);

$params= json_decode($json);
//recoger objeto de usuario
$identity= $jwt_auth->checkToken($token, true);

//validar datos
if (!empty($json)) {
   $user_id= ($identity->sub !=null) ? $identity->sub :null;
   $title= (!empty($params->title)) ? $params->title :null;
    $description= (!empty($params->description)) ? $params->description :null;
   $url= (!empty($params->url)) ? $params->url :null;
}

if(!empty($user_id) && !empty($title)){
//guardar nuevo video en la db
$em= $this->getDoctrine()->getManager();
$user=$this->getDoctrine()->getRepository(User::class)->findOneBy([
    'id'=>$user_id
]);


if( $id==null){

//crear y guardar objeto
$video=new Video();

$video->setUser($user);
$video->setTitle($title);
$video->setDescription($description);
$video->setUrl($url);
$video->setStatus('normal');

$createdAt= new \Datetime('now');
$updatedAt= new \Datetime('now');
$video->setCreatedAt($createdAt);
$video->setUpdatedAt($updatedAt);

$em->persist($video);
$em->flush();
//devolver response

$data=[
    'code'=>200,
    'status'=>'success',
    'message'=>'Video Guardado',
    'video'=>$video
    ];
}else{

    //actualizar video

    //datos by default

    $data=[
        'code'=>404,
        'status'=>'error',
        'message'=>'No se puede actualizar el video',
        
        ];


$video= $this->getDoctrine()->getRepository(Video::class)->findOneBy([
'id'=>$id,
'user'=>$identity->sub

]);

if(isset($video) && $video && is_object($video) ){

    $video->setTitle($title);
    $video->setDescription($description);
    $video->setUrl($url);
   
$updatedAt= new \Datetime('now');
$video->setUpdatedAt($updatedAt);

$em->persist($video);
$em->flush();
$data=[
    'code'=>200,
    'status'=>'success',
    'message'=>'Video Actualizado',
    'video'=>$video
    ];
}


}
}

}
        return $this->json($data);
    }


    public function videos(Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator){

//recoger la cabecera de autentication

$token= $request->headers->get('Authorization');

//comprobar el token
$authCheck= $jwt_auth->checkToken($token);

if ($authCheck) {
    //si token es valid 
//conseguir identidad de user
$identity= $jwt_auth->checkToken($token, true);

//configuarar el bundle de paginacion(en services.yamel)
$em= $this->getDoctrine()->getManager();
//hacer consulta para paginar
$dql="SELECT v FROM App\Entity\Video v WHERE v.user=($identity->sub) ORDER BY v.id DESC";

$query= $em->createQuery($dql);
//recoger parametro page de la url
$page= $request->query->getInt('page',1);

$items_per_page= 5;
//invocar paginacion y preparar array de datos para devolver

$pagination= $paginator->paginate($query, $page, $items_per_page);
$total= $pagination->getTotalItemCount();


$data= [
    'status'=>'success',
    'code'=>200,
    'total_items_count'=>$total,
    'page_actual'=>$page,
    'items_per_page'=>$items_per_page,
    'total_pages'=>ceil($total/ $items_per_page),
    'videos'=>$pagination,
    'user_id'=>$identity->sub
    
    ];


}else{

    $data= [
        'status'=>'error',
        'code'=>404,
        'message'=>'no se pueden listar los datos en este momento'
        
        ];
}

return $this->json($data);

    }





    public function video(Request $request, JwtAuth $jwt_auth, $id= null){


//datos por defecto
$data= [
    'status'=>'error',
    'code'=>404,
    'message'=>'no se pueden encontrar el video'

    
    ];


//sacar el token y comprobar si es correcto
$token = $request->headers->get('Authorization');
//sacar iudentidad de usuario

$authCheck= $jwt_auth->checkToken($token);

if($authCheck){
//sacar el objeto del video en base a id
$identity= $jwt_auth->checkToken($token, true);

//comporbar si el video existe y es propiedad del usuario identificado
$video= $this->getDoctrine()->getRepository(Video::class)->findOneBy([
'id'=>$id,
/* 'user'=>$identity->sub */
]);

if($video && is_object($video) && $identity->sub == $video->getUser()->getId()){
$data=[
    'status'=>'success',
    'code'=>200,
    'video'=>$video
];
}

//devolver response

}else{
    $data= [
        'status'=>'error',
        'code'=>404,
        'message'=>'no se pueden encontrar el video'
    
        
        ];
}

    
return $this->json($data);


    }

public function remove(Request $request, JwtAuth $jwt_auth, $id= null){
   
   $token= $request->headers->get('Authorization');

   $authCheck= $jwt_auth->checkToken($token);

    $data= [
        'status'=>'error',
        'code'=>404,
        'message'=>'no se puede eliminar el video'
    
        
        ];


     if($authCheck){
         $identity= $jwt_auth->checkToken($token, true);

         $doctrine= $this->getDoctrine();
         $em= $doctrine->getManager();

         $video= $doctrine->getRepository(Video::class)->findOneBy([
             'id'=>$id
         ]);

         if(isset($video) && is_object($video) && $identity->sub == $video->getUser()->getId()){

$em->remove($video);
$em->flush();

$data= [
    'status'=>'success',
    'code'=>200,
    'message'=>'Video eliminado correctamente',
    'video'=>$video

    
    ];

         }
     }   

return $this->json($data);
}


}
