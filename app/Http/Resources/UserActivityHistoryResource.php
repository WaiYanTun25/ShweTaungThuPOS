<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserActivityHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activity_log = $this->map(function ($activity) {
            $carbonDate = Carbon::parse($activity->created_at);
            // Format the date as per your requirements
            $formattedDate = $carbonDate->format('d M Y, g:iA');
            // Get the event type from the activity log
            $event = $activity->event == 'created' ? 'Create' : ($activity->event == 'Delete' ? 'Delete' : 'Update');

            $userName = ($activity->causer_id == Auth::user()->id) ? 'You' : $activity->causer->name;
            // Replace {userName} with the actual user's name in the description
            $description = str_replace('{userName}', $userName, $activity->description);

            return [
                'log_name' => $activity->log_name,
                'event' => $event,
                'description' => $description,
                'created_at' => $formattedDate
            ];
        });

        return [
            "acitivy_history" => $activity_log
        ];
    }
}
