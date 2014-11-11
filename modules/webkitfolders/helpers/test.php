<?php 

$apikey = $_ENV["MAILGUN_APIKEY"];
$domain = $_ENV["MAILGUN_DOMAIN"];
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