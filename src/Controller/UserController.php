<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;
use Container191YXfB\getValidator_EmailService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\DoctrineProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;


class UserController extends AbstractController
{
   


    public function index()
    {

        $user_repo= $this->getDoctrine()->getRepository(User::class);
        $video_repo= $this->getDoctrine()->getRepository(Video::class);
$videos= $video_repo->findAll();
        $users= $user_repo->findAll();
$user= $user_repo->find(1);


$data=[
     'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
];

/* 
$users= $user_repo->findAll();
foreach($users as $user){

    echo "<h1>".$user->getName()."</h1><br>";
foreach ($user->getVideos() as $video ) {
  echo $video->getTitle();
}
die();
} */
        return $this->resjson($videos);
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


public function create(Request $request){
//recoger datos por POST
$json= $request->get('json', null);

//decodificar el JSON
$params= json_decode($json);

//respuesta por defecto
$data=[
    'status'=>'error',
    'code'=>200,
    'message'=>'el usuario no se ha creado',
   
];

//comprobar y validar datos
if($json!=null){
$name= (!empty($params->name ))? $params->name : null;
$surname = (!empty($params->surname ))? $params->surname : null;

$email = (!empty($params->email ))? $params->email : null;
$password = (!empty($params->password ))? $params->password : null;

$validator= Validation::createValidator();

if(!empty($email) && !empty($password) && $password!=null && $surname!=null && $name!=null &&!empty($name) &&!empty($surname)){

    //si validacion es correcta crear objetos de usuario
$user= new User();
$user->setName($name);
$user->setSurname($surname);
$user->setEmail($email);
$user->setRole('ROLE_USER');
$date= new Datetime('now');
$date->format('Y-m-d H:i:s');
$user->setCreatedAt($date);
//cifrar passsword

$pwd= hash('sha256', $password);
$user->setPassword($pwd);


$data= $user;
//comprobar si ya existe el user
$em         = $this->getDoctrine()->getManager();
$user_repo  = $em->getRepository(User::class);
$isset_user= $user_repo->findBy(array( 'email'=> $email));

if (count($isset_user)== 0) {
    
  //guardar en base de datos
$em->persist($user);
$em->flush();


  $data=[
    'status'=>'success',
    'code'=>400,
    'message'=>'Usuario Creado Correctamente',
   'user'=>$user
];

}else{
    $data=[
        'status'=>'error',
        'code'=>400,
        'message'=>'Ya existe un usuario registrado con el email',
       
    ];
}



    

}else{
    $data=[
        'status'=>'error',
        'code'=>200,
        'message'=>'validacion incorrecta'
        
    ]; 
}


//respuesta en json

return new JsonResponse($data);

}

}

public function login(Request $request,JwtAuth $jwt_auth){
    //recibir datos por POST

$json= $request->get('json',null);
$params= json_decode($json);
/* var_dump($json); die(); */

    //Array por defecto para devolver
    $data=[
        'status'=>'error',
        'code'=>200,
        'message'=>'usuario o clave incorrectos'
        
    ]; 
    //comprobar y validar datos
    if($json != null){

$email=(!empty($params->email))? $params->email :null;
$password=(!empty($params->password))? $params->password :null;
$gettoken=(!empty($params->gettoken))? $params->gettoken :null;

$validator= Validation::createValidator();


if($email!=null && $password!=null){
//descifrar/cifrar la contraseña
$pwd= hash('sha256', $password);

    //llamar a un servicio para identificar al usuario(jwt, token o un objeto)
//crear servicio jwt

if($gettoken){

    $data= $jwt_auth->signup($email, $pwd, $gettoken);
}else{
    $data=$jwt_auth->signup($email, $pwd);
}

    //Si nos devuelve bien los datos, hacer respuesta
return new JsonResponse($data);

}

    }    

return new JsonResponse($data);
}

public function edit(Request $request, JwtAuth $jwt_auth){
//recoger la cabecera de autenticacion
$token= $request->headers->get('Authorization');

//Crear un metodo para comprobar si el token es correcto(en JWTAUth)
$authCheck= $jwt_auth->checkToken($token);

//datos por defecto
$data=[
    'code'=>400,
    'status'=>'error',
    'message'=>'usuario no actualizado',
    
];

//si el token es correcto actualizar usuario
if($authCheck){
//"actualizar el usuario"

//conseguir entity manager
$em= $this->getDoctrine()->getManager();

//conseguir datos el usuario
$identity= $jwt_auth->checkToken($token, true);

//conseguir el usuario a actualizar completo
$user_repo= $this->getDoctrine()->getRepository(User::class);

$user= $user_repo->findOneBy([
    'id'=>$identity->sub
]);
//recoger datos por POST
$json= $request->get('json',null);
$params= json_decode($json);

//comprobar y validar datos

if(!empty($json)){
    
        $name= (!empty($params->name ))? $params->name : null;
        $surname = (!empty($params->surname ))? $params->surname : null;
        
        $email = (!empty($params->email ))? $params->email : null;
       
        
        $validator= Validation::createValidator();
        if(!empty($email) && $surname!=null && $name!=null &&!empty($name) &&!empty($surname)){
//asignar nuevos datos
$user->setEmail($email);
$user->setName($name);
$user->setSurname($surname);

//comprobar duplicados

$isset_user= $user_repo->findBy([
    'email'=>$email
]);

if(count($isset_user) ==0|| $identity->email== $email){
//guardar cambios en la DB

$em->persist($user);

$em->flush();

$data=[
    'code'=>200,
    'status'=>'success',
    'message'=>'usuario actualizado',
    'user'=>$user

];
}else{
    $data=[
        'code'=>400,
        'status'=>'error',
        'message'=>'El email esta registrado',
        
    ];

}
        }
}
}



return $this->json($data);

}

}
