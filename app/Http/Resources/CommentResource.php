<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
       return [
            "comment_id" => $this->comment_id,
            "comment" => $this->comment,
            "object_id" => $this->object_id,
            "object_type" => $this->object_type,
            "parent_id" => $this->parent_id,
            "user_id" => $this->user_id,
            "users" => $this->users,
            "job" => $this->job
       ];
    }
}
