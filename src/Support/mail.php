<?php

declare(strict_types=1);

namespace LangLearn\Support;

use Resend;

function getEmailClient(): Resend\Client
{
    return Resend::client($_ENV['RESEND_API_KEY']);
}