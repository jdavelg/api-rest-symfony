<?php
namespace App\Services;
use Firebase\JWT\JWT;
use App\Entity\User;
use Symfony\Component\Validator\Constraints\Time;



class JwtAuth{
    public $manager;
public $key;



public function __construct($manager)
{
  $this->manager= $manager;
  $this->key= 'hola_esta_es_una_clave_super_secreta';
}


public function signup($email, $password, $gettoken=null){
//ocmprobar si usuario existe
$user= $this->manager->getRepository(User::class)->findOneBy([
  'email'=>$email,
  'password'=>$password
]);

$signup=false;
if(is_object($user)){
  $signup=true;
}
//si existe generar el token jwt
if($signup){

$token=[
  'sub'=>$user->getId(),
  'name'=>$user->getName(),
  'surname'=>$user->getSurname(),
  'email'=>$user->getEmail(),
  'iat'=>time(),
  'exp'=>time()+(7 * 24 * 60 * 60),
];

$jwt= JWT::encode($token, $this->key, 'HS256' );
//comprobar el flAG gettoken
if($gettoken && !empty($gettoken)){
 
  $data= $jwt;

}else{
  $decoded= JWT::decode($jwt, $this->key, ['HS256'] );
  $data= $decoded;

}



}else{
  $data=[
'status'=>'error',
'message'=>'Login incorrecto'

  ];
} 

//devolver datos
  
   return $data;
}


public function checkToken($jwt, $identity=false){
$auth=false;
try {
  $decoded= JWT::decode($jwt, $this->key, ['HS256']);
} catch (\Throwable $th) {
  $auth=false;
}catch( \UnexpectedValueException $e){
  $auth=false;
}catch( \DomainException $d){
  $auth=false;
}catch( \ErrorException $c){
  $auth=false;
}




if(isset($decoded) && !empty($decoded) && is_object($decoded) && isset($decoded->sub)){
  $auth=true;
}else{
  $auth=false;
}

if ($identity!=false) {
  return $decoded;
}else{

  return $auth;
}


}

}
