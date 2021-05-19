<?php

if (!isset($submission->subscriptions[0])) {
    return '';
}
$subsId = $submission->subscriptions[0]->vendor_subscriptipn_id;
echo $subsId;
