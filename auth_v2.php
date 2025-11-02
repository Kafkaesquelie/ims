<?php
include_once('includes/load.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $req_fields = array('username','password');
  validate_fields($req_fields);

  $username = remove_junk($_POST['username']);
  $password = remove_junk($_POST['password']);

  if (empty($errors)) {

    $user = authenticate_v2($username, $password);

    if ($user) {
      $session->login($user['id']);
      updateLastLogIn($user['id']);

      $session->msg("s", "Hello ".$user['username'].", Welcome to BSU-INV.");

      if ($user['user_level'] === '1') {
        redirect('admin.php', false);
      } elseif ($user['user_level'] === '2') {
        redirect('super_admin.php', false);
      } else {
        redirect('home.php', false);
      }

    } else {
      $session->msg("d", "Sorry Username/Password incorrect.");
      redirect('login.php', false);
    }

  } else {
    $session->msg("d", $errors);
    redirect('login.php', false);
  }

} else {
  redirect('login.php', false);
}
?>
