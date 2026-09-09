<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AttendanceNotEditableException extends ConflictHttpException
{
    public function __construct()
    {
        parent::__construct('Attendance cannot be edited for this lesson.');
    }
}
