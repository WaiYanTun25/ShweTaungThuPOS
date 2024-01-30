<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user_list = $this->map(function ($user) {
            return [
                'id' => $user->id,
                'user_code' => $user?->user_code ?? "-",
                'name' => $user->name,
                'phone_no' => $user->phone_number,
                'join_date' => formatToCustomDate($user->join_date),
                'branch_id' => $user->branch_id,
                'role' => $user->getRoleNames()[0],
                'branch_name' => $user->branch?->name ?? '-',
            ];
        });
        return [
            'user_list' => $user_list,
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'links' => $this->links(),
                'path' => $this->path(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
        ];
    }
}
