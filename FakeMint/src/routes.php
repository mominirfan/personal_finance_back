<?php

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;
use function Monolog\Handler\error_log;



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
      $sql = "INSERT INTO users (userName, pWord, lastName, firstName, email, income, bal) 
      VALUES (:userName, :pWord, :lastName, :firstName, :email, :income, :bal)";
      $sth = $this->db->prepare($sql);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("pWord", $input['pWord']);
      $sth->bindParam("lastName", $input['lastName']);
      $sth->bindParam("firstName", $input['firstName']);
      $sth->bindParam("email", $input['email']);
      $sth->bindParam("income", $input['income']);
      $sth->bindParam("bal", $input['bal']);
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
          SET lastName=:lastName, firstName=:firstName, email=:email, pWord=:pWord, income=:income
          WHERE userName=:userName"
      );
      $sth->bindParam("lastName", $input['lastName']);
      $sth->bindParam("firstName", $input['firstName']);
      $sth->bindParam("email", $input['email']);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("pWord", $input['pWord']);
      $sth->bindParam("income", $input['income']);
      $sth->execute();
      return $this->response->withJson($input);
    });

    $app->put('/edit_inc', function ($request, $response, $args){
      $input = $request->getParsedBody();
      $sth = this->db->prepare(
        "UPDATE users
        set income=:income
        WHERE userName:userName"
      );
      $sth->bindParam("income", $input['income']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });

    $app->put('/edit_bal', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE users
          SET bal=:bal
          WHERE userName=:userName"
      );
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("bal", $input['bal']);
      $sth->execute();
      return $this->response->withJson($input);

    });

    $app->get('/order-loans/[{userName}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT * FROM loans WHERE userName=:userName
        AND DATEDIFF(CAST(CONCAT(YEAR(NOW()),'-',MONTH(NOW()),'-',loans.paymentDay) as DATE), NOW()) > 0
        ORDER BY DATEDIFF(CAST(CONCAT(YEAR(NOW()),'-',MONTH(NOW()),'-',loans.paymentDay) as DATE), NOW())"
      );
      $sth->bindParam("userName", $args['userName']); 
      $sth->execute();
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

    $app->put('/update-loan', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE loans
          SET paid=:paid
          WHERE userName=:userName AND loanName=:loanName AND loanDescription=:loanDescription"
      );
      $sth->bindParam("paid", $input['paid']);
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("loanDescription", $input['loanDescription']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });

    $app->post('/add-loan', function ($request, $response) {
      $input = $request->getParsedBody();
      $sql = "INSERT INTO loans (userName, loanName, loanAmount, interest, paymentDay, loanPayment, paid, 
      loanDescription, loanPaidAmt, loanBalance) 
      VALUES (:userName, :loanName, :loanAmount, :interest, :paymentDay, :loanPayment, 0, 
      :loanDescription, 0, :loanAmount);
      UPDATE budgets SET amt = amt + :loanPayment WHERE budgetType = 'Loans' AND userName=:userName";
      $sth = $this->db->prepare($sql);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("loanAmount", $input['loanAmount']);
      $sth->bindParam("loanPayment", $input['loanPayment']);
      $sth->bindParam("interest", $input['interest']);
      $sth->bindParam("paymentDay", $input['paymentDay']);
      $sth->bindParam("loanBalance", $input['loanAmount']);
      $sth->bindParam("loanDescription", $input['loanDescription']);
      $sth->execute();
      return $this->response->withJson($input); 
    });

    $app->put('/edit-loan', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE loans
          SET loanAmount=:loanAmount, interest=:interest, paymentDay=:paymentDay, 
          loanPayment=:loanPayment, loanBalance=:loanBalance, loanPaidAmt=:loanPaidAmt
          WHERE userName=:userName AND loanName=:loanName AND loanDescription=:loanDescription"
      );
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("loanAmount", $input['loanAmount']);
      $sth->bindParam("loanPayment", $input['loanPayment']);
      $sth->bindParam("loanBalance", $input['loanBalance']);
      $sth->bindParam("loanPaidAmt", $input['loanPaidAmt']);
      $sth->bindParam("loanDescription", $input['loanDescription']);
      $sth->bindParam("interest", $input['interest']);
      $sth->bindParam("paymentDay", $input['paymentDay']);
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });

    $app->delete('/delete-loan', function($request, $response){
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "DELETE FROM loans WHERE userName=:userName AND loanName=:loanName 
          AND loanDescription=:loanDescription
          UPDATE budgets SET amt = amt - :loanPayment 
          WHERE budgetType = 'Loans' AND userName=:userName"
      );
      $sth->bindParam("loanName", $input['loanName']);
      $sth->bindParam("loanDescription", $input['loanDescription']);
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
          SET amt=:amt
          WHERE userName=:userName AND budgetType=:budgetType"
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
        "SELECT * FROM expenses WHERE userName=:userName ORDER BY date DESC"
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
        "SELECT exType , SUM(amt) as amt FROM expenses WHERE userName=:userName GROUP BY exType"
      );
      $sth->bindParam("userName", $args['userName']); $sth->execute();
      $res = $sth->fetchAll();
          return $this->response->withJson($res);
    });

    $app->put('/increment-bal', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $change = $input['change'];
      $sth = $this->db->prepare(
          "UPDATE users
          SET bal= (bal + $change)
          WHERE userName=:userName"
      );
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });

    $app->put('/decrement-bal', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $change = $input['change'];
      $sth = $this->db->prepare(
          "UPDATE users
          SET bal= (bal - $change)
          WHERE userName=:userName"
      );
      $sth->bindParam("userName", $input['userName']);
      $sth->execute();
      return $this->response->withJson($input);
    });
    $app->get('/get-sugg/[{userName}]', function ($request, $response, $args) {
      $sth = $this->db->prepare(
        "SELECT exType, SUM(amt) as amt
        FROM expenses
        WHERE userName=:userName
        GROUP BY exType
        ORDER BY amt DESC
        LIMIT 2"
      );
      $sth->bindParam("userName", $args['userName']);
      $sth->execute();
      $types = $sth->fetchAll();
      $jsonTypes = json_encode($types);
      $array = json_decode($jsonTypes,true);
      $firstST = $array[0]['exType'];
      $secST = $array[1]['exType'];
      $quer = $this->db->prepare(
        "SELECT * FROM suggs WHERE suggType=:firstST OR suggType=:secST"
      );
      $quer->bindParam("firstST", $firstST);
      $quer->bindParam("secST", $secST);
      $quer->execute();
      $suggs = $quer->fetchAll();

          return $this->response->withJson($suggs);
    });

});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
  $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
  return $handler($req, $res);
});


