<?php

namespace Released\QueueBundle\Events;

class QueueEventNames
{
    /** When partner callback return HTTP code != 200 */
    const UNEXPECTED_HTTP_CODE = 'queue_bundle.unexpected_http_code';
}