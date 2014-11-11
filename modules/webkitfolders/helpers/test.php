<?php 

$apikey = $_ENV["MAILGUN_APIKEY"];
$domain = $_ENV["MAILGUN_DOMAIN"];
$from="bob@bob.com";
$to="kaiyadavenport@gmail.com";
$subject="apples";
$body="oranges";
$arr = array(
  "curl -s --user '$apikey' https://api.mailgun.net/v2/$domain/messages",
  "-F from='$from' ",
  "-F to='$to' ",
  "-F subject='$subject' ",
  "-F text='$body' "
);

$cmd = implode(" ", $arr);

#exec($cmd);
echo "$cmd";
?>