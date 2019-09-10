<?php

namespace App\Listeners;

use Mail;
use App\Models\Notification;
use App\Models\User;
use App\Events\SlotChanged;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserShiftConfirmation
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Send a user a confimation email for taken/assigned shift
     *
     * @param  SlotChanged  $event
     * @return void
     */
    public function handle(SlotChanged $event)
    {
        if ($event->change['status'] === 'taken')
        {
            $admin_assigned = false;
            if (isset($event->change['admin_assigned']))
            {
                $admin_assigned = $event->change['admin_assigned'];
            }

            $user = User::where('name', $event->change['name'])->first();

            Notification::queue($user, 'email', 'user-shift-confirmation', [
                'event' => 'slot_taken',
                'slot_id' => $event->slot->id,
                'user_email' => $event->change['email'],
                'user_name' => $event->change['name'],
                'event_name' => $event->slot->schedule->shift->event->name,
                'shift_name' => $event->slot->schedule->shift->name,
                'start_date' => $event->slot->start_date,
                'start_time' => $event->slot->start_time,
                'end_time' => $event->slot->end_time,
                'admin_assigned' => $admin_assigned,
            ]);
        }
        else
        {
            // Get the most recently created notification for the slot
            $notification = Notification::where('user_to', $event->slot->user->id)
                ->where('metadata->slot_id', $event->slot->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $notification->status = 'canceled';
            $notification->save();
        }
    }
}
