<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class CommentHelper
{
    public static function setCommentData($jobComment, $job, $lines)
    {
        $jobCommentArr = [];
        foreach ($jobComment as $comment) {
            array_push($jobCommentArr, [
                "comment_id" => $comment->comment_id,
                "parent_id" => $comment->parent_id,
                "firstname" => $comment->users ? $comment->users->firstname : null,
                "lastname" => $comment->users ? $comment->users->lastname : null,
                "invoice_number" => $job->deals ? $job->deals->invoice_number : null,
                "job_id" => $job->job_id,
                "comment" => $comment->comment
            ]);
        }

        foreach ($lines as $line) {
            foreach ($line->line_comments as $comment) {
                array_push($jobCommentArr, [
                    "comment_id" => $comment->comment_id,
                    "parent_id" => $comment->parent_id,
                    "firstname" => $comment->users ? $comment->users->firstname : null,
                    "lastname" => $comment->users ? $comment->users->lastname : null,
                    "invoice_number" => $job->deals ? $job->deals->invoice_number : null,
                    "line_id" => $line->line_item_id,
                    "comment" => $comment->comment
                ]);
            }
        }


        return $jobCommentArr;
    }

    public static function setLineCommentData($job, $lines)
    {
        $lineCommentArr = [];

        foreach ($lines as $line) {
            foreach ($line->line_comments as $comment) {
                array_push($lineCommentArr, [
                    "comment_id" => $comment->comment_id,
                    "parent_id" => $comment->parent_id,
                    "firstname" => $comment->users ? $comment->users->firstname : null,
                    "lastname" => $comment->users ? $comment->users->lastname : null,
                    "invoice_number" => $job->deals ? $job->deals->invoice_number : null,
                    "line_id" => $line->line_item_id,
                    "comment" => $comment->comment
                ]);
            }
        }
        return $lineCommentArr;
    }
}
