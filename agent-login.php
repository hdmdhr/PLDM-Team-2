<?php
/**************************
*
* Author: PLDM Team 2
* Date: Feb. 14, 2019
* Course: CPRG 216 Project
* Description: login page, before log user in, check login try times, check if user id and password match
*
**************************/

  if(session_id() == '' || !isset($_SESSION)) {
      session_start();  // if session isn't start, start it
  }

  if (isset($_SESSION['loggedin-id-fn'])) {
    // if there is already a signed in user, head back to home page with alert message
    header("Location: http://localhost/PLDM-Team-2/index.php?alert=You're already logged in.");
    exit;
  }

  if ($_POST) {

    // ----- Check if user reached maximum try times(15) per hour -----

    function checkTryTimes($tryTimes) {
      // check try times to show corresponding message
      if ($tryTimes >= 15) {
        echo "<h1 class='alert alert-danger'>You've reached the maximum try times, please try an hour later.</h1>";
        exit;
      } else {
        echo "<h2 class='alert alert-danger'>You've tried ".$tryTimes." times, ".(15 - $tryTimes)." times left.</h2>";
      }
    }

    if (!isset($_SESSION['try-times']) || time() - $_SESSION['try-times']['time'] > 3600) {
      // if session doesn't exist or expired, create a new one, check try times
      $_SESSION['try-times'] = array('try' => 1, 'time' => time() );
      checkTryTimes($_SESSION['try-times']['try']);
    } else {
      // session exist less than 1 hour, check try times
      $_SESSION['try-times']['try'] += 1;
      checkTryTimes($_SESSION['try-times']['try']);
    }

    // ----- check username and pin against database -----

    include_once('php/function.php');
    $travelExperts = ConnectDB();
    $inputUserName = $_POST['UserId'];
    $inputPassword = $_POST['Password'];
    $sql = "SELECT AgtPassword, AgentId, AgtFirstName FROM agents WHERE AgtUserName = '$inputUserName'";
    $resultArray = queryDataArrayFromDatabase($sql,$travelExperts);

    if (!$resultArray) {
      // if query return no result, means there is no username in database matches the input username
      echo "<h2 class='alert alert-danger' role='alert'>User name or password do not exist.</h2>";
    } else {
      // if there is a matched user name, extract password, id, and first name values for future use, check password
      $savedPin = $resultArray[0]['AgtPassword'];
      $agtId = $resultArray[0]['AgentId'];
      $agtFirstName = $resultArray[0]['AgtFirstName'];

      if (password_needs_rehash($savedPin,PASSWORD_DEFAULT)) {
        // if pin need rehash, rehash it, and update database
        $savedPin = password_hash($savedPin,PASSWORD_DEFAULT);
        $updateSql = "UPDATE `agents` SET `AgtPassword` = '$savedPin' WHERE `agents`.`AgentId` = $agtId";
        if (!$travelExperts->query($updateSql)) {
          echo "<h2 class='alert alert-danger' role='alert'>Updating password failed.</h2>";
        }
      }

      if (!password_verify($inputPassword,$savedPin)) {
        echo "<h2 class='alert alert-danger' role='alert'>Password or user name do not exist.</h2>";
      } else {
        // if passwords match, save user id and first name in a session, head to *agent entry page (temporary)
        $_SESSION['loggedin-id-fn'] = array('Agent',$agtId,$agtFirstName);
        header("Location: http://localhost/PLDM-Team-2/agent-signup.php");
      }

    }

  } else {
    // echo "No post received.";
  }

 ?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="generator" content="Jekyll v3.8.5">
  <title>Agent Login</title>

  <link href="https://fonts.googleapis.com/css?family=Lato:400,700,900|Raleway:400,700,700i,900" rel="stylesheet">
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/main.css">
  <!-- Custom styles for this template -->
  <link href="css/signin.css" rel="stylesheet">
</head>

<body class="text-center">
  <?php
    include_once('php/header.php');

    // if receive any $_GET['alert'], show alert
    if (isset($_GET['alert'])) {
      echo "<h1 class='alert alert-danger'>".$_GET['alert']."</h1>";
    }
  ?>

  <form class="form-signin mt-5" method="post" action="#">

    <?php
      if (isset($errorMsg)) {
        echo $errorMsg;
      }
     ?>
    <a href="index.php" target="_blank">
      <img class="mb-2" src="img/balloon.png" alt="logo" width="72" height="72">
    </a>
    <h1 class="h3 mb-3 font-weight-normal">Agent login</h1>
    <div class="signin-section mb-3">
      <label for="user-id">User ID</label>
      <input type="text" id="user-id" class="form-control" name="UserId" placeholder="Your user id or email" required autofocus>
    </div>
    <div class="signin-section mb-3">
      <label for="inputPassword">Password</label>
      <input type="password" id="inputPassword" class="form-control" name="Password" placeholder="Password" required>
    </div>

    <div class="checkbox mb-3">
      <label>
        <input type="checkbox" name="rememberMe" value="rememberMe"> Remember me
      </label>
    </div>
    <input type="hidden" name="tries" value="1">
    <button class="btn btn-lg btn-primary btn-block" type="submit">Log In</button>
  </form>

  <?php include_once('php/footer.php') ?>

</body>


</html>
