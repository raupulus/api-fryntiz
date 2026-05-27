<?php

namespace App\Enums;

enum CvRepositoryTypeEnum: string
{
    case GitHub = 'github';
    case GitLab = 'gitlab';
    case Bitbucket = 'bitbucket';
    case Gitea = 'gitea';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::GitLab => 'GitLab',
            self::Bitbucket => 'Bitbucket',
            self::Gitea => 'Gitea',
            self::Other => 'Otro',
        };
    }
}
