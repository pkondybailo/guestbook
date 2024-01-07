<?php

namespace App\Enum;

enum CommentStatusEnum: string
{
    case Published = 'published';
    case Rejected = 'rejected';
    case Spam = 'spam';
    case Submitted = 'submitted';
}
