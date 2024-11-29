<?php

use App\Helpers;

$class = "slot empty";
$href = "/slot/{$slot->id}/view";
$name = "";

// If there is no slot user, display a link to the take shift page
if(is_null($slot->user))
{
    if(is_null($slot->start_date))
    {
        $name = "Open for Volunteers";
    }
    else
    {
        $start = strtotime($slot->start_time);
        $end = strtotime($slot->end_time);

        $name = date("h:i a", $start) . " - " . date("h:i a", $end);
    }
}

// If there is a slot user, set the class to taken
else
{
    $class = "slot taken";
    $name = Helpers::displayName($slot->user);

    // If the slot is taken by the current user, display a link to the release page
    if($slot->user->id === Auth::user()->id)
    {
        $class = "slot taken-by-current-user";
    }
}

if($href)
{
    $href = "href='{$href}'";
}

if(!is_null($slot->start_date))
{
    // If the event has passed, remove any links
    $start_date = new \Carbon\Carbon($slot->start_date);

    if($start_date->lt(\Carbon\Carbon::now()))
    {
        if (!Auth::user()->hasRole('department-lead') && !Auth::user()->hasRole('admin'))
        {
            $href = "";
        }
    }
}

if($slot->trashed()) {
    $class = 'slot taken-by-current-user';
}

?>

<span class="slot-wrap" data-start="{{ $slot->start_time }}" data-duration="{{ $schedule->duration }}" data-row="{{ $slot->row }}">
    <a {!! $href !!} class="{{ $class }}" data-id="{{ $slot->id }}" title="{{ $name }}">{{ $name }}</a>
</span>
