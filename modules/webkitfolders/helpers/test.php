<?php 

$apikey = file_get_contents("/etc/mailgunapikey.conf");
$domain = file_get_contents("/etc/mailgundomain.conf");
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
exec($cmd);
echo "$cmd";
?>