<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);



});
$app->group('/api', function () use ($app) {

  $app->post('/registration', function ($request, $response) {
     $input = $request->getParsedBody();
     $sql = "INSERT INTO users (userName, pWord, lastName, firstName, email, income) VALUES (:userName, :pWord, :lastName, :firstName, :email, :income)";
     $sth = $this->db->prepare($sql);
     $sth->bindParam("userName", $input['userName']);
     $sth->bindParam("pWord", $input['pWord']);
     $sth->bindParam("lastName", $input['lastName']);
     $sth->bindParam("firstName", $input['firstName']);
     $sth->bindParam("email", $input['email']);
     $sth->bindParam("income", $input['income']);
     $sth->execute();
     return $this->response->withJson($input); });

  $app->get('/login/[{userName}]', function ($request, $response, $args) {
       $sth = $this->db->prepare(
         "SELECT * FROM users WHERE userName=:userName"
       );
       $sth->bindParam("userName", $args['userName']); $sth->execute();
       $users = $sth->fetchObject();
           return $this->response->withJson($users);
  });

  $app->put('/edit/[{userID}]', function ($request, $response) {
    $input = $request->getParsedBody();
    $sth = $this->db->prepare(
        "UPDATE users
        SET lastName=:lastName, firstName=:firstName, email=:email, 
        userName=:userName, pWord=:pWord, income=:income
        WHERE userID=:userID"
    );
    $sth->bindParam("lastName", $input['lastName']);
    $sth->bindParam("fistName", $input['firstName']);
    $sth->bindParam("email", $input['email']);
    $sth->bindParam("userName", $input['userName']);
    $sth->bindParam("pWord", $input['pWord']);
    $sth->bindParam("income", $input['income']);
    $sth->execute();
    return $this->response->withJson($input);
  });

});
