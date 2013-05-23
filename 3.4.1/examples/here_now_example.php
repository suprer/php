<?php
require_once('../lib/Pubnub/Pubnub.php');

$pubnub    = new \Pubnub\Pubnub( 'demo', 'demo', false , false, false );
$here      = $pubnub->here_now(array( 'channel' => 'my_channel' ));
$occupancy = $here['occupancy'];
$user_ids  = $here['uuids'];

print("UUIDs (userIDs): ");
print_r($user_ids);
print("\n");
print("OCCUPANTS: $occupancy\n\n");
?>
