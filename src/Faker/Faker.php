<?php

namespace Railroad\Railcontent\Faker;

use Carbon\Carbon;
use Faker\Generator;


class Faker extends Generator
{
    public function permission(array $override = [])
    {
        return array_merge(
            [
                'name' => $this->word,
                'brand' => config('railcontent.brand'),
            ],
            $override
        );
    }

    public function userPermission(array $override = [])
    {
        return array_merge(
            [
                'user_id' => $this->randomNumber(),
                'permission_id' => $this->randomNumber(),
                'start_date' =>Carbon::now()
                    ->toDateTimeString(),
           'expiration_date' => null,
                'created_at' => Carbon::now()
                    ->toDateTimeString(),
                'updated_at' => null
            ],
            $override
        );
    }

    public function contentPermission(array $override = [])
    {
        return array_merge(
            [
                'content_id' => $this->randomNumber(),
                'content_type' => $this->word(),
                'permission_id' => $this->randomNumber(),
                'brand' => config('railcontent.brand'),
            ],
            $override
        );
    }

    public function contentHierarchy(array $override = [])
    {
        return array_merge(
            [
                'parent_id' => $this->randomNumber(),
                'child_id' => $this->randomNumber(),
                'child_position' => $this->randomNumber(),
                'created_on' =>Carbon::now(),
            ],
            $override
        );
    }

    public function comment(array $override = [])
    {
        return array_merge(
            [
                'content_id' => $this->randomNumber(),
                'parent_id' => $this->randomNumber(),
                'user_id' => $this->randomNumber(),
                'comment' => $this->paragraph(),
                'temporary_display_name' => $this->word,
                'created_on' =>Carbon::now(),
            ],
            $override
        );
    }

    public function commentLike(array $override = [])
    {
        return array_merge(
            [
                'comment_id' => $this->randomNumber(),
                'user_id' => $this->randomNumber(),
                'created_on' =>Carbon::now(),
            ],
            $override
        );
    }
}