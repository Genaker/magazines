<?php

namespace App\Support;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Models\Magazine;
use App\Models\User;
use App\Policies\MagazinePolicy;

/** Resolves magazine submission status when a post is saved with a magazine. */
final class MagazinePostSubmission
{
    public static function statusOnSave(
        User $user,
        ?Magazine $magazine,
        PostStatus $postStatus,
    ): ?MagazineSubmissionStatus {
        if ($magazine === null) {
            return null;
        }

        if (! $magazine->require_post_approval) {
            return $postStatus === PostStatus::Published
                ? MagazineSubmissionStatus::Approved
                : null;
        }

        $canReview = app(MagazinePolicy::class)->reviewSubmissions($user, $magazine);

        if ($postStatus === PostStatus::Published) {
            return $canReview
                ? MagazineSubmissionStatus::Approved
                : MagazineSubmissionStatus::Pending;
        }

        return $canReview ? null : MagazineSubmissionStatus::Pending;
    }
}
