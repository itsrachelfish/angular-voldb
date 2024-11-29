<?php

namespace App\Http\Controllers;

use Mail;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\SlotRequest;
use App\Http\Requests\SlotEditRequest;
use App\Models\Slot;
use App\Models\User;
use App\Models\UserRole;
use App\Helpers;

use Illuminate\Support\Facades\Auth;

use App\Events\SlotChanged;
use Carbon\Carbon;

class SlotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('bindings');
    }

    // Helper function to determine if an event has passed
    private function eventHasPassed(Slot $slot)
    {
        $start_date = new Carbon($slot->start_date);

        if($start_date->lte(Carbon::tomorrow()))
        {
            return true;
        }

        return false;
    }

    // Helper function to determine allowed roles
    private function userAllowed(Slot $slot)
    {
        $user = Auth::user();
        $slotRoles = $slot->schedule->getRoles();
        $allowed = false;

        // Check each allowed role to see if the user has permission
        foreach($slotRoles as $slotRole)
        {
            if($user->hasRole($slotRole->role->name))
            {
                $allowed = true;
            }
        }

        return $allowed;
    }

    private function canAlterIfDeleted($slot)
    {
        if($slot->trashed()) {
            $is_assigned = false;
            if(isset($slot->user->id)) {
                $is_assigned = $slot->user->id === Auth::user()->id;
            }
            $is_lead = Auth::user()->hasRole('department-lead');
            $is_admin = Auth::user()->hasRole('admin');

            if(!$is_assigned && !$is_lead && !$is_admin) {
                return false;
            }
        }

        return true;
    }

    // Page to view slots
    public function view(Request $request, $id)
    {
        $slot = Slot::withTrashed()->find($id);

        if(!$this->canAlterIfDeleted($slot)) {
            return redirect("/event/{$slot->event->id}");
        }

        if(is_null(Auth::user()->data) or empty(Auth::user()->data->full_name))
        {
            $request->session()->flash('error', "You must enter your name before you can sign up for shifts.");
            return redirect('/profile/data/edit');
        }

        return view('pages/slot/view', compact('slot'));
    }

    // Add yourself to an existing slot
    public function take(SlotRequest $request, Slot $slot)
    {
        // Error handling
        if($slot->schedule->password)
        {
            // TODO: Succeptable to timing attacks? lol
            if($request->get('password') != $slot->schedule->password)
            {
                $request->session()->flash('error', 'The password you entered is incorrect. Please contact your department lead to make sure you have the right password.');
                return redirect()->back();
            }
        }
        else
        {
            if(!$this->userAllowed($slot))
            {
                $request->session()->flash('error', 'This shift is only available to certain user groups, your account must be approved by an administrator before signing up.');
                return redirect()->back();
            }
        }

        if($this->eventHasPassed($slot))
        {
            $request->session()->flash('error', 'This event is starting soon or has already started, you are no longer able to sign up for shifts online. See you at the burn!');
            return redirect()->back();
        }

        // Has somebody else already taken this slot?
        if(is_null($slot->user))
        {
            $slot->user_id = Auth::user()->id;
            $slot->save();

            event(new SlotChanged($slot, ['status' => 'taken', 'name' => Auth::user()->name, 'email' => Auth::user()->email]));
            $request->session()->flash('success', 'You signed up for a volunteer shift.');

            // If a password was used
            if($slot->schedule->password)
            {
                // Assign the user to any roles this shift requires
                $roles = [];

                foreach($slot->schedule->getRoles() as $role)
                {
                    $roles[] = $role->role->name;
                }

                UserRole::assign(Auth::user(), $roles);
            }
        }
        else
        {
            $request->session()->flash('error', 'This slot has already been taken by someone else.');
        }

        return redirect('/event/' . $slot->event->id);
    }

    // Remove yourself from a slot
    public function release(Request $request, $id)
    {
        $slot = Slot::withTrashed()->find($id);

        if(!$this->canAlterIfDeleted($slot)) {
            return redirect("/event/{$slot->event->id}");
        }

        if($slot->trashed()) {
            $slot->forceDelete();
            return redirect("/event/{$slot->event->id}");
        }

        if($this->eventHasPassed($slot))
        {
            $request->session()->flash('error', 'This event has already passed, you are no longer able to make changes to your shifts.');
        }
        else
        {
            if(!is_null($slot->user) && $slot->user->id === Auth::user()->id)
            {
                $slot->user_id = null;
                $slot->save();

                event(new SlotChanged($slot, ['status' => 'released']));
                $request->session()->flash('success', 'You are no longer volunteering for your shift.');
            }
            else
            {
                $request->session()->flash('error', 'You are not currently scheduled to volunteer for this shift.');
            }
        }

        return redirect('/event/' . $slot->event->id);
    }

    // change the flake column status
    public function edit(SlotEditRequest $request, Slot $slot)
    {
        $slot->status = $request->get('status');
        $slot->save();
        return;
    }

    public function adminRelease(Request $request, $id)
    {
        $slot = Slot::withTrashed()->find($id);

        if(!$this->canAlterIfDeleted($slot)) {
            return redirect("/event/{$slot->event->id}");
        }

        if($slot->trashed()) {
            $slot->forceDelete();
            return redirect("/event/{$slot->event->id}");
        }

        if(!is_null($slot->user))
        {
            $username = Helpers::displayName($slot->user);

            $slot->user_id = null;
            $slot->save();
            event(new SlotChanged($slot, ['status' => 'released', 'admin_released' => true]));
            $request->session()->flash('success', $username.' is removed!!');
        }
        else
        {
            $request->session()->flash('error', 'there is nobody currently scheduled to volunteer for this shift.');
        }
        return redirect('/event/' . $slot->event->id);
    }

    public function adminAssign(Request $request, Slot $slot)
    {
        $user = User::findorFail($request->get('user'));

        if(is_null($slot->user))
        {
            $username = Helpers::displayName($user);

            $slot->user_id=$user->id;
            $slot->save();
            event(new SlotChanged($slot, ['status' => 'taken', 'admin_assigned' => true, 'name' => $user->name, 'email' => $user->email]));
            $request->session()->flash('success', 'You added '.$username.' to this shift');
        }
        return redirect('/event/'.$slot->event->id);
    }
}
