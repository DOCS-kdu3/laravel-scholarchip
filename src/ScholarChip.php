<?php

namespace Itacs\ScholarChip;

use Itacs\ScholarChip\Core\ScholarChipCore;
use SoapClient;

class ScholarChip extends ScholarChipCore
{
    /**
    * Order statuses as defined by ScholarChip API documentation.
    */
    const CANCELLED = "Cancelled";
    const EXPIRED = "Expired";
    const INITIALIZED = "Initialized";
    const INVALID = "Invalid";
    const PENDING = "Pending";
    const PROCESSED = "Processed";
    const FAILED = "Failed";

    public function __construct($user, $password, $gl, $endpointUrl)
    {
        // apparently there are connection issues when user_agent is
        // not specified 
        $soapClient = new SoapClient(
                                $endpointUrl,
                                array('user_agent'=>'')
                            );
        parent::__construct($soapClient, $user, $password, $gl);
    }
}
