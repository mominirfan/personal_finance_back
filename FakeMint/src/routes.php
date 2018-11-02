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
      return $this->response->withJson($input); 
    });

    $app->get('/login/[{userName}]', function ($request, $response, $args) {
       $sth = $this->db->prepare(
         "SELECT * FROM users WHERE userName=:userName"
       );
       $sth->bindParam("userName", $args['userName']); $sth->execute();
       $users = $sth->fetchObject();
           return $this->response->withJson($users);
    });

    $app->put('/edit', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE users
          SET lastName=:lastName, firstName=:firstName, email=:email, 
          userName=:userName, pWord=:pWord, income=:income
          WHERE userID=:userID"
      );
      $sth->bindParam("userID", $input['userID']);
      $sth->bindParam("lastName", $input['lastName']);
      $sth->bindParam("firstName", $input['firstName']);
      $sth->bindParam("email", $input['email']);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("pWord", $input['pWord']);
      $sth->bindParam("income", $input['income']);
      $sth->execute();
      return $this->response->withJson($input);
    });

    $app->post('/add-loan', function ($request, $response) {
      $input = $request->getParsedBody();
      $sql = "INSERT INTO loans (userName, loanName, loanAmount, interest) 
      VALUES (:userName, :loanName, :loanAmount, :interest)";
      $sth = $this->db->prepare($sql);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("loanAmount", $input['loanAmount']);
      $sth->bindParam("interest", $input['interest']);
      $sth->execute();
      return $this->response->withJson($input); 
    });

    $app->put('/edit-loan', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE loans
          SET loanName=:loanName, loanAmount=:loanAmount
          WHERE userName=:userName"
      );
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("loanAmount", $input['loanAmount']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });
  
  
    $app->post('/add-budget', function ($request, $response) {
      $input = $request->getParsedBody();
      $sql = "INSERT INTO budgets (userName, budgetType, active_date, amt) 
      VALUES (:userName, :budgetType, now(), :amt)";
      $sth = $this->db->prepare($sql);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("budgetType", $input['budgetType']);
      $sth->bindParam("amt", $input['amt']);
      $sth->execute();
      return $this->response->withJson($input); 
     });
     $app->put('/edit-budget', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE budgets
          SET budgetType=:budgetType, amt=:amt
          WHERE userName=:userName"
      );
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("loanAmount", $input['loanAmount']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });  
  
});

