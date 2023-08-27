<?php

namespace App\Enum;

enum CommentStatusEnum: string
{
    case Published = 'published';
    case Spam = 'spam';
    case Submitted = 'submitted';
}
