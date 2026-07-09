<?php

namespace App\Enums;

enum DestinationStatus: string
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case ARCHIVED = 'archived';
}