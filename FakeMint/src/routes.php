<?php

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;



$app->options('/{routes:.+}', function ($request, $response, $args) {
  return $response;
});

$app->add(function ($req, $res, $next) {
  $response = $next($req, $res);
  return $response
          ->withHeader('Access-Control-Allow-Origin', '*')
          ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
          ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});


$app->post('/login', function (Request $request, Response $response, array $args) {
 
  $input = $request->getParsedBody();
  $sql = "SELECT * FROM users WHERE userName= :userName";
  $sth = $this->db->prepare($sql);
  $sth->bindParam("userName", $input['userName']);
  $sth->execute();
  $user = $sth->fetchObject();



  // verify email address.
  if(!$user) {
      return $this->response->withJson(['error' => true, 'message' => 'Username or Password is not valid.']);  
  }

  // verify password.
  if ($input['pWord'] != $user->pWord) {
      return $this->response->withJson(['error' => true, 'message' => 'Username or Password is not valid.']);  
  }
  return $this->response->withJson(['userName' => $user->userName]);

});


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

    $app->get('/order-loans/[{userName}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT * FROM loans WHERE userName=:userName
        AND DATEDIFF(CAST(CONCAT(YEAR(NOW()),'-',MONTH(NOW()),'-',loans.paymentDay) as DATE), NOW()) > 0
        ORDER BY DATEDIFF(CAST(CONCAT(YEAR(NOW()),'-',MONTH(NOW()),'-',loans.paymentDay) as DATE), NOW())"
      );
      $sth->bindParam("userName", $args['userName']); $sth->execute();
      $users = $sth->fetchAll();
          return $this->response->withJson($users);
    });

    $app->get('/get-loans/[{userName}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT * FROM loans WHERE userName=:userName"
      );
      $sth->bindParam("userName", $args['userName']); $sth->execute();
      $users = $sth->fetchAll();
          return $this->response->withJson($users);
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

    $app->delete('/delete-loan', function($request, $response){
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "DELETE FROM loans WHERE userName=:userName AND loanName=:loanName"
      );
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);

    });

    $app->get('/get-budgets/[{userName}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT * FROM budgets WHERE userName=:userName"
      );
      $sth->bindParam("userName", $args['userName']); $sth->execute();
      $users = $sth->fetchAll();
          return $this->response->withJson($users);
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
      $sth->bindParam("budgetType", $input['budgetType']);
      $sth->bindParam("amt", $input['amt']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });  

    $app->delete('/delete-budget', function($request, $response){
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "DELETE FROM budgets WHERE userName=:userName AND budgetType=:budgetType"
      );
      $sth->bindParam("budgetType", $input['budgetType']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);

    });
  

    $app->get('/get-expenses/[{userName}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT * FROM expenses WHERE userName=:userName"
      );
      $sth->bindParam("userName", $args['userName']); $sth->execute();
      $users = $sth->fetchAll();
          return $this->response->withJson($users);
    });
    


    $app->post('/add-expense', function ($request, $response) {
      $input = $request->getParsedBody();
      $sql = "INSERT INTO expenses (userName, exType, amt, date) 
      VALUES (:userName, :exType, :amt, now())";
      $sth = $this->db->prepare($sql);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("exType", $input['exType']);
      $sth->bindParam("amt", $input['amt']);
      $sth->execute();
      return $this->response->withJson($input); 
    });

    
    $app->put('/edit-expense', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE expenses
          SET exType=:exType, amt=:amt
          WHERE userName=:userName"
      );
      $sth->bindParam("exType", $input['exType']);
      $sth->bindParam("amt", $input['amt']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });  

    $app->delete('/delete-expense', function($request, $response){
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "DELETE FROM expenses WHERE userName=:userName AND exType=:exType"
      );
      $sth->bindParam("exType", $input['exType']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);

    });
    $app->get('/get-suggs/[{suggType}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT * FROM suggs WHERE suggType=:suggType"
      );
      $sth->bindParam("suggType", $args['suggType']); $sth->execute();
      $users = $sth->fetchAll();
          return $this->response->withJson($users);
    });

    $app->get('/get-total-spending/[{userName}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT exType , SUM(amt) FROM expenses WHERE userName=:userName GROUP BY exType"
      );
      $sth->bindParam("userName", $args['userName']); $sth->execute();
      $res = $sth->fetchObject();
          return $this->response->withJson($res);
    });



});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
  $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
  return $handler($req, $res);
});


